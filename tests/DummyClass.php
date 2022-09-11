<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Nodes\TraversableNodeInterface;

/**
 * Class DummyClass
 */
class DummyClass
{
    const YIELDER_ITERATIONS = 5;

    /**
     * @param null|mixed $param
     *
     * @return mixed
     */
    public function dummyMethod($param = null)
    {
        return $param ? $param : 'dummyMethod';
    }

    /**
     * @param null|mixed $param
     *
     * @return \Generator
     */
    public function dummyInstanceYielder($param = null)
    {
        for ($i = 1; $i <= self::YIELDER_ITERATIONS; ++$i) {
            yield $i;
        }
    }

    /**
     * @param null|mixed $param
     *
     * @return mixed
     */
    public static function dummyStatic($param = null)
    {
        return $param ? $param : 'dummyStatic';
    }

    /**
     * @param null|mixed $param
     *
     * @return \Generator
     */
    public static function dummyStaticYielder($param = null)
    {
        for ($i = 1; $i <= self::YIELDER_ITERATIONS; ++$i) {
            yield $i;
        }
    }

    /**
     * @param TraversableNodeInterface $node
     *
     * @return bool
     */
    public static function dummyYielderValidator(TraversableNodeInterface $node)
    {
        $i      = 1;
        $result = true;
        foreach ($node->getTraversable(null) as $value) {
            $result = $result && $i === $value;
            ++$i;
        }

        return $result;
    }
}
