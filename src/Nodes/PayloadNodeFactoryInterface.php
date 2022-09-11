<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

/**
 * Interface PayloadNodeFactoryInterface
 */
interface PayloadNodeFactoryInterface
{
    /**
     * Instantiate the proper Payload Node for the payload
     *
     * @param object|callable $payload
     * @param bool            $isAReturningVal
     * @param bool            $isATraversable
     *
     * @return PayloadNodeInterface
     */
    public static function create($payload, bool $isAReturningVal, bool $isATraversable = false): PayloadNodeInterface;
}
