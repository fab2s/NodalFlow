<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

/**
 * Interface TraversableNodeInterface
 */
interface TraversableNodeInterface extends NodeInterface
{
    /**
     * get the traversable to traverse within the Flow
     *
     * Until PHP agrees that a Generator DOES implement the
     * Traversable pseudo interface, or allows us to further
     * restrict interfaces, we are left with the impossibility
     * to properly type the return of this
     *
     * @param mixed|null $param
     *
     * @return \Traversable
     */
    public function getTraversable($param = null);
}
