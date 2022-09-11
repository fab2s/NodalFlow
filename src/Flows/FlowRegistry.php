<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * class FlowRegistry
 */
class FlowRegistry implements FlowRegistryInterface
{
    /**
     * @var array
     */
    protected static $registry = [];

    /**
     * @var FlowInterface[]
     */
    protected static $flows = [];

    /**
     * @var NodeInterface[]
     */
    protected static $nodes = [];

    /**
     * Get registry meta data reference
     *
     * @param string $flowId
     *
     * @return mixed
     */
    public function &get(string $flowId)
    {
        return static::$registry[$flowId];
    }

    /**
     * Used upon FlowMap un-serialization
     *
     * @param FlowInterface $flow
     * @param array         $entry
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function load(FlowInterface $flow, array $entry): FlowRegistryInterface
    {
        $this->registerFlow($flow);
        $flowId                    = $flow->getId();
        static::$registry[$flowId] = $entry;

        foreach ($flow->getNodes() as $node) {
            $this->registerNode($node);
        }

        return $this;
    }

    /**
     * @param FlowInterface $flow
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function registerFlow(FlowInterface $flow): FlowRegistryInterface
    {
        $flowId = $flow->getId();
        if (isset(static::$flows[$flowId])) {
            throw new NodalFlowException('Duplicate Flow instances are not allowed', 1, null, [
                'flowClass' => get_class($flow),
                'flowId'    => $flowId,
            ]);
        }

        static::$flows[$flowId] = $flow;

        return $this;
    }

    /**
     * @param NodeInterface $node
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function registerNode(NodeInterface $node): FlowRegistryInterface
    {
        $nodeId = $node->getId();
        if (isset(static::$nodes[$nodeId])) {
            throw new NodalFlowException('Duplicate Node instances are not allowed', 1, null, [
                'nodeClass' => get_class($node),
                'nodeId'    => $nodeId,
            ]);
        }

        static::$nodes[$nodeId] = $node;

        return $this;
    }

    /**
     * @param string $flowId
     *
     * @return FlowInterface|null
     */
    public function getFlow(string $flowId): ? FlowInterface
    {
        return isset(static::$flows[$flowId]) ? static::$flows[$flowId] : null;
    }

    /**
     * @param string $nodeId
     *
     * @return NodeInterface|null
     */
    public function getNode(string $nodeId): ? NodeInterface
    {
        return isset(static::$nodes[$nodeId]) ? static::$nodes[$nodeId] : null;
    }

    /**
     * @param NodeInterface $node
     *
     * @return $this
     */
    public function removeNode(NodeInterface $node): FlowRegistryInterface
    {
        static::$nodes[$node->getId()] = null;

        return $this;
    }
}
