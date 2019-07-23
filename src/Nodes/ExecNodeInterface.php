<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

/**
 * Interface ExecNodeInterface
 */
interface ExecNodeInterface extends NodeInterface
{
    /**
     * Execute this Node
     *
     * @param mixed $param
     *
     * @return mixed The result of this node
     *               execution with this param
     */
    public function exec($param = null);
}
