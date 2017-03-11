<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow;

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\Nodes\CallableNode;
use fab2s\NodalFlow\Nodes\ClosureNode;
use fab2s\NodalFlow\Nodes\NodeFactoryInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * class NodeFactoryAbstract
 */
class NodeFactory implements NodeFactoryInterface
{
    /**
     * @param mixed $payload
     * @param bool  $isAReturningVal
     * @param bool  $isATraversable
     *
     * @return NodeInterface
     */
    public static function create($payload, $isAReturningVal, $isATraversable = false)
    {
        if (is_array($payload) || is_string($payload)) {
            return new CallableNode($payload, $isAReturningVal, $isATraversable);
        } elseif ($payload instanceof \Closure) {
            // distinguishing Closures actually is surrealistic
            return new ClosureNode($payload, $isAReturningVal, $isATraversable);
        } elseif ($payload instanceof FlowInterface) {
            return new BranchNode($payload, $isAReturningVal, $isATraversable);
        }

        throw new \Exception('Payload not supported, must be Callable');
    }
}
