<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

interface OnFlowStartInterface
{
    /**
     * Triggered within the flowStart methods of flows
     * Typically to perform init operations such has
     * opening a file
     *
     * @return $this
     */
    public function onFlowStart(): self;
}
