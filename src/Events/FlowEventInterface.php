<?php

/*
 * This file is part of NodalFlow
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Events;

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Interface FlowEventInterface
 */
interface FlowEventInterface
{
    /**
     * Flow Events
     * These are removed from the implementation due to
     * Symfony BC mess (it is one)
     */
    const FLOW_START    = 'flow.start';
    const FLOW_PROGRESS = 'flow.progress';
    const FLOW_CONTINUE = 'flow.continue';
    const FLOW_BREAK    = 'flow.break';
    const FLOW_SUCCESS  = 'flow.success';
    const FLOW_FAIL     = 'flow.fail';

    public function getFlow(): FlowInterface;

    public function getNode(): ?NodeInterface;

    /**
     * @return $this
     */
    public function setNode(?NodeInterface $node = null): self;

    public static function getEventList(): array;
}
