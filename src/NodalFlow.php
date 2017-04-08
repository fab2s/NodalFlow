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
use fab2s\NodalFlow\Flows\FlowStatus;
use fab2s\NodalFlow\Flows\FlowStatusInterface;
use fab2s\NodalFlow\Nodes\AggregateNodeInterface;
use fab2s\NodalFlow\Nodes\BranchNode;
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
     * @var string
     */
    protected $flowId;

    /**
     * @var array
     */
    protected $nodes = [];

    /**
     * @var int
     */
    protected $nodeIdx = 0;

    /**
     * @var int
     */
    protected $lastIdx = 0;

    /**
     * @var int
     */
    protected $nodeCount = 0;

    /**
     * @var int
     */
    protected $numIterate = 0;

    /**
     * @var CallbackInterface
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
     * @var array
     */
    protected $nodeMapDefault = [
        'class'        => null,
        'branchId'     => null,
        'hash'         => null,
        'index'        => 0,
        'num_exec'     => 0,
        'num_iterate'  => 0,
    ];

    /**
     * @var array
     */
    protected $nodeStatsDefault = [
        'num_exec'     => 0,
        'num_iterate'  => 0,
    ];

    /**
     * @var array
     */
    protected $nodeStats = [];

    /**
     * @var array
     */
    protected $objectMap = [];

    /**
     * @var array
     */
    protected $nodeMap = [];

    /**
     * @var array
     */
    protected $statsDefault = [
        'start'    => null,
        'end'      => null,
        'duration' => null,
        'mib'      => null,
    ];

    /**
     * @var array
     */
    protected $stats = [
        'invocations' => [],
    ];

    /**
     * @var int
     */
    protected $numExec = 0;

    /**
     * @var bool
     */
    protected $continue = false;

    /**
     * @var bool
     */
    protected $break = false;

    /**
     * @var FlowStatusInterface
     */
    protected $flowStatus;

    /**
     * @var int
     */
    private static $nonce = 0;

    public function __construct()
    {
        $this->flowId = $this->uniqId();
        $this->stats += $this->statsDefault;
    }

    /**
     * @param NodeInterface $node
     *
     * @return $this
     */
    public function add(NodeInterface $node)
    {
        $nodeHash = $this->objectHash($node);
        $node->setCarrier($this)->setNodeHash($nodeHash);

        $this->nodes[$this->nodeIdx] = $node;
        $this->nodeMap[$nodeHash]    = \array_replace($this->nodeMapDefault, [
            'class'    => \get_class($node),
            'branchId' => $this->flowId,
            'hash'     => $nodeHash,
            'index'    => $this->nodeIdx,
        ]);

        // register references to nodeStats to increment faster
        // nodeStats can also be used as reverse lookup table
        $this->nodeStats[$this->nodeIdx] = &$this->nodeMap[$nodeHash];

        ++$this->nodeIdx;

        return $this;
    }

    /**
     * @param callable $payload
     * @param mixed    $isAReturningVal
     * @param mixed    $isATraversable
     *
     * @return $this
     */
    public function addPayload(callable $payload, $isAReturningVal, $isATraversable = false)
    {
        $node = PayloadNodeFactory::create($payload, $isAReturningVal, $isATraversable);

        parent::add($node);

        return $this;
    }

    /**
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
     * We need to uniquely identify each flow in constructor
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
     * @param object $object
     *
     * @return string
     */
    public function objectHash($object)
    {
        return \sha1(\spl_object_hash($object));
    }

    /**
     * @param null|mixed $param
     *
     * @return mixed the last result of the
     *               last returing value node
     */
    public function exec($param = null)
    {
        try {
            $result = $this->rewind()
                    ->flowStart()
                    ->recurse($param);
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
     * @param float $seconds
     *
     * @return array
     */
    public function duration($seconds)
    {
        $result = [
            'hour'     => (int) \floor($seconds / 3600),
            'min'      => (int) \floor(($seconds / 60) % 60),
            'sec'      => $seconds % 60,
            'ms'       => (int) \round(\fmod($seconds, 1) * 1000),
        ];

        $durationStr = '';
        foreach ($result as $unit => $value) {
            if (!empty($value)) {
                $durationStr .= $value . "$unit ";
            }
        }

        $result['durationStr'] = \trim($durationStr);

        return $result;
    }

    /**
     * @return $this
     */
    public function resetNodeStats()
    {
        foreach ($this->nodeStats as &$nodeStat) {
            $nodeStat = \array_replace($nodeStat, $this->nodeStatsDefault);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getStats()
    {
        foreach ($this->nodes as $node) {
            if (\is_a($node, BranchNode::class)) {
                $this->stats['branches'][$node->getPayload()->getFlowId()] = $node->getPayload()->getStats();
            }
        }

        return $this->stats;
    }

    /**
     * @return string
     */
    public function getFlowId()
    {
        return $this->flowId;
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @return array
     */
    public function getNodeMap()
    {
        foreach ($this->nodes as $node) {
            if (\is_a($node, BranchNode::class)) {
                $this->nodeMap[$node->getNodeHash()]['nodes'] = $node->getPayload()->getNodeMap();
                continue;
            }

            if ($node instanceof AggregateNodeInterface) {
                foreach ($node->getNodeCollection() as $aggregatedNode) {
                    $this->nodeMap[$node->getNodeHash()]['nodes'][$aggregatedNode->getNodeHash()] = [
                        'class' => \get_class($aggregatedNode),
                        'hash'  => $aggregatedNode->getNodeHash(),
                    ];
                }
                continue;
            }
        }

        return $this->nodeMap;
    }

    /**
     * @return array
     */
    public function getNodeStats()
    {
        foreach ($this->nodes as $nodeIdx => $node) {
            if (\is_a($node, BranchNode::class)) {
                $this->nodeStats[$nodeIdx]['nodes'] = $node->getPayload()->getNodeStats();
            }
        }

        return $this->nodeStats;
    }

    /**
     * Rewinds the Flow
     *
     * @return $this
     */
    public function rewind()
    {
        $this->nodeCount = count($this->nodes);
        $this->lastIdx   = $this->nodeCount - 1;
        $this->nodeIdx   = 0;

        return $this;
    }

    /**
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
     * @return $this
     */
    public function breakFlow()
    {
        $this->flowStatus = new FlowStatus(FlowStatus::FLOW_DIRTY);

        $this->break = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function continueFlow()
    {
        $this->continue = true;

        return $this;
    }

    /**
     * Triggered just before the flow starts
     *
     * @return $this
     */
    protected function flowStart()
    {
        ++$this->numExec;
        $this->triggerCallback(static::FLOW_START);
        $this->stats['start']                                = \microtime(true);
        $this->stats['invocations'][$this->numExec]['start'] = $this->stats['start'];
        // each start is clean
        $this->flowStatus = new FlowStatus(FlowStatus::FLOW_CLEAN);

        return $this;
    }

    /**
     * Triggered right after the flow stops
     *
     * @return $this
     */
    protected function flowEnd()
    {
        $this->stats['end']                                     = \microtime(true);
        $this->stats['invocations'][$this->numExec]['end']      = $this->stats['end'];
        $this->stats['duration']                                = $this->stats['end'] - $this->stats['start'];
        $this->stats['invocations'][$this->numExec]['duration'] = $this->stats['duration'];
        $this->stats['mib']                                     = \memory_get_peak_usage(true) / 1048576;
        $this->stats['invocations'][$this->numExec]['mib']      = $this->stats['mib'];

        $this->triggerCallback($this->flowStatus->isException() ? static::FLOW_FAIL : static::FLOW_SUCCESS);

        return $this;
    }

    /**
     * @return int
     */
    protected function getNonce()
    {
        return self::$nonce++;
    }

    /**
     * Recurse over flows and nodes which may
     * as well be Traversable ...
     * Welcome to the abysses of recursion ^^
     *
     * @param mixed $param
     * @param int   $nodeIdx
     *
     * @return mixed the last value returned by the last
     *               returning value Node in the flow
     */
    protected function recurse($param = null, $nodeIdx = 0)
    {
        // the while construct here saves as many recursion depth
        // as there are exec nodes in the flow
        while ($nodeIdx <= $this->lastIdx) {
            $node      = $this->nodes[$nodeIdx];
            $nodeStat  = &$this->nodeStats[$nodeIdx];
            $returnVal = $node->isReturningVal();

            if ($node->isTraversable()) {
                foreach ($node->getTraversable($param) as $value) {
                    if ($returnVal) {
                        // pass current $value as next param
                        // else keep last $param
                        $param = $value;
                    }

                    ++$nodeStat['num_iterate'];
                    ++$this->numIterate;
                    if (!($this->numIterate % $this->progressMod)) {
                        $this->triggerCallback(static::FLOW_PROGRESS, $node);
                    }

                    // here this means that if a deeper child does return something
                    // its result will buble up to the first node as param in case
                    // one of the previous node is a Traversable
                    // It's of course up to each node to decide what to do with the
                    // input param.
                    $param = $this->recurse($param, $nodeIdx + 1);
                    if ($this->continue) {
                        // we drop one iteration
                        // could be because there is no matching join record from somewhere
                        $this->continue = false;
                        continue;
                    }

                    if ($this->break) {
                        // we drop all subsequent iterations
                        break;
                    }
                }

                // we reached the end of the flow
                ++$nodeStat['num_exec'];

                return $param;
            }

            $value = $node->exec($param);
            ++$nodeStat['num_exec'];

            if ($this->continue || $this->break) {
                return $param;
            }

            if ($returnVal) {
                // pass current $value as next param
                $param = $value;
            }

            ++$nodeIdx;
        }

        // we reached the end of the flow
        return $param;
    }

    /**
     * KISS helper
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
