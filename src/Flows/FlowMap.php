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
     * @var array
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
        'num_exec'        => 0,
        'num_iterate'     => 0,
        'num_break'       => 0,
        'num_continue'    => 0,
    ];

    /**
     * The default Node stats values
     *
     * @var array
     */
    protected $incrementStatsDefault = [
        'num_exec'     => 0,
        'num_iterate'  => 0,
        'num_break'    => 0,
        'num_continue' => 0,
    ];

    /**
     * The Flow stats default values
     *
     * @var array
     */
    protected $flowStatsDefault = [
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
    protected $defaultFlowStats;

    /**
     * @var array
     */
    protected $flowStats;

    /**
     * @var bool
     */
    protected $resetOnRestart = false;

    /**
     * @var FlowInterface
     */
    protected $flow;

    /**
     * Instantiate a Flow Status
     *
     * @param FlowInterface $flow
     * @param array         $flowIncrements
     */
    public function __construct(FlowInterface $flow, array $flowIncrements = [])
    {
        $this->flow             = $flow;
        $this->defaultFlowStats = array_replace($this->flowStatsDefault, $this->incrementStatsDefault, [
            'class' => get_class($this->flow),
            'id'    => $this->flow->getId(),
        ]);
        $this->flowStats = $this->defaultFlowStats;

        $this->setFlowIncrement($flowIncrements);
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
        $nodeHash                 = $node->getNodeHash();
        $this->nodeMap[$nodeHash] = array_replace($this->nodeMapDefault, [
            'class'           => get_class($node),
            'flowId'          => $node->getCarrier()->getId(),
            'hash'            => $nodeHash,
            'index'           => $index,
            'isATraversable'  => $node->isTraversable(),
            'isAReturningVal' => $node->isReturningVal(),
            'isAFlow'         => $node->isFlow(),
        ]);

        $this->setNodeIncrement($node);

        if (isset($this->reverseMap[$index])) {
            // replacing a note, maintain nodeMap accordingly
            unset($this->nodeMap[$this->reverseMap[$index]]);
        }

        $this->reverseMap[$index] = $nodeHash;
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
     * Get/Generate Node Map
     *
     * @return array
     */
    public function getNodeMap()
    {
        foreach ($this->flow->getNodes() as $node) {
            if ($node instanceof BranchNodeInterface) {
                $this->nodeMap[$node->getNodeHash()]['nodes'] = $node->getPayload()->getNodeMap();
                continue;
            }

            if ($node instanceof AggregateNodeInterface) {
                $flowId = $node->getCarrier()->getId();
                foreach ($node->getNodeCollection() as $aggregatedNode) {
                    $this->nodeMap[$node->getNodeHash()]['nodes'][$aggregatedNode->getNodeHash()] = array_replace($this->nodeMapDefault, [
                        'class'  => get_class($aggregatedNode),
                        'flowId' => $flowId,
                        'hash'   => $aggregatedNode->getNodeHash(),
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
        $result = array_intersect_key($this->flowStats, $this->defaultFlowStats);
        foreach ($this->flow->getNodes() as $node) {
            if ($node instanceof BranchNodeInterface) {
                $result['branches'][$node->getPayload()->getId()] = $node->getPayload()->getStats();
            }
        }

        return $result;
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
            foreach ($this->incrementStatsDefault as $key => $value) {
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

                $this->flowStats[$incrementKey] = &$this->flowStats[$target];
                continue;
            }

            $this->defaultFlowStats[$incrementKey] = $target;
            $this->flowStats[$incrementKey]        = $target;
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
        $nodeHash = $node->getNodeHash();
        foreach ($node->getNodeIncrements() as $incrementKey => $target) {
            if (is_string($target)) {
                if (!isset($this->incrementStatsDefault[$target])) {
                    throw new NodalFlowException('Tried to set an increment alias to an un-registered increment', 1, null, [
                        'aliasKey'  => $incrementKey,
                        'targetKey' => $target,
                    ]);
                }

                $this->nodeMap[$nodeHash][$incrementKey] = &$this->nodeMap[$nodeHash][$target];
                continue;
            }

            $this->incrementStatsDefault[$incrementKey] = $target;
            $this->nodeMap[$nodeHash][$incrementKey]    = $target;
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
        if (isset($this->nodeMap[$node->getNodeHash()])) {
            throw new NodalFlowException('Cannot reuse Node instances within a Flow', 1, null, [
                'duplicate_node' => get_class($node),
                'hash'           => $node->getNodeHash(),
            ]);
        }

        return $this;
    }
}
