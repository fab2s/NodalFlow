<?php

/*
 * This file is part of NodalFlow.
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
     * @return FlowInterface
     */
    public function getFlow();

    /**
     * @return NodeInterface
     */
    public function getNode();

    /**
     * @param NodeInterface|null $node
     *
     * @return $this
     */
    public function setNode(NodeInterface $node = null);

    /**
     * @return array
     */
    public static function getEventList();
}
