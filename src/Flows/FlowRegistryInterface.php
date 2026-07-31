<?php

/*
 * This file is part of NodalFlow
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
     */
    public function &get(string $flowId);

    /**
     * Used upon FlowMap un-serialization
     *
     *
     * @return $this
     */
    public function load(FlowInterface $flow, array $entry): self;

    /**
     * @return $this
     *
     * @throws NodalFlowException
     */
    public function registerFlow(FlowInterface $flow): self;

    /**
     * @return $this
     *
     * @throws NodalFlowException
     */
    public function registerNode(NodeInterface $node): self;

    public function getFlow(string $flowId): ?FlowInterface;

    public function getNode(string $nodeId): ?NodeInterface;

    /**
     * @return $this
     */
    public function removeNode(NodeInterface $node): self;
}
