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
     * @return bool
     */
    public function isTraversable();

    /**
     * @return bool true if this node is an instanceof FlowInterface
     */
    public function isFlow();

    /**
     * @return bool true if this node is expected to return
     *              something to pass to the next node as param.
     *              If nothing is returned, the previously
     *              returned value will be use as param
     *              for next nodes.
     */
    public function isReturningVal();

    /**
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setCarrier(FlowInterface $flow);

    /**
     * @return FlowInterface
     */
    public function getCarrier();

    /**
     * @param string $nodeHash
     *
     * @return $this
     */
    public function setNodeHash($nodeHash);

    /**
     * @return string
     */
    public function getNodeHash();
}
