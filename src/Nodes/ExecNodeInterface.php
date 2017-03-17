<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

/**
 * Interface NodeInterface
 * Stuffing every exec combo here saves a lot of interfaces,
 * KISS wins ^^
 */
interface ExecNodeInterface extends NodeInterface
{
    /**
     * @param mixed
     * @param mixed $param
     *
     * @return mixed
     */
    public function exec($param);
}
