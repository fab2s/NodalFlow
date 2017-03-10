<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow;

use fab2s\NodalFlow\Flows\FlowAbstract;

/**
 * Class CallableFlow
 */
class CallableFlow extends FlowAbstract
{
    /**
     * @param mixed $payload
     * @param mixed $isAReturningVal
     * @param mixed $isATraversable
     *
     * @return $this
     */
    public function add($payload, $isAReturningVal, $isATraversable)
    {
        $node = NodeFactory::create($payload, $isAReturningVal, $isATraversable);

        parent::addNode($node);

        return $this;
    }
}
