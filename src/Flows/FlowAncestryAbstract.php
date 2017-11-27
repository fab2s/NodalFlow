<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

/**
 * Abstract Class FlowAncestryAbstract
 */
abstract class FlowAncestryAbstract extends FlowInterruptAbstract
{
    /**
     * The parent Flow, only set when branched
     *
     * @var FlowInterface
     */
    public $parent;

    /**
     * Set parent Flow, happens only when branched
     *
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setParent(FlowInterface $flow)
    {
        $this->parent = $flow;

        return $this;
    }

    /**
     * Get eventual parent Flow
     *
     * @return FlowInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Tells if this flow has a parent
     *
     * @return bool
     */
    public function hasParent()
    {
        return !empty($this->parent);
    }

    /**
     * Get this Flow's root Flow
     *
     * @param FlowInterface $flow Root Flow, or self if root flow
     *
     * @return FlowInterface
     */
    public function getRootFlow(FlowInterface $flow)
    {
        while ($flow->hasParent()) {
            $flow = $flow->getParent();
        }

        return $flow;
    }
}
