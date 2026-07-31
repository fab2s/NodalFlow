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
 * Interface FlowMapInterface
 */
interface FlowMapInterface
{
    public function getNodeIndex(string $nodeId): ?int;

    /**
     * Triggered right before the flow starts
     *
     * @return $this
     */
    public function flowStart(): self;

    /**
     * Triggered right after the flow stops
     *
     * @return $this
     */
    public function flowEnd(): self;

    /**
     * Let's be fast at incrementing while we are at it
     */
    public function &getNodeStat(string $nodeId): array;

    /**
     * @return $this
     *
     * @throws NodalFlowException
     */
    public function register(NodeInterface $node, int $index, bool $replace = false): self;

    /**
     * @return $this
     */
    public function incrementNode(string $nodeId, string $key): self;

    /**
     * @return $this
     */
    public function incrementFlow(string $key): self;

    /**
     * Get/Generate Node Map
     */
    public function getNodeMap(): array;

    /**
     * Get the latest Node stats
     *
     * @return array<string,int|string>
     */
    public function getStats(): array;
}
