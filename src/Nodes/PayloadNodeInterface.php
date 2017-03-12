<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

/**
 * Interface PayloadNodeInterface
 */
interface PayloadNodeInterface extends NodeInterface
{
    /**
     * As a Payload Node is supposed to be immutable, and thus
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
    public function __construct($payload, $isAReturningVal, $isATraversable = false);

    /**
     * @return object|Callable
     */
    public function getPayload();
}
