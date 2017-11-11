<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow;

use fab2s\NodalFlow\Callbacks\CallbackInterface;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Flows\FlowMap;
use fab2s\NodalFlow\Flows\FlowMapInterface;
use fab2s\NodalFlow\Flows\FlowStatus;
use fab2s\NodalFlow\Flows\FlowStatusInterface;
use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\Nodes\BranchNodeInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Class NodalFlow
 */
class NodalFlow implements FlowInterface
{
    /**
     * Flow steps triggering callbacks
     */
    const FLOW_START    = 'start';
    const FLOW_PROGRESS = 'progress';
    const FLOW_SUCCESS  = 'success';
    const FLOW_FAIL     = 'fail';

    /**
     * The parent Flow, only set when branched
     *
     * @var FlowInterface
     */
    public $parent;

    /**
     * This Flow id
     *
     * @var string
     */
    protected $id;

    /**
     * @var array
     */
    protected $flowIncrements = [];

    /**
     * The underlying node structure
     *
     * @var NodeInterface[]
     */
    protected $nodes = [];

    /**
     * The current Node index
     *
     * @var int
     */
    protected $nodeIdx = 0;

    /**
     * The last index value
     *
     * @var int
     */
    protected $lastIdx = 0;

    /**
     * The number of Node in this Flow
     *
     * @var int
     */
    protected $nodeCount = 0;

    /**
     * The number of iteration within this Flow
     *
     * @var int
     */
    protected $numIterate = 0;

    /**
     * The current registered Callback class if any
     *
     * @var CallbackInterface|null
     */
    protected $callBack;

    /**
     * Progress modulo to apply
     * Set to x if you want to trigger
     * progress every x iterations in flow
     *
     * @var int
     */
    protected $progressMod = 1024;

    /**
     * Continue flag
     *
     * @var bool
     */
    protected $continue = false;

    /**
     * Break Flag
     *
     * @var bool
     */
    protected $break = false;

    /**
     * Current Flow Status
     *
     * @var FlowStatusInterface
     */
    protected $flowStatus;

    /**
     * @var FlowMapInterface
     */
    protected $flowMap;

    /**
     * @var string|bool
     */
    protected $interruptNodeId;

    /**
     * Current nonce
     *
     * @var int
     */
    private static $nonce = 0;

    /**
     * Instantiate a Flow
     */
    public function __construct()
    {
        $this->id      = $this->uniqId();
        $this->flowMap = new FlowMap($this, $this->flowIncrements);
    }

    /**
     * Adds a Node to the flow
     *
     * @param NodeInterface $node
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function add(NodeInterface $node)
    {
        if ($node instanceof BranchNodeInterface) {
            // this node is a branch, set it's parent
            $node->getPayload()->setParent($this);
        }

        $node->setCarrier($this);

        $this->flowMap->register($node, $this->nodeIdx);
        $this->nodes[$this->nodeIdx] = $node;
        ++$this->nodeIdx;

        return $this;
    }

    /**
     * Adds a Payload Node to the Flow
     *
     * @param callable $payload
     * @param mixed    $isAReturningVal
     * @param mixed    $isATraversable
     *
     * @return $this
     */
    public function addPayload(callable $payload, $isAReturningVal, $isATraversable = false)
    {
        $node = PayloadNodeFactory::create($payload, $isAReturningVal, $isATraversable);

        $this->add($node);

        return $this;
    }

    /**
     * Register callback class
     *
     * @param CallbackInterface $callBack
     *
     * @return $this
     */
    public function setCallBack(CallbackInterface $callBack)
    {
        $this->callBack = $callBack;

        return $this;
    }

    /**
     * Used to set the eventual Node Target of an Interrupt signal
     * set to :
     * - A node hash to target
     * - true to interrupt every upstream nodes
     *     in this Flow
     * - false to only interrupt up to the first
     *     upstream Traversable in this Flow
     *
     * @param string|bool $interruptNodeId
     *
     * @return $this
     */
    public function setInterruptNodeId($interruptNodeId)
    {
        $this->interruptNodeId = $interruptNodeId;

        return $this;
    }

    /**
     * Set parent Flow, happens only when branched
     *
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setParent(FlowInterface $flow)
    {
        $this->parent = $flow;

        return $this;
    }

    /**
     * Get eventual parent Flow
     *
     * @return FlowInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Tells if this flow has a parent
     *
     * @return bool
     */
    public function hasParent()
    {
        return !empty($this->parent);
    }

