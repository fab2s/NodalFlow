<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\Nodes\NodeInterface;
use fab2s\NodalFlow\Nodes\TraversableNodeInterface;

/**
 * Abstract Class FlowAncestryAbstract
 */
abstract class FlowAncestryAbstract implements FlowInterface
{
    /**
     * The underlying node structure
     *
     * @var TraversableNodeInterface|NodeInterface[]
     */
    protected $nodes = [];

    /**
     * The current Node index, being the next slot in $this->nodes for
     * node additions and the current node index when executing the flow
     *
     * @var int
     */
    protected $nodeIdx = 0;

    /**
     * The last index value
     *
     * @var int
     */
    protected $lastIdx = 0;

    /**
     * The parent Flow, only set when branched
     *
     * @var FlowInterface|null
     */
    protected $parent;

    /**
     * Set parent Flow, happens only when branched
     *
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setParent(FlowInterface $flow): FlowInterface
    {
        $this->parent = $flow;

        return $this;
    }

    /**
     * Get eventual parent Flow
     *
     * @return FlowInterface
     */
    public function getParent(): FlowInterface
    {
        return $this->parent;
    }

    /**
     * Tells if this flow has a parent
     *
     * @return bool
     */
    public function hasParent(): bool
    {
        return isset($this->parent);
    }

    /**
     * Get this Flow's root Flow
     *
     * @param FlowInterface $flow Root Flow, or self if root flow
     *
     * @return FlowInterface
     */
    public function getRootFlow(FlowInterface $flow): FlowInterface
    {
        while ($flow->hasParent()) {
            $flow = $flow->getParent();
        }

        return $flow;
    }
}
