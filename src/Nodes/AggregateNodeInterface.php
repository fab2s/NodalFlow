<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

/**
 * Interface AggregateNodeInterface
 */
interface AggregateNodeInterface extends TraversableNodeInterface
{
    /**
     * get the traversable to traverse within the Flow
     *
     * TraversableNodeInterface $node
     *
     * @return $this
     */
    public function addTraversable(TraversableNodeInterface $node);

    /**
     * get the underlying node collection
     *
     * @return array
     */
    public function getNodeCollection();
}
