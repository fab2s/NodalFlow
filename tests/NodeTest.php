<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\Nodes\ExecNodeInterface;

/**
 * Class NodeTest
 */
class NodeTest extends \TestCase
{
    /**
     * @return array
     */
    public function nodeProvider()
    {
        $use                 = 'use';
        $lambda              = function () {
        };
        $closure             = function () use ($use) {
        };
        $function              = 'trim';
        $callableInstance      = [new DummyClass, 'dummyMethod'];
        $callableStatic        = '\\DummyClass::dummyStatic';
        $callableStaticYielder = '\\DummyClass::dummyStaticYielder';

        $yielderValidator = function ($node) {
            return DummyClass::dummyYielderValidator($node);
        };

        $payloads = [
            'CallableNode' => [
                [
                    'payload'         => $lambda,
                ],
                [
                    'payload'         => function () {
                        return 42;
                    },
                    // forcing these two will bypass comboing
                    'isAReturningVal'   => true,
                    'isATraversable'    => false,
                    'closureAssertTrue' => function (ExecNodeInterface $node) {
                        return $node->exec(null) === 42;
                    },
                ],
                [
                    'payload'         => function () {
                        for ($i = 1; $i < 6; ++$i) {
                            yield $i;
                        }
                    },
                    'isAReturningVal'   => true,
                    'isATraversable'    => true,
                    'closureAssertTrue' => $yielderValidator,
                ],
                $closure,
                [
                    'payload'         => function () use ($use) {
                        return $use;
                    },
                    'isAReturningVal'   => true,
                    'isATraversable'    => false,
                    'closureAssertTrue' => function (ExecNodeInterface $node) use ($use) {
                        return $node->exec(null) === $use;
                    },
                ],
                $function,
                [
                    'payload'         => $callableInstance,
                ],
                [
                    'payload'           => $callableInstance,
                    'isAReturningVal'   => true,
                    'isATraversable'    => false,
                    'closureAssertTrue' => function (ExecNodeInterface $node) {
                        return $node->exec(null) === 'dummyMethod';
                    },
                ],
                [
                    'payload'           => [new DummyClass, 'dummyInstanceYielder'],
                    'isAReturningVal'   => true,
                    'isATraversable'    => true,
                    'closureAssertTrue' => $yielderValidator,
                ],
                $callableStatic,
                [
                    'payload'           => $callableStaticYielder,
                    'isAReturningVal'   => true,
                    'isATraversable'    => true,
                    'closureAssertTrue' => $yielderValidator,
                ],
            ],
            'ClosureNode'  => $closure,
            'BranchNode'   => [
                [
                    'payload'           => new NodalFlow,
                    'isAReturningVal'   => true,
                    'isATraversable'    => false,
                ],
            ],
        ];

        $result = [];
        foreach ($this->nodes as $nodeName => $className) {
            $payloadSetup = $payloads[$nodeName];
            $entries      = [];
            $entry        = [
                'class'   => $className,
            ];

            if (is_array($payloadSetup)) {
                foreach ($payloadSetup as $setup) {
                    if (is_array($setup)) {
                        $entries[] = $entry + $setup;
                    } else {
                        $entries[] = $entry + ['payload' => $setup];
                    }
                }
            } else {
                $entry['payload'] = $payloads[$nodeName];
                $entries[]        = $entry;
            }

            foreach ($entries as $entry) {
                if (!isset($entry['isAReturningVal'], $entry['isATraversable'])) {
                    foreach ($this->iserCombos as $combo) {
                        $result[] = $entry + $combo;
                    }
                } else {
                    $result[] = $entry;
                }
            }
        }

        return $result;
    }

    /**
     * @dataProvider nodeProvider
     *
     * @param string     $class
     * @param mixed      $payload
     * @param bool       $isAReturningVal
     * @param bool       $isATraversable
     * @param null|mixed $closureAssertTrue
     */
    public function testNodes($class, $payload, $isAReturningVal, $isATraversable, $closureAssertTrue = null)
    {
        $node = new $class($payload, $isAReturningVal, $isATraversable);
        $this->validateNode($node, $isAReturningVal, $isATraversable, $closureAssertTrue);
    }
}
