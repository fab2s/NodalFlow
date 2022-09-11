<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow;

use Exception;
use fab2s\NodalFlow\Events\FlowEventInterface;
use fab2s\NodalFlow\Flows\FlowAbstract;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Flows\FlowMap;
use fab2s\NodalFlow\Flows\FlowRegistry;
use fab2s\NodalFlow\Flows\FlowStatus;
use fab2s\NodalFlow\Nodes\BranchNodeInterface;
use fab2s\NodalFlow\Nodes\ExecNodeInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;
use fab2s\NodalFlow\Nodes\TraversableNodeInterface;

/**
 * Class NodalFlow
 */
class NodalFlow extends FlowAbstract
{
    /**
     * @var array
     */
    protected $flowIncrements = [];

    /**
     * The number of Node in this Flow
     *
     * @var int
     */
    protected $nodeCount = 0;

    /**
     * Instantiate a Flow
     *
     * @throws NodalFlowException
     */
    public function __construct()
    {
        $this->flowMap     = new FlowMap($this, $this->flowIncrements);
        $this->registry    = new FlowRegistry;
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
    public function add(NodeInterface $node): FlowInterface
    {
        if ($node instanceof BranchNodeInterface) {
            // this node is a branch
            $childFlow = $node->getPayload();
            $this->branchFlowCheck($childFlow);
            $childFlow->setParent($this);
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
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function addPayload(callable $payload, bool $isAReturningVal, bool $isATraversable = false): FlowInterface
    {
        $node = PayloadNodeFactory::create($payload, $isAReturningVal, $isATraversable);

        $this->add($node);

        return $this;
    }

    /**
     * Replaces a node with another one
     *
     * @param int           $nodeIdx
     * @param NodeInterface $node
     *
     * @throws NodalFlowException
     *
     * @return static
     */
    public function replace(int $nodeIdx, NodeInterface $node): FlowInterface
    {
        if (!isset($this->nodes[$nodeIdx])) {
            throw new NodalFlowException('Argument 1 should be a valid index in nodes', 1, null, [
                'nodeIdx' => $nodeIdx,
                'node'    => get_class($node),
            ]);
        }

        $node->setCarrier($this);
        $this->nodes[$nodeIdx] = $node;
        $this->flowMap->register($node, $nodeIdx, true);

        return $this;
    }

    /**
     * @param string|null $nodeId
     * @param mixed|null  $param
     *
     * @throws Exception
     * @throws NodalFlowException
     *
     * @return mixed
     */
    public function sendTo(string $nodeId = null, $param = null)
    {
        $nodeIndex = 0;
        if ($nodeId !== null) {
            if (!($nodeIndex = $this->flowMap->getNodeIndex($nodeId))) {
                throw new NodalFlowException('Cannot sendTo without valid Node target', 1, null, [
                    'flowId' => $this->getId(),
                    'nodeId' => $nodeId,
                ]);
            }
        }

        return $this->rewind()->recurse($param, $nodeIndex);
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
        } catch (Exception $e) {
            $this->flowStatus = new FlowStatus(FlowStatus::FLOW_EXCEPTION, $e);
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
     * Rewinds the Flow
     *
     * @return $this
     */
    public function rewind(): FlowInterface
    {
        $this->nodeCount       = count($this->nodes);
        $this->lastIdx         = $this->nodeCount - 1;
        $this->break           = false;
        $this->continue        = false;
        $this->interruptNodeId = null;

        return $this;
    }

    /**
     * @param FlowInterface $flow
     *
     * @throws NodalFlowException
     */
    protected function branchFlowCheck(FlowInterface $flow)
    {
        if (
            // this flow has parent already
            $flow->hasParent() ||
            // adding root flow in itself
            $this->getRootFlow($flow)->getId() === $this->getRootFlow($this)->getId()
        ) {
            throw new NodalFlowException('Cannot reuse Flow within Branches', 1, null, [
                'flowId'             => $this->getId(),
                'BranchFlowId'       => $flow->getId(),
                'BranchFlowParentId' => $flow->hasParent() ? $flow->getParent()->getId() : null,
            ]);
        }
    }

    /**
     * Triggered just before the flow starts
     *
     *
     * @return $this
     */
    protected function flowStart(): self
    {
        $this->flowMap->incrementFlow('num_exec')->flowStart();
        $this->listActiveEvent(!$this->hasParent())->triggerEvent(FlowEventInterface::FLOW_START);
        // flow started status kicks in after Event start to hint eventual children
        // this way, root flow is only running when a record hits a branch
        // and triggers a child flow flowStart() call
        $this->flowStatus = new FlowStatus(FlowStatus::FLOW_RUNNING);

        return $this;
    }

    /**
     * Triggered right after the flow stops
     *
     * @return $this
     */
    protected function flowEnd(): self
    {
        $this->flowMap->flowEnd();
        $eventName = FlowEventInterface::FLOW_SUCCESS;
        $node      = null;
        if ($this->flowStatus->isException()) {
            $eventName = FlowEventInterface::FLOW_FAIL;
            $node      = $this->nodes[$this->nodeIdx];
        }

        // restore nodeIdx
        $this->nodeIdx = $this->lastIdx + 1;
        $this->triggerEvent($eventName, $node);

        return $this;
    }

    /**
     * Recurse over nodes which may as well be Flows and Traversable ...
     * Welcome to the abysses of recursion or iter-recursion ^^
     *
     * `recurse` perform kind of an hybrid recursion as the Flow
     * is effectively iterating and recurring over its Nodes,
     * which may as well be seen as over itself
     *
     * Iterating tends to limit the amount of recursion levels:
     * recursion is only triggered when executing a Traversable
     * Node's downstream Nodes while every consecutive exec
     * Nodes are executed within the while loop.
     * The size of the recursion context is kept to a minimum
     * as pretty much everything is done by the iterating instance
     *
     * @param mixed $param
     * @param int   $nodeIdx
     *
     * @return mixed the last value returned by the last
     *               returning value Node in the flow
     */
    protected function recurse($param = null, int $nodeIdx = 0)
    {
        while ($nodeIdx <= $this->lastIdx) {
            $node          = $this->nodes[$nodeIdx];
            $this->nodeIdx = $nodeIdx;
            $nodeStats     = &$this->flowMap->getNodeStat($node->getId());
            $returnVal     = $node->isReturningVal();

            if ($node->isTraversable()) {
                /** @var TraversableNodeInterface $node */
                foreach ($node->getTraversable($param) as $value) {
                    if ($returnVal) {
                        // pass current $value as next param
                        $param = $value;
                    }

                    ++$nodeStats['num_iterate'];
                    if (!($nodeStats['num_iterate'] % $this->progressMod)) {
                        $this->triggerEvent(FlowEventInterface::FLOW_PROGRESS, $node);
                    }

                    $param = $this->recurse($param, $nodeIdx + 1);
                    if ($this->continue) {
                        if ($this->continue = $this->interruptNode($node)) {
                            // since we want to bubble the continue upstream
                            // we break here waiting for next $param if any
                            ++$nodeStats['num_break'];
                            break;
                        }

                        // we drop one iteration
                        ++$nodeStats['num_continue'];
                        continue;
                    }

                    if ($this->break) {
                        // we drop all subsequent iterations
                        ++$nodeStats['num_break'];
                        $this->break = $this->interruptNode($node);
                        break;
                    }
                }

                // we reached the end of this Traversable and executed all its downstream Nodes
                ++$nodeStats['num_exec'];

                return $param;
            }

            /** @var ExecNodeInterface $node */
            $value = $node->exec($param);
            ++$nodeStats['num_exec'];

            if ($this->continue) {
                ++$nodeStats['num_continue'];
                // a continue does not need to bubble up unless
                // it specifically targets a node in this flow
                // or targets an upstream flow
                $this->continue = $this->interruptNode($node);

                return $param;
            }

            if ($this->break) {
                ++$nodeStats['num_break'];
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
}
