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
     * @param mixed|null $param
     *
     * @return iterable
     */
    public function getTraversable($param = null): iterable;
}
