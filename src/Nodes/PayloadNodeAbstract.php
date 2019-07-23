<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\NodalFlowException;

/**
 * abstract class PayloadNodeAbstract
 */
abstract class PayloadNodeAbstract extends NodeAbstract implements PayloadNodeInterface
{
    /**
     * The underlying executable or traversable Payload
     *
     * @var object|callable
     */
    protected $payload;

    /**
     * A Payload Node is supposed to be immutable, and thus
     * have no setters on $isAReturningVal and $isATraversable
     *
     * @param mixed $payload
     * @param bool  $isAReturningVal
     * @param bool  $isATraversable
     *
     * @throws NodalFlowException
     */
    public function __construct($payload, bool $isAReturningVal, bool $isATraversable = false)
    {
        $this->payload         = $payload;
        $this->isAFlow         = (bool) ($payload instanceof FlowInterface);
        $this->isAReturningVal = (bool) $isAReturningVal;
        // let wrong traversability be enforced by parent
        $this->isATraversable  = (bool) $isATraversable;

        parent::__construct();
    }

    /**
     * Get this Node's Payload
     *
     * @return object|callable
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
