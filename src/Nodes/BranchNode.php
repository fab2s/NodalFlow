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
 * Class BranchNode
 */
class BranchNode extends NodeAbstract
{
    /**
     * @var bool
     */
    protected $isAFlow = true;

    /**
     * @param FlowInterface $payload
     * @param bool          $isAReturningVal
     * @param bool          $isATraversable
     */
    public function __construct($payload, $isAReturningVal, $isATraversable)
    {
        if (!($payload instanceof FlowInterface)) {
            throw new \Exception('Payload does not implement FlowInterface : ' . (is_object($payload) ? get_class($payload) : gettype($payload)));
        }

        parent::__construct($payload, $isAReturningVal, $isATraversable);
    }
}