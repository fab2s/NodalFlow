<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\NodalFlowException;
use Generator;

/**
 * Class CallableNode
 */
class CallableNode extends PayloadNodeAbstract implements TraversableNodeInterface, ExecNodeInterface
{
    /**
     * The underlying executable or traversable Payload
     *
     * @var callable
     */
    protected $payload;

    /**
     * Instantiate a Callable Node
     *
     * @param callable $payload
     * @param bool     $isAReturningVal
     * @param bool     $isATraversable
     *
     * @throws NodalFlowException
     */
    public function __construct(callable $payload, bool $isAReturningVal, bool $isATraversable = false)
    {
        parent::__construct($payload, $isAReturningVal, $isATraversable);
    }

    /**
     * Execute this node
     *
     * @param mixed|null $param
     *
     * @return mixed
     */
    public function exec($param = null)
    {
        return \call_user_func($this->payload, $param);
    }

    /**
     * Get this Node's Traversable
     *
     * @param mixed $param
     *
     * @return Generator
     */
    public function getTraversable($param = null): iterable
    {
        foreach (\call_user_func($this->payload, $param) as $value) {
            yield $value;
        }
    }
}
