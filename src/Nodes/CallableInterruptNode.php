<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\NodalFlowException;

/**
 * Class CallableInterruptNode
 */
class CallableInterruptNode extends InterruptNodeAbstract
{
    /**
     * @var callable
     */
    protected $interrupter;

    /**
     * Instantiate a CallableInterruptNode Node
     *
     * @param callable $interrupter
     *
     * @throws NodalFlowException
     */
    public function __construct(callable $interrupter)
    {
        $this->interrupter = $interrupter;
        parent::__construct();
    }

    /**
     * @param mixed $param
     *
     * @return InterrupterInterface|null|bool `null` do do nothing, eg let the Flow proceed untouched
     *                                        `true` to trigger a continue on the carrier Flow (not ancestors)
     *                                        `false` to trigger a break on the carrier Flow (not ancestors)
     *                                        `InterrupterInterface` to trigger an interrupt to propagate up to a target (which may be one ancestor)
     */
    public function interrupt($param)
    {
        return \call_user_func($this->interrupter, $param);
    }
}
