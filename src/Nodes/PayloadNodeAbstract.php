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
 * abstract class PayloadNodeAbstract
 */
abstract class PayloadNodeAbstract extends NodeAbstract implements PayloadNodeInterface
{
    /**
     * @var object|callable
     */
    protected $payload;

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
    public function __construct($payload, $isAReturningVal, $isATraversable = false)
    {
        $this->payload         = $payload;
        $this->isAFlow         = (bool) ($payload instanceof FlowInterface);
        $this->isAReturningVal = (bool) $isAReturningVal;
        // let wrong traversability be enforced by parent
        $this->isATraversable  = (bool) $isATraversable;

        parent::__construct();
    }

    /**
     * @return object|callable
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