    /**
     * Generates a truly unique id for the Flow context
     *
     * @return string
     */
    public function uniqId()
    {
        // while we're at it, drop any doubt about
        // colliding from here
        return \sha1(uniqid() . $this->getNonce());
    }

    /**
     * Execute the flow
     *
     * @param null|mixed $param The eventual init argument to the first node
     *                          or, in case of a branch, the last relevant
     *                          argument from upstream Flow
     *
     * @throws NodalFlowException
     *
     * @return mixed the last result of the
     *               last returning value node
     */
    public function exec($param = null)
    {
        try {
            $result = $this->rewind()
                    ->flowStart()
                    ->recurse($param);
            // set flowStatus to make sure that we have the proper
            // value in flowEnd even when overridden without (or when
            // improperly) calling parent
            if ($this->flowStatus->isRunning()) {
                $this->flowStatus = new FlowStatus(FlowStatus::FLOW_CLEAN);
            }

            $this->flowEnd();

            return $result;
        } catch (\Exception $e) {
            $this->flowStatus = new FlowStatus(FlowStatus::FLOW_EXCEPTION);
            $this->flowEnd();
            if ($e instanceof NodalFlowException) {
                throw $e;
            }

            throw new NodalFlowException('Flow execution failed', 0, $e, [
                'nodeMap' => $this->getNodeMap(),
            ]);
        }
    }

    /**
     * Get the stats array with latest Node stats
     *
     * @return array
     */
    public function getStats()
    {
        return $this->flowMap->getStats();
    }

    /**
     * Return the Flow id as set during instantiation
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * getId() alias for backward compatibility
     *
     * @deprecated
     *
     * @return string
     */
    public function getFlowId()
    {
        return $this->getId();
    }

    /**
     * Get the Node array
     *
     * @return NodeInterface[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Get/Generate Node Map
     *
     * @return array
     */
    public function getNodeMap()
    {
        return $this->flowMap->getNodeMap();
    }

    /**
     * Rewinds the Flow
     *
     * @return $this
     */
    public function rewind()
    {
        $this->nodeCount       = count($this->nodes);
        $this->lastIdx         = $this->nodeCount - 1;
        $this->break           = false;
        $this->continue        = false;
        $this->interruptNodeId = null;

        return $this;
    }

    /**
     * Define the progress modulo, Progress Callback will be
     * triggered upon each iteration in the flow modulo $progressMod
     *
     * @param int $progressMod
     *
     * @return $this
     */
    public function setProgressMod($progressMod)
    {
        $this->progressMod = max(1, (int) $progressMod);

        return $this;
    }

    /**
     * Get current $progressMod
     *
     * @return int
     */
    public function getProgressMod()
    {
        return $this->progressMod;
    }

    /**
     * The Flow status can either indicate be:
     *      - clean (isClean()): everything went well
     *      - dirty (isDirty()): one Node broke the flow
     *      - exception (isException()): an exception was raised during the flow
     *
     * @return FlowStatusInterface
     */
    public function getFlowStatus()
    {
        return $this->flowStatus;
    }

    /**
     * Break the flow's execution, conceptually similar to breaking
     * a regular loop
     *
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @return $this
     */
    public function breakFlow(InterrupterInterface $flowInterrupt = null)
    {
        return $this->interruptFlow(InterrupterInterface::TYPE_BREAK, $flowInterrupt);
    }

    /**
     * Continue the flow's execution, conceptually similar to continuing
     * a regular loop
     *
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @return $this
     */
    public function continueFlow(InterrupterInterface $flowInterrupt = null)
    {
        return $this->interruptFlow(InterrupterInterface::TYPE_CONTINUE, $flowInterrupt);
    }

    /**
     * @param string                    $interruptType
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function interruptFlow($interruptType, InterrupterInterface $flowInterrupt = null)
    {
        switch ($interruptType) {
            case InterrupterInterface::TYPE_CONTINUE:
                $this->continue = true;
                $this->flowMap->incrementFlow('num_continue');
                break;
            case InterrupterInterface::TYPE_BREAK:
                $this->flowStatus = new FlowStatus(FlowStatus::FLOW_DIRTY);
                $this->break      = true;
                $this->flowMap->incrementFlow('num_break');
                break;
            default:
                throw new NodalFlowException('FlowInterrupt Type missing');
        }

        if ($flowInterrupt) {
            $flowInterrupt->setType($interruptType)->propagate($this);
        }

        return $this;
    }

    /**
     * @param NodeInterface $node
     *
     * @return bool
     */
    protected function interruptNode(NodeInterface $node)
    {
        // if we have an interruptNodeId, bubble up until we match a node
        // else stop propagation
        return $this->interruptNodeId ? $this->interruptNodeId !== $node->getNodeHash() : false;
    }

