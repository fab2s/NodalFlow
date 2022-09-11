<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow;

use Closure;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\Nodes\CallableNode;
use fab2s\NodalFlow\Nodes\ClosureNode;
use fab2s\NodalFlow\Nodes\PayloadNodeFactoryInterface;
use fab2s\NodalFlow\Nodes\PayloadNodeInterface;

/**
 * class PayloadNodeFactory
 */
class PayloadNodeFactory implements PayloadNodeFactoryInterface
{
    /**
     * Instantiate the proper Payload Node for the payload
     *
     * @param mixed $payload
     * @param bool  $isAReturningVal
     * @param bool  $isATraversable
     *
     * @throws NodalFlowException
     *
     * @return PayloadNodeInterface
     */
    public static function create($payload, bool $isAReturningVal, bool $isATraversable = false): PayloadNodeInterface
    {
        if (\is_array($payload) || \is_string($payload)) {
            return new CallableNode($payload, $isAReturningVal, $isATraversable);
        }

        if ($payload instanceof Closure) {
            // distinguishing Closures actually is surrealistic
            return new ClosureNode($payload, $isAReturningVal, $isATraversable);
        }

        if ($payload instanceof FlowInterface) {
            return new BranchNode($payload, $isAReturningVal);
        }

        throw new NodalFlowException('Payload not supported, must be Callable or Flow');
    }
}
