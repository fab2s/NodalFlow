<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

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
     * @return bool true if payload is an instanceof FlowInterface
     */
    public function isFlow();

    /**
     * @return bool true if payload is expected to return
     *              something to pass to the next node as param.
     *              If nothing is returned, the previously
     *              returned value will be use as param
     *              for next nodes.
     */
    public function isReturningVal();

    /**
     * @param mixed $branchId
     *
     * @return $this
     */
    public function setBranchId($branchId);

    /**
     * @return mixed
     */
    public function getBranchId();

    /**
     * @param mixed $nodeHash
     *
     * @return $this
     */
    public function setNodeHash($nodeHash);

    /**
     * @return mixed
     */
    public function getNodeHash();
}
