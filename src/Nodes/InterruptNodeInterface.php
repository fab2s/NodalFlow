<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\InterrupterInterface;

/**
 * Interface InterruptNodeInterface
 */
interface InterruptNodeInterface extends ExecNodeInterface
{
    /**
     * @param mixed $param
     *
     * @return InterrupterInterface|null|bool `null` do do nothing, eg let the Flow proceed untouched
     *                                        `true` to trigger a continue on the carrier Flow (not ancestors)
     *                                        `false` to trigger a break on the carrier Flow (not ancestors)
     *                                        `InterrupterInterface` to trigger an interrupt with a target (which may be one ancestor)
     */
    public function interrupt($param);
}
