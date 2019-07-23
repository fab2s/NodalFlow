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
 * Interface FlowMapInterface
 */
interface FlowMapInterface
{
    /**
     * @param string $nodeId
     *
     * @return int|null
     */
    public function getNodeIndex(string $nodeId): ? int;

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
     *
     * @param string $nodeId
     *
     * @return array
     */
    public function &getNodeStat(string $nodeId): array;

    /**
     * @param NodeInterface $node
     * @param int           $index
     * @param bool          $replace
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function register(NodeInterface $node, int $index, bool $replace = false): self;

    /**
     * @param string $nodeId
     * @param string $key
     *
     * @return $this
     */
    public function incrementNode(string $nodeId, string $key): self;

    /**
     * @param string $key
     *
     * @return $this
     */
    public function incrementFlow(string $key): self;

    /**
     * Get/Generate Node Map
     *
     * @return array
     */
    public function getNodeMap(): array;

    /**
     * Get the latest Node stats
     *
     * @return array<string,integer|string>
     */
    public function getStats(): array;
}
