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
interface AggregateNodeInterface extends TraversableNodeInterface, PayloadNodeInterface, BranchNodeInterface
{
    /**
     * Add a traversable to the aggregate
     *
     * @param TraversableNodeInterface $node A Traversable Node
     *
     * @return $this
     */
    public function addTraversable(TraversableNodeInterface $node): self;
}
