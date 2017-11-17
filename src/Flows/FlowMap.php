<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\AggregateNodeInterface;
use fab2s\NodalFlow\Nodes\BranchNodeInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * class FlowMap
 * Do not implement Serializable interface on purpose
 *
 * @SEE https://externals.io/message/98834#98834
 */
class FlowMap implements FlowMapInterface
{
    /**
     * Flow map
     *
     * @var array
     */
    protected $nodeMap = [];

    /**
     * @var NodeInterface[]
     */
    protected $reverseMap = [];

    /**
     * The default Node Map values
     *
     * @var array
     */
    protected $nodeMapDefault = [
        'class'           => null,
        'flowId'          => null,
        'hash'            => null,
        'index'           => null,
        'isATraversable'  => null,
        'isAReturningVal' => null,
        'isAFlow'         => null,
    ];

    /**
     * The default Node stats values
     *
     * @var array
     */
    protected $nodeIncrements = [
        'num_exec'     => 0,
        'num_iterate'  => 0,
        'num_break'    => 0,
        'num_continue' => 0,
    ];

    /**
     * The Flow map default values
     *
     * @var array
     */
    protected $flowMapDefault = [
        'class'    => null,
        'id'       => null,
        'start'    => null,
        'end'      => null,
        'elapsed'  => null,
        'duration' => null,
        'mib'      => null,
    ];

    /**
     * @var array
     */
    protected $incrementTotals = [];

    /**
     * @var array
     */
    protected $flowIncrements;

    /**
     * @var array
     */
    protected $flowStats;

    /**
     * @var array
     */
    protected static $registry = [];

    /**
     * @var FlowInterface
     */
    protected $flow;

    /**
     * @var string
     */
    protected $flowId;

    /**
     * Instantiate a Flow Status
     *
     * @param FlowInterface $flow
     * @param array         $flowIncrements
     */
    public function __construct(FlowInterface $flow, array $flowIncrements = [])
    {
        $this->flow             = $flow;
        $this->flowId           = $this->flow->getId();
        $this->initDefaults()->setRefs()->setFlowIncrement($flowIncrements);
    }

    /**
     * The goal is to offer a cheap global state that supports
     * sending a parameter to whatever node in any live Flows.
     * We also need some kind of specific setup for each Flows
     * and Nodes (custom increments etc).
     *
     * The idea is to share a registry among all instances
     * without :
     *  - singleton: would only be useful to store the global state
     *      not the specific setup
     *  - breaking references: we want to be able to increment fast
     *      thus multiple entries at once by reference
     *  - DI: it would be a bit too much of code for the purpose and
     *      would come with the same limitation as the singleton
     *  - Complex and redundant merge strategies: registering a Node
     *      would then require to look up for ascendant and descendant
     *      Flows each time.
     *  - Breaking serialization: Playing with static and references
     *      require some attention. The matter is dealt with transparently
     *      as the static registry acts like a global cache feed with each
     *      instance data upon un-serialization.
     *
     * By using a static, we make sure all instances share the same
     * registry at all time in the simplest way.
     * Each FlowMap instance keeps dynamic reference to the portion
     * of the registry that belongs to the Flow holding the instance.
     * Upon Serialization, every FlowMap instance will thus only store
     * a portion of the global state, that is relevant to its carrying
     * Flow.
     *
     * Upon un-serialization, the global state is restored bit by bit
     * as each FlowMap gets un-serialized.
     * This leverage one edge case, that is, if we un-serialize two
     * time the same Flow (could only be members of different Flows).
     * This is not very likely as Flow id are immutable and unique,
     * but it could occur.
     * This situation is currently dealt with by throwing an exception,
     * as it would introduce the need to deal with distinct instances
     * with the same Id. And generating new ids is not simple either
     * as their whole point is to stay the same for others to know
     * who's who.
     * I find it a small trade of though, as un-serializing twice
     * the same string seems buggy and reusing the same Flow without
     * cloning is not such a good idea either (nor required).
     *
     * The use of a static registry also bring a somehow exotic feature :
     * The ability to target any Node in any Flow is not limited to the
     * flow within the same root Flow. You can actually target any Flow
     * in the current process. Using reference implementation would limit
     * the sharing to the root FLow members.
     * I don't know if this can be actually useful, but I don't think
     * it's such a big deal either.
     *
     * If you don't feel like doing this at home, I completely
     * understand, I'd be very happy to hear about a better and
     * more efficient way
     */
    public function __wakeup()
    {
        if (isset(static::$registry[$this->flowId])) {
            throw new NodalFlowException('Un-serializing a Flow when it is already instantiated is not supported', 1, null, [
                'class' => get_class($this->flow),
                'id'    => $this->flowId,
            ]);
        }

        $this->setRefs();
    }

