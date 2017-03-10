<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

/**
 * Class ClosureNode
 */
class ClosureNode extends NodeAbstract implements TraversableNodeInterface, ExecNodeInterface
{
    /**
     * @param \Closure $payload
     * @param bool     $isAReturningVal
     * @param bool     $isATraversable
     *
     * @throws \Exception
     */
    public function __construct($payload, $isAReturningVal, $isATraversable)
    {
        $this->enforceIsATraversable($isATraversable);

        if (!($payload instanceof \Closure)) {
            throw new \Exception('Payload is not a Closure');
        }

        parent::__construct($payload, $isAReturningVal, $isATraversable);
    }

    /**
     * @param mixed
     * @param null|mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param = null)
    {
        $callable = $this->payload;
        foreach ($callable($param) as $value) {
            yield $value;
        }
    }

    /**
     * @param mixed
     * @param null|mixed $param
     *
     * @return mixed
     */
    public function exec($param = null)
    {
        $callable = $this->payload;

        return $callable($param);
    }
}
