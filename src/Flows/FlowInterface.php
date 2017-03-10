<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Interface FlowInterface
 */
interface FlowInterface
{
    /**
     * @param mixed $payload
     * @param bool  $isAReturningVal
     * @param bool  $isATraversable
     */
    public function add($payload, $isAReturningVal, $isATraversable);

    /**
     * @param NodeInterface $node
     *
     * @return $this
     */
    public function addNode(NodeInterface $node);

    /**
     * Execute the Flow
     *
     * @param mixed $param The first param to apply, mostly
     *                     usefull for branches when values have
     *                     already been generated
     *
     * @return mixed, the last value returned in the chain
     */
    public function exec($param = null);

    /**
     * Rewinds the Flow
     *
     * @return $this
     */
    public function rewind();

    /**
     * get the underlying node array
     *
     * @return array
     */
    public function getNodes();
}