    /**
     * @param NodeInterface $node
     * @param int           $index
     *
     * @throws NodalFlowException
     */
    public function register(NodeInterface $node, $index)
    {
        $this->enforceUniqueness($node);
        $nodeId                 = $node->getId();
        $this->nodeMap[$nodeId] = array_replace($this->nodeMapDefault, [
            'class'           => get_class($node),
            'flowId'          => $this->flowId,
            'hash'            => $nodeId,
            'index'           => $index,
            'isATraversable'  => $node->isTraversable(),
            'isAReturningVal' => $node->isReturningVal(),
            'isAFlow'         => $node->isFlow(),
        ], $this->nodeIncrements);

        $this->setNodeIncrement($node);

        if (isset($this->reverseMap[$index])) {
            // replacing a node, maintain nodeMap accordingly
            unset($this->nodeMap[$this->reverseMap[$index]->getId()]);
        }

        $this->reverseMap[$index] = $node;
    }

    /**
     * Triggered right before the flow starts
     *
     * @return $this
     */
    public function flowStart()
    {
        $this->flowStats['start'] = microtime(true);

        return $this;
    }

    /**
     * Triggered right after the flow stops
     *
     * @return $this
     */
    public function flowEnd()
    {
        $this->flowStats['end']     = microtime(true);
        $this->flowStats['mib']     = memory_get_peak_usage(true) / 1048576;
        $this->flowStats['elapsed'] = $this->flowStats['end'] - $this->flowStats['start'];

        $this->flowStats = array_replace($this->flowStats, $this->duration($this->flowStats['elapsed']));

        return $this;
    }

    /**
     * Let's be fast at incrementing while we are at it
     *
     * @param string $nodeHash
     *
     * @return array
     */
    public function &getNodeStat($nodeHash)
    {
        return $this->nodeMap[$nodeHash];
    }

    /**
     * Get/Generate Node Map
     *
     * @throws NodalFlowException
     *
     * @return array
     */
    public function getNodeMap()
    {
        foreach ($this->flow->getNodes() as $node) {
            $nodeId = $node->getId();
            if ($node instanceof BranchNodeInterface) {
                $this->nodeMap[$nodeId]['nodes'] = $node->getPayload()->getNodeMap();
                continue;
            }

            if ($node instanceof AggregateNodeInterface) {
                foreach ($node->getNodeCollection() as $aggregatedNode) {
                    $this->nodeMap[$nodeId]['nodes'][$aggregatedNode->getId()] = array_replace($this->nodeMapDefault, [
                        'class'  => get_class($aggregatedNode),
                        'flowId' => $this->flowId,
                        'hash'   => $aggregatedNode->getId(),
                    ]);
                }
                continue;
            }
        }

        return $this->nodeMap;
    }

    /**
     * Get the latest Node stats
     *
     * @return array
     */
    public function getStats()
    {
        foreach ($this->flow->getNodes() as $node) {
            $nodeMap = $this->nodeMap[$node->getId()];
            foreach ($this->incrementTotals as $srcKey => $totalKey) {
                if (isset($nodeMap[$srcKey])) {
                    $this->flowStats[$totalKey] += $nodeMap[$srcKey];
                }
            }

            if ($node instanceof BranchNodeInterface) {
                $childFlowId                               = $node->getPayload()->getId();
                $this->flowStats['branches'][$childFlowId] = $node->getPayload()->getStats();
                foreach ($this->incrementTotals as $srcKey => $totalKey) {
                    if (isset($this->flowStats['branches'][$childFlowId][$totalKey])) {
                        $this->flowStats[$totalKey] += $this->flowStats['branches'][$childFlowId][$totalKey];
                    }
                }
            }
        }

        return $this->flowStats;
    }

