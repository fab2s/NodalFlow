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
     * @var FlowRegistryInterface
     */
    protected $registry;

    /**
     * @var array
     */
    protected $registryData = [];

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
     *
     * @throws NodalFlowException
     */
    public function __construct(FlowInterface $flow, array $flowIncrements = [])
    {
        $this->flow     = $flow;
        $this->flowId   = $this->flow->getId();
        $this->registry = (new FlowRegistry)->registerFlow($flow);
        $this->initDefaults()->setRefs()->setFlowIncrement($flowIncrements);
    }

    /**
     * If you don't feel like doing this at home, I completely
     * understand, I'd be very happy to hear about a better and
     * more efficient way
     *
     * @throws NodalFlowException
     */
    public function __wakeup()
    {
        $this->registry->load($this->flow, $this->registryData);
        $this->setRefs();
    }

    /**
     * @param NodeInterface $node
     * @param int           $index
     * @param bool          $replace
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function register(NodeInterface $node, int $index, bool $replace = false): FlowMapInterface
    {
        if (!$replace) {
            $this->registry->registerNode($node);
        } else {
            $this->registry->removeNode($this->reverseMap[$index]);
        }

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
            unset($this->nodeMap[$this->reverseMap[$index]->getId()], $this->reverseMap[$index]);
        }

        $this->reverseMap[$index] = $node;

        return $this;
    }

    /**
     * @param string $nodeId
     *
     * @return int|null
     */
    public function getNodeIndex(string $nodeId): ? int
    {
        return isset($this->nodeMap[$nodeId]) ? $this->nodeMap[$nodeId]['index'] : null;
    }

    /**
     * Triggered right before the flow starts
     *
     * @return $this
     */
    public function flowStart(): FlowMapInterface
    {
        $this->flowStats['start'] = microtime(true);

        return $this;
    }

    /**
     * Triggered right after the flow stops
     *
     * @return $this
     */
    public function flowEnd(): FlowMapInterface
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
     * @param string $nodeId
     *
     * @return array
     */
    public function &getNodeStat(string $nodeId): array
    {
        return $this->nodeMap[$nodeId];
    }

    /**
     * Get/Generate Node Map
     *
     * @return array
     */
    public function getNodeMap(): array
    {
        foreach ($this->flow->getNodes() as $node) {
            $nodeId = $node->getId();
            if ($node instanceof BranchNodeInterface || $node instanceof AggregateNodeInterface) {
                $this->nodeMap[$nodeId]['nodes'] = $node->getPayload()->getNodeMap();
            }
        }

        return $this->nodeMap;
    }

    /**
     * Get the latest Node stats
     *
     * @return array<string,integer|string>
     */
    public function getStats(): array
    {
        $this->resetTotals();
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

        $flowStatus = $this->flow->getFlowStatus();
        if ($flowStatus !== null) {
            $this->flowStats['flow_status'] = $flowStatus->getStatus();
        }

        return $this->flowStats;
    }

    /**
     * @param string $nodeId
     * @param string $key
     *
     * @return $this
     */
    public function incrementNode(string $nodeId, string $key): FlowMapInterface
    {
        ++$this->nodeMap[$nodeId][$key];

        return $this;
    }

    /**
     * @param string $key
     *
     * @return $this
     */
    public function incrementFlow(string $key): FlowMapInterface
    {
        ++$this->flowStats[$key];

        return $this;
    }

    /**
     * Resets Nodes stats, can be used prior to Flow's re-exec
     *
     * @return $this
     */
    public function resetNodeStats(): FlowMapInterface
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
    public function duration(float $seconds): array
    {
        $result = [
            'hour'     => (int) floor($seconds / 3600),
            'min'      => (int) floor(\fmod($seconds / 60, 60)),
            'sec'      => (int) \fmod($seconds, 60),
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
    protected function setRefs(): self
    {
        $this->registryData              = &$this->registry->get($this->flowId);
        $this->registryData['flowStats'] = &$this->flowStats;
        $this->registryData['nodeStats'] = &$this->nodeMap;
        $this->registryData['nodes']     = &$this->reverseMap;

        return $this;
    }

    /**
     * @return $this
     */
    protected function initDefaults(): self
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
     * @return $this
     */
    protected function resetTotals(): self
    {
        foreach ($this->incrementTotals as $totalKey) {
            $this->flowStats[$totalKey] = 0;
        }

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
    protected function setFlowIncrement(array $flowIncrements): self
    {
        foreach ($flowIncrements as $incrementKey => $target) {
            if (is_string($target)) {
                if (!isset($this->flowStats[$target])) {
                    throw new NodalFlowException('Cannot set reference on unset target');
                }

                $this->flowStats[$incrementKey]            = &$this->flowStats[$target];
                $this->flowStats[$incrementKey . '_total'] = &$this->flowStats[$target . '_total'];
                continue;
            }

            $this->flowIncrements[$incrementKey]  = $target;
            $this->incrementTotals[$incrementKey] = $incrementKey . '_total';
            $this->flowStats[$incrementKey]       = $target;
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
    protected function setNodeIncrement(NodeInterface $node): self
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
}
