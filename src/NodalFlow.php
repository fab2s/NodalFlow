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
     * This Flow id
     *
     * @var string
     */
    protected $flowId;

    /**
     * The underlying node structure
     *
     * @var array
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
     * The default Node Map values
     *
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
     * The default Node stats values
     *
     * @var array
     */
    protected $nodeStatsDefault = [
        'num_exec'     => 0,
        'num_iterate'  => 0,
    ];

    /**
     * Node stats values
     *
     * @var array
     */
    protected $nodeStats = [];

    /**
     * The object map, used to enforce object unicity within the Flow
     *
     * @var array
     */
    protected $objectMap = [];

    /**
     * The Node Map
     *
     * @var array
     */
    protected $nodeMap = [];

    /**
     * The Flow stats default values
     *
     * @var array
     */
    protected $statsDefault = [
        'start'    => null,
        'end'      => null,
        'duration' => null,
        'mib'      => null,
    ];

    /**
     * The Flow Stats
     *
     * @var array
     */
    protected $stats = [
        'invocations' => [],
    ];

    /**
     * Number of exec calls in thhis Flow
     *
     * @var int
     */
    protected $numExec = 0;

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
        $this->flowId = $this->uniqId();
        $this->stats += $this->statsDefault;
    }

    /**
     * Adds a Node to the flow
     *
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

        parent::add($node);

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
     * Generates a truely unique id for the Flow context
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
     * Generate a friendly (read humanly distinguishable) object hash
     *
     * @param object $object
     *
     * @return string
     */
    public function objectHash($object)
    {
        return \sha1(\spl_object_hash($object));
    }

    /**
     * Execute the flow
     *
     * @param null|mixed $param The eventual init argument to the first node
     *                          or, in case of a branch, the last relevant
     *                          argument from upstream Flow
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
     * Computes a human readable duration string from floating seconds
     *
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
     * Resets Nodes stats, used prior to Flow's re-exec
     *
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
     * Get the stats array with latest Node stats
     *
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
     * Return the Flow id as set during instantiation
     *
     * @return string
     */
    public function getFlowId()
    {
        return $this->flowId;
    }

    /**
     * Get the Node array
     *
     * @return array
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Generate Node Map
     *
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
     * Get the Node stats
     *
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
     * Break the flow's execution, conceptuially similar to breaking
     * a regular loop
     *
     * @return $this
     */
    public function breakFlow()
    {
        $this->flowStatus = new FlowStatus(FlowStatus::FLOW_DIRTY);

        $this->break = true;

        return $this;
    }

    /**
     * Continue the flow's execution, conceptuially similar to continuing
     * a regular loop
     *
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
     * Return a simple nonce, fully valid within each flow
     *
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
