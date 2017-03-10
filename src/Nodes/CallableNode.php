<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

/**
 * Class CallableNode
 */
class CallableNode extends NodeAbstract implements TraversableNodeInterface, ExecNodeInterface
{
    /**
     * @param callable $payload
     * @param bool     $isAReturningVal
     * @param bool     $isATraversable
     *
     * @throws \Exception
     */
    public function __construct($payload, $isAReturningVal, $isATraversable = false)
    {
        $this->enforceIsATraversable($isATraversable);

        if (!is_callable($payload)) {
            throw new \Exception('Payload is not callable');
        }

        parent::__construct($payload, $isAReturningVal, $isATraversable);
    }

    /**
     * @param mixed
     * @param null|mixed $param
     *
     * @return mixed|void|yield
     */
    public function exec($param = null)
    {
        return \call_user_func($this->payload, $param);
    }

    /**
     * @param mixed
     * @param null|mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param = null)
    {
        foreach (\call_user_func($this->payload, $param) as $value) {
            yield $value;
        }
    }
}
