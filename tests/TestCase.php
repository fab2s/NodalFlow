<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\AggregateNode;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\Nodes\BranchNodeInterface;
use fab2s\NodalFlow\Nodes\CallableNode;
use fab2s\NodalFlow\Nodes\NodeInterface;
use fab2s\NodalFlow\Nodes\TraversableNodeInterface;

/**
 * abstract Class TestCase
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    const PAYLOAD_TYPE_INSTANCE_TRAVERSABLE = 'instance_traversable';
    const PAYLOAD_TYPE_INSTANCE_EXEC        = 'instance_exec';

    /**
     * @var array
     */
    protected $flows = [
        'NodalFlow' => 'fab2s\\NodalFlow\\NodalFlow',
    ];

    /**
     * @var array
     */
    protected $nodes = [
        'CallableNode' => 'fab2s\\NodalFlow\\Nodes\\CallableNode',
        'ClosureNode'  => 'fab2s\\NodalFlow\\Nodes\\ClosureNode',
        'BranchNode'   => 'fab2s\\NodalFlow\\Nodes\\BranchNode',
    ];

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
     * @var int
     */
    protected $traversableIterations = 8;

    /**
     * @var int
     */
    protected $ExecConst = 27;

    /**
     * @var int
     */
    protected $flowParam = 42;

    /**
     * @var int
     */
    protected $interruptPos = 4;

    /**
     * @param NodeInterface $node
     * @param bool          $isAReturningVal
     * @param bool          $isATraversable
     * @param \Closure|null $closureAssertTrue
     */
    public function validateNode(NodeInterface $node, $isAReturningVal, $isATraversable, $closureAssertTrue = null)
    {
        $this->assertEquals($isAReturningVal, $node->isReturningVal(), 'isReturningVal for: ' . get_class($node));

        $this->assertEquals($node->isFlow(), $node instanceof  BranchNodeInterface, 'BranchNode isFlow for: ' . get_class($node));

        if ($closureAssertTrue !== null) {
            $this->assertTrue($closureAssertTrue($node), 'Node failing: ' . get_class($node));
        }
    }

    /**
     * @return array
     */
    public function getExecInstance()
    {
        static $nonce = 0;
        $class        = 'ExecInstance' . $nonce++;
        $stub         = $this->getMockBuilder($class)
                ->setMethods(['exec'])
                ->getMock();

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
        static $nonce = 0;
        $class        = 'TraversableInstance' . $nonce++;
        $stub         = $this->getMockBuilder($class)
                ->setMethods(['exec'])
                ->getMock();

        $traversableIterations = $this->traversableIterations;
        $stub->expects($spy = $this->any())
            ->method('exec')
            ->will($this->returnCallback(
                function ($param = null) use ($traversableIterations) {
                    $param  = max(0, (int) $param);
                    $result = [];
                    for ($i = $param; $i < $param + $traversableIterations; ++$i) {
                        $result[] = $i + 1;
                    }

                    return $result;
                }
            ));

        return $this->registerPayloadMock(self::PAYLOAD_TYPE_INSTANCE_TRAVERSABLE, $stub, $spy);
    }

    /**
     * @throws Exception
     * @throws NodalFlowException
     *
     * @return array
     */
    public function flowCasesProvider()
    {
        $testNodes = $this->getTestNodes();
        $cases     = $this->getFlowCases();

        $entryDefault = [
            'flow'     => null,
            'nodes'    => [],
            'param'    => null,
            'expected' => null,
            'case'     => [],
        ];

        $result = [];
        $debug  = false;
        foreach ($cases as $flowName => $case) {
            $flowName = $case['flowName'];
            foreach ($case['expectations'] as $expectation) {
                foreach ($expectation['cases'] as $expectationCase) {
                    $entry         = $entryDefault;
                    $entry['case'] = $expectationCase;
                    $entry['flow'] = $this->getFlow($flowName);

                    foreach ($case['nodes'] as $idx => $nodeName) {
                        if (is_array($nodeName)) {
                            $nodeSetup = $nodeName;
                            if (!empty($nodeSetup['aggregate'])) {
                                $aggregateNode = new AggregateNode($expectation['isAReturningVal'][$idx]);
                                foreach ($nodeSetup['nodes'] as $subIdx => $payloadGenerator) {
                                    $isAReturningVal         = $nodeSetup['nodeIsAReturningVal'][$subIdx];
                                    $payloadSetup            = $this->$payloadGenerator();
                                    $subNode                 = new CallableNode($payloadSetup['callable'], $isAReturningVal, true);
                                    $payloadSetup['nodeId']  = $subNode->getId();
                                    $nodeSetup['payloads'][] = $payloadSetup;
                                    $aggregateNode->addTraversable($subNode);
                                }

                                $nodeSetup['aggregate'] = $aggregateNode;
                            }

                            if (!empty($nodeSetup['branch'])) {
                                $flow = new NodalFlow;
                                foreach ($nodeSetup['nodes'] as $subIdx => $payloadGenerator) {
                                    // in the branch case, we only test returning val case
                                    // as returning is already thoroughly tested with flows
                                    $payloadSetup            = $this->$payloadGenerator();
                                    $nodeSetup['payloads'][] = $payloadSetup;
                                    $flow->add(new CallableNode($payloadSetup['callable'], true, $payloadGenerator === 'getTraversableInstance'));
                                }

                                $nodeSetup['branch'] = new BranchNode($flow, $expectation['isAReturningVal'][$idx]);
                            }
                        } else {
                            $nodeSetup = $testNodes[$nodeName];
                        }

                        $nodeSetup['isAReturningVal'] = $expectation['isAReturningVal'][$idx];
                        if (isset($expectationCase['expected_exec'])) {
                            $nodeSetup['expected_iteration'] = $expectationCase['expected_exec'][$idx]['iterations'];
                            $nodeSetup['expected_exec']      = $expectationCase['expected_exec'][$idx]['exec'];
                        }

                        if (isset($nodeSetup['payloadGenerator'])) {
                            if ($nodeSetup['payloadGenerator'] === 'getExecInterruptClosure') {
                                if (isset($expectationCase['interrupt'], $expectationCase['interruptAt'])) {
                                    $nodeSetup['payload'] = $this->{$nodeSetup['payloadGenerator']}($expectationCase['interrupt'], $expectationCase['interruptAt'], $entry['flow'], $debug);
                                } else {
                                    continue;
                                }
                            } else {
                                $nodeSetup['payloadSetup'] = $this->{$nodeSetup['payloadGenerator']}();
                                $nodeSetup['payload']      = $nodeSetup['payloadSetup']['callable'];
                            }
                        }

                        $entry['nodes'][] = $nodeSetup;
                    }

                    $entry['param']    = $expectationCase['param'];
                    $entry['expected'] = $expectationCase['expected'];
                    $result[]          = $entry;
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getTestNodes()
    {
        $testNodes = [
            'traversableInstance' => [
                'nodeName'         => 'CallableNode',
                'payloadGenerator' => 'getTraversableInstance',
                'isATraversable'   => true,
            ],
            'execInstance' => [
                'nodeName'         => 'CallableNode',
                'payloadGenerator' => 'getExecInstance',
                'isATraversable'   => false,
            ],
            'execClosure' => [
                'nodeName'       => 'CallableNode',
                'payload'        => $this->getExecClosure(true),
                'isATraversable' => false,
            ],
            'execInterruptClosure' => [
                'nodeName'         => 'CallableNode',
                'payloadGenerator' => 'getExecInterruptClosure',
                'isATraversable'   => false,
            ],
        ];

        $nodeDefault = [
            'nodeName'        => 'CallableNode',
            'nodeClass'       => null,
            'payload'         => null,
            'isAReturningVal' => true,
            'isATraversable'  => false,
            'validate'        => null,
        ];

        foreach ($testNodes as &$testNodeSetup) {
            $testNodeSetup              = array_replace($nodeDefault, $testNodeSetup);
            $testNodeSetup['nodeClass'] = $this->nodes[$testNodeSetup['nodeName']];
        }

        return $testNodes;
    }

    /**
     * @param null|string $flowName
     *
     * @return bool
     */
    protected function getFlow($flowName = null)
    {
        if (isset($this->flows[$flowName])) {
            return new $this->flows[$flowName];
        }

        return false;
    }

    /**
     * @param null|string $nodeName
     *
     * @return bool
     */
    protected function getNode($nodeName = null)
    {
        if (isset($this->nodes[$nodeName])) {
            return new $this->nodes[$nodeName];
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
        $closure         = function (TraversableNodeInterface $node, $param = null) use ($generationOrder, $debug) {
            static $invocations = 0;
            $result             = true;
            $i                  = max(0, (int) $param);
            ++$invocations;
            foreach ($node->getTraversable(null) as $value) {
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
     * @param bool $debug
     *
     * @return \Closure
     */
    protected function getTraversableClosure($debug = false)
    {
        $generationOrder = self::$generationOrder;
        $closure         = function ($param = null) use ($generationOrder, $debug) {
            static $invocations = 0;
            ++$invocations;
            $param = max(0, (int) $param);
            $limit = $param + 5;
            for ($i = $param; $i < $limit; ++$i) {
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
        $closure         = function ($param = null) use ($generationOrder, $debug) {
            static $invocations = 0;
            ++$invocations;
            $param  = max(0, (int) $param);
            $result = $param + 1;
            if ($debug) {
                echo str_repeat('    ', $generationOrder) . "#$generationOrder execPayload invocations: $invocations, param: $param result: $result\n";
            }

            return $result;
        };

        ++self::$generationOrder;

        return $closure;
    }

    /**
     * @param string                                                                                                 $type
     * @param object                                                                                                 $payloadMock
     * @param PHPUnit\Framework\MockObject\Matcher\AnyInvokedCount|PHPUnit\Framework\MockObject\Rule\AnyInvokedCount $spy
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

    /**
     * @return Closure
     */
    protected function getNoOpClosure()
    {
        return function ($record) {
            return $record;
        };
    }
}
