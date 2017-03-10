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
     * As a node is supposed to be immutable, and thus
     * have no setters on $isAReturningVal and $isATraversable
     * we enforce the constructor's signature in this interface
     * One can of course still add defaulting param in extend
     *
     * @param mixed $payload
     * @param bool  $isAReturningVal
     * @param bool  $isATraversable
     *
     * @throws \Exception
     */
    public function __construct($payload, $isAReturningVal, $isATraversable);

    /**
     * @return bool
     */
    public function isTraversable();

    /**
     * @return bool true if payload instanceof FlowInterface
     */
    public function isFlow();

    /**
     * @return bool true if payload is expected to return
     *              something to pass on next node as param.
     *              If nothing is returned, the previously
     *              returned value will be use as param
     *              for next nodes.
     */
    public function isReturningVal();

    /**
     * @return object
     */
    public function getPayload();

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
