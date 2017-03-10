<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\Nodes\CallableNode;
use fab2s\NodalFlow\Nodes\ClosureNode;
use fab2s\NodalFlow\Nodes\NodeInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    const PAYLOAD_TYPE_INSTANCE_TRAVERSABLE = 'instance_traversable';
    const PAYLOAD_TYPE_INSTANCE_EXEC        = 'instance_exec';

    /**
     * @var array
     */
    protected $flows = [
        'CallableFlow' => 'fab2s\\NodalFlow\\CallableFlow',
    ];

    /**
     * @var array
     */
    protected $nodes = [
        'CallableNode' => 'fab2s\NodalFlow\Nodes\CallableNode',
        'ClosureNode'  => 'fab2s\NodalFlow\Nodes\ClosureNode',
        'BranchNode'   => 'fab2s\NodalFlow\Nodes\BranchNode',
    ];

    /**
     * @var array
     */
    protected $callableNodeSetup = [];

    /**
     * @var array
     */
    protected $iserCombos = [
        [
            'isAReturningVal' => true,
            'isATraversable'  => true,
        ],
        [
            'isAReturningVal' => true,
            'isATraversable'  => false,
        ],
        [
            'isAReturningVal' => false,
            'isATraversable'  => true,
        ],
        [
            'isAReturningVal' => false,
            'isATraversable'  => false,
        ],
    ];

    /**
     * @var array
     */
    protected $payloads = [];

    /**
     * @var int
     */
    protected static $generationOrder = 0;

    /**
     * @var array
     */
    protected $payLoadSpy = [];

    /**
     * @var int
     */
    protected $traversableIterations = 7;

    /**
     * @var int
     */
    protected $ExecConst = 3;

    /**
     * @var int
     */
    protected $flowParam = 42;

    /**
     * Setup the test environment.
     */
    protected function setUp()
    {
        $this->callableNodeSetup = [
            'exec' => [
                'payload' => function () {
                    return 42;
                },
                'validate' => function (NodeInterface $node) {
                    return $node->exec() === 42;
                },
            ],
            'yield' => [
                'payload' => function ($param = null) {
                    for ($i = 1; $i <= 5; ++$i) {
                        if ($param) {
                            yield $i * $param;
                        } else {
                            yield $i;
                        }
                    }
                },
                'validate' => function (NodeInterface $node, $param = null) {
                    $i = 1;
                    $result = true;
                    foreach ($node->getTraversable() as $value) {
                        $factor = $param ? ($param * $i) : $i;
                        $result = $result && $factor === $value;
                        ++$i;
                    }

                    return $result;
                },
            ],
        ];

        parent::setUp();
    }

    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @param NodeInterface $node
     * @param bool          $isAReturningVal
     * @param bool          $isATraversable
     * @param \Closure|null $closureAssertTrue
     */
    public function validateNode(NodeInterface $node, $isAReturningVal, $isATraversable, $closureAssertTrue = null)
    {
        $payload = $node->getPayload();
        $this->assertEquals($isAReturningVal, $node->isReturningVal(), 'isReturningVal for: ' . get_class($node));
        $this->assertEquals($isATraversable, $node->isTraversable(), 'isTraversable Val for: ' . get_class($node));
        $this->assertEquals($node->isFlow(), is_a($node, BranchNode::class), 'BranchNode isFlow for: ' . get_class($node));
        $this->assertEquals($node->isFlow(), $payload instanceof FlowInterface, 'FlowInterface isFlow for: ' . get_class($node));

        if ($closureAssertTrue !== null) {
            $this->assertTrue($closureAssertTrue($node), 'Node failling: ' . get_class($node));
        }
    }

    /**
     * @return array
     */
    public function getExecInstance()
    {
        $stub = $this->getMock(
          'ExecInstance', ['exec']
        );
        $ExecConst = $this->ExecConst;
        $stub->expects($spy = $this->any())
            ->method('exec')
            ->will($this->returnCallback(
                function ($param = null) use ($ExecConst) {
                    return max(0, (int) $param) + $ExecConst;
                }
            ));

        return $this->registerPayloadMock(self::PAYLOAD_TYPE_INSTANCE_EXEC, $stub, $spy);
    }

    /**
     * @return array
     */
    public function getTraversableInstance()
    {
        $stub = $this->getMock(
          'TraversableInstance', ['exec']
        );
        $traversableIterations = $this->traversableIterations;
        $stub->expects($spy = $this->any())
            ->method('exec')
            ->will($this->returnCallback(
                function ($param = null) use ($traversableIterations) {
                    $param = max(0, (int) $param);
                    $result = [];
                    for ($i = $param; $i < $param + $traversableIterations; ++$i) {
                        $result[] = $i + 1;
                    }

                    return $result;
                }
            ));

        return $this->registerPayloadMock(self::PAYLOAD_TYPE_INSTANCE_TRAVERSABLE, $stub, $spy);
    }

    protected function getFlow($flowName = null)
    {
        if (isset($this->flows[$flowName])) {
            return new $this->flows[$flowName];
        }

        return false;
    }

    protected function getNode($nodeName = null)
    {
        if (isset($this->nodes[$nodeName])) {
            return new $this->nodes[$nodeName];
        }

        return false;
    }

    protected function getCallableNodeSetup($type)
    {
        if (isset($this->callableNodeSetup[$type])) {
            return new $this->callableNodeSetup[$type];
        }

        return false;
    }

    /**
     * @param bool $debug
     *
     * @return \Closure
     */
    protected function getTraversableValidator($debug = false)
    {
        $generationOrder = self::$generationOrder;
        $closure         = function (NodeInterface $node, $param = null) use ($generationOrder, $debug) {
            static $invocations = 0;
            $result             = true;
            $i                  = max(0, (int) $param);
            ++$invocations;
            foreach ($node->getTraversable() as $value) {
                $result = $result && ($i === $value);
                if ($debug) {
                    echo str_repeat('    ', $generationOrder) . "#$generationOrder TraversableValidator invocations: $invocations, param: $param value: $value, i: $i, result:" . ($result ? 'true' : 'false') . "\n";
                }

                ++$i;
            }

            return $result;
        };

        ++self::$generationOrder;

        return $closure;
    }

    /**
     * @param int    $generationOrder
     * @param string $generator
     * @param int    $invocations
     * @param mixed  $param
     * @param mixed  $value
     * @param int    $iteration
     */
    protected function payloadClosureCalled($generationOrder, $generator, $invocations, $param, $value, $iteration = 0)
    {
        //var_dump($generationOrder, $generator, $invocations, $param, $value, $iteration);
        $this->payLoadSpy[] = [
            'genorder'    => $generationOrder,
            'generator'   => $generator,
            'invocations' => $invocations,
            'param'       => $param,
            'value'       => $value,
            'iteration'   => $iteration,
        ];
    }

    protected function getPayloadSpy()
    {
        return $this->payLoadSpy;
    }

    /**
     * @param bool $debug
     *
     * @return \Closure
     */
    protected function getTraversableClosure($debug = false)
    {
        $generationOrder = self::$generationOrder;
        $caller          = __FUNCTION__;
        $closure         = function ($param = null) use ($generationOrder, $caller, $debug) {
            static $invocations = 0;
            ++$invocations;
            $param = max(0, (int) $param);
            $limit = $param + 5;
            for ($i = $param; $i < $limit; ++$i) {
                $this->payloadClosureCalled($generationOrder, $caller, $invocations, $param, $i, $i - $param + 1);
                if ($debug) {
                    echo str_repeat('    ', $generationOrder) . "#$generationOrder traversablePayload invocations: $invocations, param: $param yield: $i\n";
                }

                yield $i;
            }
        };

        ++self::$generationOrder;

        return $closure;
    }

    /**
     * @param bool $debug
     *
     * @return \Closure
     */
    protected function getExecClosure($debug = false)
    {
        $generationOrder = self::$generationOrder;
        $caller          = __FUNCTION__;
        $closure         = function ($param = null) use ($generationOrder, $caller, $debug) {
            static $invocations = 0;
            ++$invocations;
            $param  = max(0, (int) $param);
            $result = $param + 1;
            $this->payloadClosureCalled($generationOrder, $caller, $invocations, $param, $result);
            if ($debug) {
                echo str_repeat('    ', $generationOrder) . "#$generationOrder execPayload invocations: $invocations, param: $param result: $result\n";
            }

            return $result;
        };

        ++self::$generationOrder;

        return $closure;
    }

    /**
     * @param object $payloadMock
     * @param string $type
     * @param mixed  $spy
     *
     * @return array
     */
    protected function registerPayloadMock($type, $payloadMock, $spy)
    {
        $hash                  = $this->getObjectHash($payloadMock);
        $this->payloads[$hash] = [
            'type'     => $type,
            'callable' => [$payloadMock, 'exec'],
            'spy'      => $spy,
            'hash'     => $hash,
        ];

        return $this->payloads[$hash];
    }

    /**
     * @param null|string $hash
     *
     * @return array
     */
    protected function getPayloadMock($hash = null)
    {
        if ($hash !== null) {
            return isset($this->payloads[$hash]) ? $this->payloads[$hash] : null;
        }

        return $this->payloads;
    }

    /**
     * @param object $object
     *
     * @return string
     */
    protected function getObjectHash($object)
    {
        return \sha1(\spl_object_hash($object));
    }
}