    /**
     * @param string $nodeHash
     * @param string $key
     *
     * @return $this
     */
    public function incrementNode($nodeHash, $key)
    {
        ++$this->nodeMap[$nodeHash][$key];

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function incrementFlow($key)
    {
        ++$this->flowStats[$key];

        return $this;
    }

    /**
     * Resets Nodes stats, can be used prior to Flow's re-exec
     *
     * @return $this
     */
    public function resetNodeStats()
    {
        foreach ($this->nodeMap as &$nodeStat) {
            foreach ($this->nodeIncrements as $key => $value) {
                if (isset($nodeStat[$key])) {
                    $nodeStat[$key] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Computes a human readable duration string from floating seconds
     *
     * @param float $seconds
     *
     * @return array<string,integer|string>
     */
    public function duration($seconds)
    {
        $result = [
            'hour'     => (int) floor($seconds / 3600),
            'min'      => (int) floor(($seconds / 60) % 60),
            'sec'      => $seconds % 60,
            'ms'       => (int) round(\fmod($seconds, 1) * 1000),
        ];

        $duration = '';
        foreach ($result as $unit => $value) {
            if (!empty($value) || $unit === 'ms') {
                $duration .= $value . "$unit ";
            }
        }

        $result['duration'] = trim($duration);

        return $result;
    }

    /**
     * @return $this
     */
    protected function setRefs()
    {
        static::$registry[$this->flowId]['flowStats'] = &$this->flowStats;
        static::$registry[$this->flowId]['nodeStats'] = &$this->nodeMap;
        static::$registry[$this->flowId]['flow']      = $this->flow;
        static::$registry[$this->flowId]['nodes']     = &$this->reverseMap;

        return $this;
    }

    /**
     * @return $this
     */
    protected function initDefaults()
    {
        $this->flowIncrements = $this->nodeIncrements;
        foreach (array_keys($this->flowIncrements) as $key) {
            $totalKey                        = $key . '_total';
            $this->incrementTotals[$key]     = $totalKey;
            $this->flowIncrements[$totalKey] = 0;
        }

        $this->flowMapDefault = array_replace($this->flowMapDefault, $this->flowIncrements, [
            'class' => get_class($this->flow),
            'id'    => $this->flowId,
        ]);

        $this->flowStats = $this->flowMapDefault;

        return $this;
    }

    /**
     * Set additional increment keys, use :
     *      'keyName' => int
     * to add keyName as increment, starting at int
     * or :
     *      'keyName' => 'existingIncrement'
     * to assign keyName as a reference to existingIncrement
     *
     * @param array $flowIncrements
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    protected function setFlowIncrement(array $flowIncrements)
    {
        foreach ($flowIncrements as $incrementKey => $target) {
            if (is_string($target)) {
                if (!isset($this->flowStats[$target])) {
                    throw new NodalFlowException('Cannot set reference on unset target');
                }

                if (substr($incrementKey, -6) === '_total') {
                    $this->incrementTotals[$incrementKey] = $target;
                    $this->flowStats[$incrementKey]       = 0;
                    continue;
                }

                $this->flowStats[$incrementKey] = &$this->flowStats[$target];
                continue;
            }

            $this->flowIncrements[$incrementKey] = $target;
            $this->flowStats[$incrementKey]      = $target;
        }

        return $this;
    }

    /**
     * @param NodeInterface $node
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    protected function setNodeIncrement(NodeInterface $node)
    {
        $nodeId = $node->getId();
        foreach ($node->getNodeIncrements() as $incrementKey => $target) {
            if (is_string($target)) {
                if (!isset($this->nodeIncrements[$target])) {
                    throw new NodalFlowException('Tried to set an increment alias to an un-registered increment', 1, null, [
                        'aliasKey'  => $incrementKey,
                        'targetKey' => $target,
                    ]);
                }

                $this->nodeMap[$nodeId][$incrementKey] = &$this->nodeMap[$nodeId][$target];
                continue;
            }

            $this->nodeIncrements[$incrementKey]   = $target;
            $this->nodeMap[$nodeId][$incrementKey] = $target;
        }

        return $this;
    }

    /**
     * @param NodeInterface $node
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    protected function enforceUniqueness(NodeInterface $node)
    {
        if (isset($this->nodeMap[$node->getId()])) {
            throw new NodalFlowException('Cannot reuse Node instances within a Flow', 1, null, [
                'duplicate_node' => get_class($node),
                'hash'           => $node->getId(),
            ]);
        }

        return $this;
    }
}
