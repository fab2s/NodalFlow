<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\Interrupter;
use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\Nodes\CallableInterruptNode;
use fab2s\NodalFlow\Nodes\NodeInterface;
use fab2s\NodalFlow\PayloadNodeFactory;

/**
 * Class FlowInterruptTest
 */
class FlowInterruptTest extends \TestCase
{
    /**
     * @var int
     */
    protected $progressMod = 10;

    /**
     * @var array
     */
    protected $interruptMapUnset = [
        'class'           => 1,
        'branchId'        => 1,
        'hash'            => 1,
        'index'           => 1,
        'isATraversable'  => 1,
        'isAReturningVal' => 1,
        'isAFlow'         => 1,
    ];

    /**
     * @dataProvider interruptProvider
     *
     * @param FlowInterface $flow
     * @param array         $expected
     */
    public function testInterrupt(FlowInterface $flow, array $expected)
    {
        $flow->exec();
        $this->interruptAssertions($flow->getNodeMap(), $expected);
    }

    /**
     * @dataProvider flowCasesProvider
     *
     * @param FlowInterface $flow
     * @param array         $nodes
     * @param mixed         $param
     * @param mixed         $expected
     * @param array         $case
     *
     * @throws NodalFlowException
     */
    public function testInterruptFlows(FlowInterface $flow, array $nodes, $param, $expected, $case)
    {
        foreach ($nodes as $key => $nodeSetup) {
            /** @var NodeInterface $node */
            $node = new $nodeSetup['nodeClass']($nodeSetup['payload'], $nodeSetup['isAReturningVal'], $nodeSetup['isATraversable']);
            $this->validateNode($node, $nodeSetup['isAReturningVal'], $nodeSetup['isATraversable'], $nodeSetup['validate']);

            $flow->add($node);
            $nodes[$key]['hash'] = $node->getId();
        }

        $result    = $flow->exec($param);
        $nodeMap   = $flow->getNodeMap();
        $flowStats = $flow->getStats();

        foreach ($nodes as $nodeSetup) {
            if (isset($nodeSetup['payloadSetup'])) {
                $payloadSetup   = $nodeSetup['payloadSetup'];
                // get spy's invocations
                // check multi phpunit versions support
                if (is_callable([$payloadSetup['spy'], 'getInvocations'])) {
                    $invocations    = $payloadSetup['spy']->getInvocations();
                    $spyInvocations = count($invocations);
                } else {
                    $spyInvocations = $payloadSetup['spy']->getInvocationCount();
                }

                $nodeStats      = $nodeMap[$nodeSetup['hash']];
                $nodeMap[$nodeSetup['hash']] += [
                    'isAReturningVal' => $nodeSetup['isAReturningVal'],
                    'isATraversable'  => $nodeSetup['isATraversable'],
                    'spy'             => $payloadSetup['spy'],
                ];

                // assert that each node has effectively been called
                // as many time as reported internally.
                // Coupled with overall result provides with a
                // pretty good guaranty about what happened.
                // It is for example making sure that params
                // where properly passed since we try all return
                // val combos and the result is pretty unique for
                // each combo
                $this->assertSame($spyInvocations, $nodeStats['num_exec'], "Node num_exec {$nodeStats['num_exec']} does not match spy's invocation $spyInvocations");

                if ($case['interrupt']) {
                    $interruptType = $case['interrupt'];
                    $nodeCnt       = $nodeStats['num_' . $interruptType];
                    if ($nodeCnt) {
                        // this is not ideal, but is a quick way to
                        // catch the interrupting node
                        $flowCnt = $flowStats['num_' . $interruptType];
                        $this->assertSame($flowCnt, $nodeCnt, "Node num_$interruptType $nodeCnt does not match flow's $flowCnt");
                    }
                }

                if (isset($nodeSetup['expected_exec'])) {
                    $this->assertSame($nodeStats['num_exec'], $nodeSetup['expected_exec'], "Node num_exec {$nodeStats['num_exec']} does not match expected: {$nodeSetup['expected_exec']}");
                }

                if (isset($nodeSetup['num_iterate'])) {
                    // make sure we iterated as expected
                    if (isset($nodeSetup['expected_iteration'])) {
                        $iterations = $nodeSetup['expected_iteration'];
                    } else {
                        $iterations = $this->traversableIterations;
                    }

                    $this->assertSame($iterations, $nodeSetup['num_iterate'], "Node num_iterate {$nodeStats['num_iterate']} does not match expected: {$iterations}");
                }
            }
        }

        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider interruptProvider
     *
     * @param NodalFlow $flow
     *
     * @throws NodalFlowException
     * @throws ReflectionException
     */
    public function testCallback(NodalFlow $flow)
    {
        $dummyCallback = new DummyCallback;
        $flow->setProgressMod($this->progressMod)->setCallback($dummyCallback)->exec();
        $this->assertTrue($dummyCallback->hasStarted());
        $this->assertTrue($dummyCallback->hasSucceeded());
        $this->assertFalse($dummyCallback->hasFailed());

        $nodeMap        = $flow->getNodeMap();
        $shouldProgress = false;
        foreach ($nodeMap as $nodeId => $data) {
            if ($data['num_iterate'] >= $this->progressMod) {
                $shouldProgress = true;
                break;
            }
        }

        $this->assertTrue($shouldProgress ? $dummyCallback->getNumProgress() > 0 : true);
    }

    /**
     * @throws Exception
     * @throws NodalFlowException
     *
     * @return array
     */
    public function interruptProvider()
    {
        $breakAt5Node1    = new CallableInterruptNode($this->getBreakAt5Closure());
        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $testCases          = [];
        $testCases['flow1'] = [
            'flow'     => (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($breakAt5Node1)
                ->add($noOpNode2),
            'expected' => [
                $traversableNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 5,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpNode1->getId() => [
                    'num_exec'     => 5,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $breakAt5Node1->getId() => [
                    'num_exec'     => 5,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpNode2->getId() => [
                    'num_exec'     => 4,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $continueAt5Node1   = new CallableInterruptNode($this->getContinueAt5Closure());
        $traversableNode1   = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $noOpNode1          = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode2          = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $testCases['flow2'] = [
            'flow'     => (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($continueAt5Node1)
                ->add($noOpNode2),
            'expected' => [
                $traversableNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpNode1->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $continueAt5Node1->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 1,
                ],
                $noOpNode2->getId() => [
                    'num_exec'     => 9,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $breakAt5Node1    = new CallableInterruptNode($this->getBreakAt5Closure());
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($breakAt5Node1)
                ->add($noOpNode2),
            false
        );

        $noOpNode3 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $testCases['flow3'] = [
            'flow'     => (new NodalFlow)->add($noOpNode3)
                ->add($branchNode1)
                ->add($noOpNode4),
            'expected' => [
                $noOpNode3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $continueAt5Node1 = new CallableInterruptNode($this->getContinueAt5Closure());
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($continueAt5Node1)
                ->add($noOpNode2),
            false
        );

        $noOpNode3 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $testCases['flow4'] = [
            'flow'     => (new NodalFlow)->add($noOpNode3)
                ->add($branchNode1)
                ->add($noOpNode4),
            'expected' => [
                $noOpNode3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 10,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 9,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $breakAt5Node1    = new CallableInterruptNode($this->getBreakAt5Closure());
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($breakAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $testCases['flow5'] = [
            'flow'     => (new NodalFlow)->add($noOpNode4)
                ->add($branchNode1)
                ->add($noOpNode5),
            'expected' => [
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 10,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 95,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 95,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 95,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 94,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $continueAt5Node1 = new CallableInterruptNode($this->getContinueAt5Closure());
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($continueAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $testCases['flow6'] = [
            'flow'     => (new NodalFlow)->add($noOpNode4)
                ->add($branchNode1)
                ->add($noOpNode5),
            'expected' => [
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 10,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 100,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 100,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 100,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 99,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $breakAt5Node1    = new CallableInterruptNode($this->getBreakAt5Closure(new Interrupter(InterrupterInterface::TARGET_SELF, $traversableNode1, InterrupterInterface::TYPE_BREAK)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($breakAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $testCases['flow7'] = [
            'flow'     => (new NodalFlow)->add($noOpNode4)
                ->add($branchNode1)
                ->add($noOpNode5),
            'expected' => [
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 1,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $continueAt5Node1 = new CallableInterruptNode($this->getContinueAt5Closure(new Interrupter(InterrupterInterface::TARGET_SELF, $traversableNode1, InterrupterInterface::TYPE_CONTINUE)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($continueAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $testCases['flow8'] = [
            'flow'     => (new NodalFlow)->add($noOpNode4)
                ->add($branchNode1)
                ->add($noOpNode5),
            'expected' => [
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 10,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 10,
                            'num_iterate'  => 95,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 95,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 95,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 94,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new NodalFlow;
        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $breakAt5Node1    = new CallableInterruptNode($this->getBreakAt5Closure(new Interrupter($rootFlow, null, InterrupterInterface::TYPE_BREAK)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($breakAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $rootFlow->add($noOpNode4)
            ->add($branchNode1)
            ->add($noOpNode5);

        $testCases['flow9'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 1,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 0,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new NodalFlow;
        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $continueAt5Node1 = new CallableInterruptNode($this->getContinueAt5Closure(new Interrupter($rootFlow, null, InterrupterInterface::TYPE_CONTINUE)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($continueAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $rootFlow->add($noOpNode4)
            ->add($branchNode1)
            ->add($noOpNode5);

        $testCases['flow10'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 1,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 1,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 0,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new NodalFlow;
        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode3 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode4 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $breakAt5Node1    = new CallableInterruptNode($this->getBreakAt5Closure(new Interrupter($rootFlow, null, InterrupterInterface::TYPE_BREAK)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($breakAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode6 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $rootFlow->add($traversableNode3)
            ->add($noOpNode6)
            ->add($traversableNode4)
            ->add($noOpNode4)
            ->add($branchNode1)
            ->add($noOpNode5);

        $testCases['flow11'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $traversableNode3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpNode6->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $traversableNode4->getId() => [
                    'num_exec'     => 10,
                    // the break signal is sent at rec n°5
                    // it is detected on the 1st records of
                    // this traversable which breaks there
                    // and get the upstream traversable 2nd
                    // rec and so on
                    'num_iterate'  => 91,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpNode4->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 91,
                            'num_iterate'  => 901,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 9005,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 9004,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 90,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new NodalFlow;
        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode3 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode4 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $continueAt5Node1 = new CallableInterruptNode($this->getBreakAt5Closure(new Interrupter($rootFlow, null, InterrupterInterface::TYPE_CONTINUE)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($continueAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode6 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $rootFlow->add($traversableNode3)
            ->add($noOpNode6)
            ->add($traversableNode4)
            ->add($noOpNode4)
            ->add($branchNode1)
            ->add($noOpNode5);

        $testCases['flow12'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $traversableNode3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpNode6->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $traversableNode4->getId() => [
                    'num_exec'     => 10,
                    // the break signal is sent at rec n°5
                    // it is detected on the 1st records of
                    // this traversable which breaks there
                    // and get the upstream traversable 2nd
                    // rec and so on
                    'num_iterate'  => 100,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpNode4->getId() => [
                    'num_exec'     => 100,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 100,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 1,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 100,
                            'num_iterate'  => 991,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 991,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 991,
                            'num_iterate'  => 9905,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 9905,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 9905,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 9904,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 99,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new NodalFlow;
        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode3 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode4 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $breakAt5Node1    = new CallableInterruptNode($this->getBreakAt5Closure(new Interrupter($rootFlow, $traversableNode3, InterrupterInterface::TYPE_BREAK)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($breakAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode6 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $rootFlow->add($traversableNode3)
            ->add($noOpNode6)
            ->add($traversableNode4)
            ->add($noOpNode4)
            ->add($branchNode1)
            ->add($noOpNode5);

        $testCases['flow13'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $traversableNode3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 1,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpNode6->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $traversableNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 1,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpNode4->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 1,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 1,
                            'num_iterate'  => 5,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 5,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 4,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 0,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new NodalFlow;
        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode3 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode4 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $continueAt5Node1 = new CallableInterruptNode($this->getBreakAt5Closure(new Interrupter($rootFlow, $traversableNode3, InterrupterInterface::TYPE_CONTINUE)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($continueAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode6 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $rootFlow->add($traversableNode3)
            ->add($noOpNode6)
            ->add($traversableNode4)
            ->add($noOpNode4)
            ->add($branchNode1)
            ->add($noOpNode5);

        $testCases['flow14'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $traversableNode3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 1,
                ],
                $noOpNode6->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $traversableNode4->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 91,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpNode4->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 1,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 91,
                            'num_iterate'  => 901,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 9005,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $continueAt5Node1->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 1,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 9004,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 90,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        $rootFlow         = new NodalFlow;
        $traversableNode1 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode2 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode3 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $traversableNode4 = PayloadNodeFactory::create($this->getTraversable10Closure(), true, true);
        $breakAt5Node1    = new CallableInterruptNode($this->getBreakAt5Closure(new Interrupter($rootFlow, $traversableNode4, InterrupterInterface::TYPE_BREAK)));
        $noOpNode1        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode2        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $noOpNode3        = PayloadNodeFactory::create($this->getNoOpClosure(), true);
        $branchNode1      = new BranchNode(
            (new NodalFlow)->add($traversableNode1)
                ->add($noOpNode1)
                ->add($traversableNode2)
                ->add($noOpNode2)
                ->add($breakAt5Node1)
                ->add($noOpNode3),
            false
        );

        $noOpNode4 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode5 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $noOpNode6 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);

        $rootFlow->add($traversableNode3)
            ->add($noOpNode6)
            ->add($traversableNode4)
            ->add($noOpNode4)
            ->add($branchNode1)
            ->add($noOpNode5);

        $testCases['flow15'] = [
            'flow'     => $rootFlow,
            'expected' => [
                $traversableNode3->getId() => [
                    'num_exec'     => 1,
                    'num_iterate'  => 10,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $noOpNode6->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $traversableNode4->getId() => [
                    'num_exec'     => 10,
                    'num_iterate'  => 91,
                    'num_break'    => 1,
                    'num_continue' => 0,
                ],
                $noOpNode4->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
                $branchNode1->getId() => [
                    'num_exec'     => 91,
                    'num_iterate'  => 0,
                    'num_break'    => 1,
                    'num_continue' => 0,
                    'nodes'        => [
                        $traversableNode1->getId() => [
                            'num_exec'     => 91,
                            'num_iterate'  => 901,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode1->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $traversableNode2->getId() => [
                            'num_exec'     => 901,
                            'num_iterate'  => 9005,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode2->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                        $breakAt5Node1->getId() => [
                            'num_exec'     => 9005,
                            'num_iterate'  => 0,
                            'num_break'    => 1,
                            'num_continue' => 0,
                        ],
                        $noOpNode3->getId() => [
                            'num_exec'     => 9004,
                            'num_iterate'  => 0,
                            'num_break'    => 0,
                            'num_continue' => 0,
                        ],
                    ],
                ],
                $noOpNode5->getId() => [
                    'num_exec'     => 90,
                    'num_iterate'  => 0,
                    'num_break'    => 0,
                    'num_continue' => 0,
                ],
            ],
        ];

        return $testCases;
    }

    /**
     * @return Closure
     */
    protected function getTraversable10Closure()
    {
        return function () {
            for ($i = 1; $i <= 10; ++$i) {
                yield $i;
            }
        };
    }

    /**
     * @param bool|InterrupterInterface $return
     *
     * @return Closure
     */
    protected function getBreakAt5Closure($return = false)
    {
        return function () use ($return) {
            static $cnt = 1;
            if ($cnt === 5) {
                ++$cnt;

                return $return;
            }

            ++$cnt;
        };
    }

    /**
     * @param bool|InterrupterInterface $return
     *
     * @return Closure
     */
    protected function getContinueAt5Closure($return = true)
    {
        return function () use ($return) {
            static $cnt = 1;
            if ($cnt === 5) {
                ++$cnt;

                return $return;
            }

            ++$cnt;
        };
    }

    /**
     * @param array $nodeMap
     * @param array $expected
     */
    protected function interruptAssertions(array $nodeMap, array $expected)
    {
        foreach ($nodeMap as $nodeId => $data) {
            $this->assertTrue(isset($expected[$nodeId]));
            if (isset($data['nodes'])) {
                $this->assertTrue(isset($expected[$nodeId]['nodes']));
                $this->interruptAssertions($data['nodes'], $expected[$nodeId]['nodes']);
            }

            unset($data['nodes'], $expected[$nodeId]['nodes']);

            $expected[$nodeId]['class'] = $data['class'];
            $expected[$nodeId]['hash']  = $data['hash'];
            $actual                     = array_intersect_key($data, $expected[$nodeId]);
            $this->assertSame(array_replace($actual, $expected[$nodeId]), $actual);
        }
    }

    /**
     * @param mixed         $interrupt
     * @param mixed         $interruptAt
     * @param FlowInterface $flow
     * @param bool          $debug
     *
     * @return Closure
     */
    protected function getExecInterruptClosure($interrupt, $interruptAt, FlowInterface $flow, $debug = false)
    {
        $generationOrder = self::$generationOrder;
        $execConst       = $this->ExecConst;
        $closure         = function ($param = null) use ($generationOrder, $execConst, $interrupt, $interruptAt, $flow, $debug) {
            static $invocations = 0;
            ++$invocations;
            if (
                $interruptAt &&
                ((
                    $interrupt === 'break' &&
                    $invocations >= $interruptAt
                ) ||
                (
                    $interrupt   === 'continue' &&
                    $invocations === $interruptAt
                ))
            ) {
                if ($debug) {
                    echo str_repeat('    ', $generationOrder) . "#$generationOrder execInterruptPayload INTERRUPT. invocations: $invocations, interrupt: $interrupt, interruptAt: $interruptAt,  param: $param result: $param\n";
                }

                $interrupt = $interrupt . 'Flow';

                $flow->$interrupt();
                // return param as it came
                return $param;
            }

            $param  = max(0, (int) $param);
            $result = $param + $execConst;
            if ($debug) {
                echo str_repeat('    ', $generationOrder) . "#$generationOrder execInterruptPayload invocations: $invocations, interrupt: $interrupt, interruptAt: $interruptAt,  param: $param result: $result\n";
            }

            return $result;
        };

        ++self::$generationOrder;

        return $closure;
    }

    /**
     * @return array
     */
    protected function getFlowCases()
    {
        $traversedInterrupt    = $this->interruptPos ? $this->interruptPos : $this->traversableIterations;
        $interruptEqIterations = $this->interruptPos === $this->traversableIterations;
        $cases                 = [
            'single1' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['traversableInstance', 'execInstance', 'execInterruptClosure'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true, true],
                        'cases'           => [
                            [
                                'param'       => null,
                                'expected'    => $this->interruptPos ? $traversedInterrupt + $this->ExecConst : $traversedInterrupt + 2 * $this->ExecConst,
                                'interrupt'   => 'break',
                                'interruptAt' => $this->interruptPos,
                            ],
                            [
                                'param'       => $this->flowParam,
                                'expected'    => ($this->interruptPos ? $traversedInterrupt + $this->ExecConst : $traversedInterrupt + 2 * $this->ExecConst) + $this->flowParam,
                                'interrupt'   => 'break',
                                'interruptAt' => $this->interruptPos,
                            ],
                            [
                                'param'       => null,
                                'expected'    => $interruptEqIterations ? $this->traversableIterations + $this->ExecConst : $this->traversableIterations + 2 * $this->ExecConst,
                                'interrupt'   => 'continue',
                                'interruptAt' => $this->interruptPos,
                            ],
                            [
                                'param'       => $this->flowParam,
                                'expected'    => ($interruptEqIterations ? $this->traversableIterations + $this->ExecConst : $this->traversableIterations + 2 * $this->ExecConst) + $this->flowParam,
                                'interrupt'   => 'continue',
                                'interruptAt' => $this->interruptPos,
                            ],
                        ],
                    ],
                ],
            ],
            'single2' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['traversableInstance', 'execInterruptClosure', 'execInstance'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true, true],
                        'cases'           => [
                            [
                                'param'         => null,
                                'expected'      => $this->interruptPos ? $traversedInterrupt : $traversedInterrupt + 2 * $this->ExecConst,
                                'interrupt'     => 'break',
                                'interruptAt'   => $this->interruptPos,
                                'expected_exec' => [
                                    [
                                        'iterations' => $traversedInterrupt,
                                        'exec'       => 1,
                                    ],
                                    [
                                        'iterations' => 0,
                                        'exec'       => $traversedInterrupt,
                                    ],
                                    [
                                        'iterations' => 0,
                                        'exec'       => $this->interruptPos ? $traversedInterrupt - 1 : $traversedInterrupt,
                                    ],
                                ],
                            ],
                            [
                                'param'         => $this->flowParam,
                                'expected'      => ($this->interruptPos ? $traversedInterrupt : $traversedInterrupt + 2 * $this->ExecConst) + $this->flowParam,
                                'interrupt'     => 'break',
                                'interruptAt'   => $this->interruptPos,
                                'expected_exec' => [
                                    [
                                        'iterations' => $traversedInterrupt,
                                        'exec'       => 1,
                                    ],
                                    [
                                        'iterations' => 0,
                                        'exec'       => $traversedInterrupt,
                                    ],
                                    [
                                        'iterations' => 0,
                                        'exec'       => $this->interruptPos ? $traversedInterrupt - 1 : $traversedInterrupt,
                                    ],
                                ],
                            ],
                            [
                                'param'         => null,
                                'expected'      => $interruptEqIterations ? $this->traversableIterations : $this->traversableIterations + 2 * $this->ExecConst,
                                'interrupt'     => 'continue',
                                'interruptAt'   => $this->interruptPos,
                                'expected_exec' => [
                                    [
                                        'iterations' => $this->traversableIterations,
                                        'exec'       => 1,
                                    ],
                                    [
                                        'iterations' => 0,
                                        'exec'       => $this->traversableIterations,
                                    ],
                                    [
                                        'iterations' => 0,
                                        'exec'       => $this->interruptPos ? $this->traversableIterations - 1 : $this->traversableIterations,
                                    ],
                                ],
                            ],
                            [
                                'param'         => $this->flowParam,
                                'expected'      => ($interruptEqIterations ? $this->traversableIterations : $this->traversableIterations + 2 * $this->ExecConst) + $this->flowParam,
                                'interrupt'     => 'continue',
                                'interruptAt'   => $this->interruptPos,
                                'expected_exec' => [
                                    [
                                        'iterations' => $this->traversableIterations,
                                        'exec'       => 1,
                                    ],
                                    [
                                        'iterations' => 0,
                                        'exec'       => $this->traversableIterations,
                                    ],
                                    [
                                        'iterations' => 0,
                                        'exec'       => $this->interruptPos ? $this->traversableIterations - 1 : $this->traversableIterations,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $cases;
    }
}
