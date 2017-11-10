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
     * Triggered right before the flow starts
     *
     * @return $this
     */
    public function flowStart();

    /**
     * Triggered right after the flow stops
     *
     * @return $this
     */
    public function flowEnd();

    /**
     * @param NodeInterface $node
     * @param int           $index
     *
     * @throws NodalFlowException
     */
    public function register(NodeInterface $node, $index);

    /**
     * @param string $nodeHash
     * @param array  $incrementAliases
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function setIncrementAlias($nodeHash, array $incrementAliases = []);

    /**
     * @param array $flowIncrements
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function setFlowIncrement(array $flowIncrements);

    /**
     * @param string $nodeHash
     * @param string $key
     *
     * @return $this
     */
    public function increment($nodeHash, $key);

    /**
     * @param string $key
     *
     * @return $this
     */
    public function incrementFlow($key);

    /**
     * Get/Generate Node Map
     *
     * @return array
     */
    public function getNodeMap();

    /**
     * Get the latest Node stats
     *
     * @return array
     */
    public function getStats();
}
