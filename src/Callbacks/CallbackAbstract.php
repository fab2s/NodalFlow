<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Callbacks;

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Abstract Class CallbackAbstract
 * Provide with dummy implementations to allow partial
 * and DRY usage (eg actually use some of the events)
 */
abstract class CallbackAbstract implements CallbackInterface
{
    /**
     * Triggered when a Flow starts
     *
     * @param FlowInterface $flow
     */
    public function start(FlowInterface $flow)
    {
    }

    /**
     * Triggered when a Flow progresses,
     * eg exec once or generates once
     *
     * @param FlowInterface $flow
     * @param NodeInterface $node
     */
    public function progress(FlowInterface $flow, NodeInterface $node)
    {
    }

    /**
     * Triggered when a Flow succeeds
     *
     * @param FlowInterface $flow
     */
    public function success(FlowInterface $flow)
    {
    }

    /**
     * Triggered when a Flow fails
     *
     * @param FlowInterface $flow
     */
    public function fail(FlowInterface $flow)
    {
    }
}
