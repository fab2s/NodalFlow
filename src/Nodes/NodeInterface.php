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
 * Interface NodeInterface
 */
interface NodeInterface
{
    /**
     * Indicate if the Node is Traversable
     *
     * @return bool
     */
    public function isTraversable();

    /**
     * Indicate if the Node is a Flow (branch)
     *
     * @return bool true if this node is an instanceof FlowInterface
     */
    public function isFlow();

    /**
     * Indicate if the Node is returning a value
     *
     * @return bool true if this node is expected to return
     *              something to pass to the next node as param.
     *              If nothing is returned, the previously
     *              returned value will be use as param
     *              for next nodes.
     */
    public function isReturningVal();

    /**
     * Set carrying Flow
     *
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setCarrier(FlowInterface $flow);

    /**
     * Return the carrying Flow
     *
     * @return FlowInterface
     */
    public function getCarrier();

    /**
     * Get this Node's hash, must be deterministic and unique
     *
     * @return string
     */
    public function getNodeHash();
}