    /**
     * Triggered just before the flow starts
     *
     * @return $this
     */
    protected function flowStart()
    {
        $this->flowMap->incrementFlow('num_exec')->flowStart();
        $this->triggerCallback(static::FLOW_START);

        // flow is started
        $this->flowStatus = new FlowStatus(FlowStatus::FLOW_RUNNING);

        return $this;
    }

    /**
     * Triggered right after the flow stops
     *
     * @return $this
     */
    protected function flowEnd()
    {
        $this->flowMap->flowEnd();

        $this->triggerCallback($this->flowStatus->isException() ? static::FLOW_FAIL : static::FLOW_SUCCESS);

        return $this;
    }

    /**
     * Return a simple nonce, fully valid within any flow
     *
     * @return int
     */
    protected function getNonce()
    {
        return self::$nonce++;
    }

    /**
     * Recurse over nodes which may as well be Flows and
     * Traversable ...
     * Welcome to the abysses of recursion or iter-recursion ^^
     *
     * `recurse` perform kind of an hybrid recursion as the
     * Flow is effectively iterating and recurring over its
     * Nodes, which may as well be seen as over itself
     *
     * Iterating tends to limit the amount of recursion levels:
     * recursion is only triggered when executing a Traversable
     * Node's downstream Nodes while every consecutive exec
     * Nodes are executed within a while loop.
     * And recursion keeps the size of the recursion context
     * to a minimum as pretty much everything is done by the
     * iterating instance
     *
     * @param mixed $param
     * @param int   $nodeIdx
     *
     * @return mixed the last value returned by the last
     *               returning value Node in the flow
     */
    protected function recurse($param = null, $nodeIdx = 0)
    {
        while ($nodeIdx <= $this->lastIdx) {
            $node      = $this->nodes[$nodeIdx];
            $nodeHash  = $node->getNodeHash();
            $returnVal = $node->isReturningVal();

            if ($node->isTraversable()) {
                foreach ($node->getTraversable($param) as $value) {
                    if ($returnVal) {
                        // pass current $value as next param
                        $param = $value;
                    }

                    $this->flowMap->incrementNode($nodeHash, 'num_iterate')->incrementFlow('num_iterate');
                    ++$this->numIterate;
                    if (!($this->numIterate % $this->progressMod)) {
                        $this->triggerCallback(static::FLOW_PROGRESS, $node);
                    }

                    $param = $this->recurse($param, $nodeIdx + 1);
                    if ($this->continue) {
                        if ($this->continue = $this->interruptNode($node)) {
                            // since we want to bubble the continue upstream
                            // we break here waiting for next $param if any
                            $this->flowMap->incrementNode($nodeHash, 'num_break');
                            break;
                        }

                        // we drop one iteration
                        $this->flowMap->incrementNode($nodeHash, 'num_continue');
                        continue;
                    }

                    if ($this->break) {
                        // we drop all subsequent iterations
                        $this->flowMap->incrementNode($nodeHash, 'num_break');
                        $this->break = $this->interruptNode($node);
                        break;
                    }
                }

                // we reached the end of this Traversable and executed all its downstream Nodes
                $this->flowMap->incrementNode($nodeHash, 'num_exec');

                return $param;
            }

            $value = $node->exec($param);
            $this->flowMap->incrementNode($nodeHash, 'num_exec');

            if ($this->continue) {
                $this->flowMap->incrementNode($nodeHash, 'num_continue');
                // a continue does not need to bubble up unless
                // it specifically targets a node in this flow
                // or targets an upstream flow
                $this->continue = $this->interruptNode($node);

                return $param;
            }

            if ($this->break) {
                $this->flowMap->incrementNode($nodeHash, 'num_break');
                // a break always need to bubble up to the first upstream Traversable if any
                return $param;
            }

            if ($returnVal) {
                // pass current $value as next param
                $param = $value;
            }

            ++$nodeIdx;
        }

        // we reached the end of this recursion
        return $param;
    }

    /**
     * KISS helper to trigger Callback slots
     *
     * @param string             $which
     * @param null|NodeInterface $node
     *
     * @return $this
     */
    protected function triggerCallback($which, NodeInterface $node = null)
    {
        if (null !== $this->callBack) {
            $this->callBack->$which($this, $node);
        }

        return $this;
    }
}
