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
 * Interface FlowRegistryInterface
 */
interface FlowRegistryInterface
{
    /**
     * Get registry meta data reference
     *
     * @param string $flowId
     *
     * @return mixed
     */
    public function &get($flowId);

    /**
     * Used upon FlowMap un-serialization
     *
     * @param FlowInterface $flow
     * @param array         $entry
     *
     * @return $this
     */
    public function load(FlowInterface $flow, array $entry);

    /**
     * @param FlowInterface $flow
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function registerFlow(FlowInterface $flow);

    /**
     * @param NodeInterface $node
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function registerNode(NodeInterface $node);

    /**
     * @param string $flowId
     *
     * @return FlowInterface|null
     */
    public function getFlow($flowId);

    /**
     * @param string $nodeId
     *
     * @return NodeInterface|null
     */
    public function getNode($nodeId);
}
