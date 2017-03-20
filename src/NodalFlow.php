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
    public $branchId;

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
    protected $numActions = 0;

    /**
     * @var CallbackInterface
     */
    protected $callBack;

    /**
     * Progress modulo to apply
     * Set to x if you want to trigger
     * progress every x action in flow
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
        'start'           => null,
        'end'             => null,
        'duration'        => null,
        'memory'          => null,
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
     * @var int
     */
    private static $nonce = 0;

    public function __construct()
    {
        $this->branchId = $this->uniqId();
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
        $this->nodeMap[$nodeHash]    = array_replace($this->nodeMapDefault, [
            'class'    => get_class($node),
            'branchId' => $this->branchId,
            'hash'     => $nodeHash,
            'index'    => $this->nodeIdx,
        ]);

        // regsiter references to nodeStats to increment faster
        foreach ($this->nodeStatsDefault as $incrementKey => $ignore) {
            $this->nodeStats[$this->nodeIdx][$incrementKey] = &$this->nodeMap[$nodeHash][$incrementKey];
        }

        // expose node hash to also use nodeStat as a reverse lookup table
        $this->nodeStats[$this->nodeIdx]['hash'] = $nodeHash;

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
     * spl_object_hash seems tempting here, but we
     * want to garanty both this and the position
     * and two object may be reused in a branch at
     * a different location
     *
     * @param object $object
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
            $this->flowEnd(true);

            return $result;
        } catch (\Exception $e) {
            $this->flowEnd(false);
            throw $e;
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

        $durationStr = \trim($durationStr, ' ');

        $result['durationStr'] = $durationStr;

        return $result;
    }

    /**
     * @return $this
     */
    public function resetNodeStats()
    {
        foreach ($this->nodeStats as &$nodeStat) {
            $nodeStat = array_replace($nodeStat, $this->nodeStatDefault);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
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
        return $this->nodeMap;
    }

    /**
     * @return array
     */
    public function getNodeStats()
    {
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
     * @return $this
     */
    public function breakFlow()
    {
        $this->break = true;

        return $this;
    }

    /**
     * @param bool $continue
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

        return $this;
    }

    /**
     * Triggered right after the flow stops
     *
     * @param bool $success
     *
     * @return $this
     */
    protected function flowEnd($success)
    {
        $this->stats['end']                                     = \microtime(true);
        $this->stats['invocations'][$this->numExec]['end']      = $this->stats['end'];
        $this->stats['duration']                                = $this->stats['end'] - $this->stats['start'];
        $this->stats['invocations'][$this->numExec]['duration'] = $this->stats['duration'];
        $this->stats['memory']                                  = \memory_get_peak_usage(true) / 1048576;
        $this->stats['invocations'][$this->numExec]['memory']   = $this->stats['memory'];

        $this->triggerCallback($success ? static::FLOW_SUCCESS : static::FLOW_FAIL);

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
     * @return mixed, the last value returned in the chain
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
                    ++$this->numActions;
                    if (!($this->numActions % $this->progressMod)) {
                        $this->triggerCallback(static::FLOW_PROGRESS, $node);
                    }

                    // here this means that if a deeper child does return something
                    // its result will buble up to the first node as param in case
                    // one of the previous node is a Traversable
                    // It's of course up to each node to decide what to do with the
                    // input param.
                    $param = $this->recurse($param, $nodeIdx + 1);
                    if ($this->continue) {
                        // we drop one action
                        // could be because there is no matching join record from somewhere
                        $this->continue = false;
                        continue;
                    }

                    if ($this->break) {
                        // we drop all subsequent actions
                        break;
                    }
                }
                // we reached the end of the flow
                ++$nodeStat['num_exec'];

                return $param;
            }

            $value = $node->exec($param);
            ++$nodeStat['num_exec'];
            ++$this->numActions;
            if ($this->continue || $this->break) {
                return $param;
            }

            if (!($this->numActions % $this->progressMod)) {
                $this->triggerCallback(static::FLOW_PROGRESS, $node);
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
     * @param type          $which
     * @param NodeInterface $node
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
