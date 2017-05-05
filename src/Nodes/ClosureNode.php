<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\NodalFlowException;

/**
 * Class ClosureNode
 */
class ClosureNode extends PayloadNodeAbstract implements TraversableNodeInterface, ExecNodeInterface
{
    /**
     * Instantiate a Closure Node
     *
     * @param \Closure $payload
     * @param bool     $isAReturningVal
     * @param bool     $isATraversable
     *
     * @throws NodalFlowException
     */
    public function __construct($payload, $isAReturningVal, $isATraversable = false)
    {
        if (!($payload instanceof \Closure)) {
            throw new NodalFlowException('Payload is not a Closure');
        }

        parent::__construct($payload, $isAReturningVal, $isATraversable);
    }

    /**
     * Get this Node's Traversable (payload mmust be consistant for the usage)
     *
     * @param mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param)
    {
        $callable = $this->payload;
        foreach ($callable($param) as $value) {
            yield $value;
        }
    }

    /**
     * Execute this Node (payload mmust be consistant for the usage)
     *
     * @param mixed $param
     *
     * @return mixed
     */
    public function exec($param)
    {
        $callable = $this->payload;

        return $callable($param);
    }
}
