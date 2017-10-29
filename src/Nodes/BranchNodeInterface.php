<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\FlowInterface;

/**
 * Interface BranchNodeInterface
 */
interface BranchNodeInterface extends PayloadNodeInterface, ExecNodeInterface
{
    /**
     * Get this Node's Payload
     *
     * @return FlowInterface
     */
    public function getPayload();
}
