<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Class FlowInstanceTest
 */
class FlowInstanceTest extends \TestCase
{
    /**
     * @dataProvider flowCasesProvider
     *
     * @param FlowInterface $flow
     * @param array         $nodes
     * @param mixed         $param
     * @param mixed         $expected
     *
     * @throws NodalFlowException
     */
    public function testFlows(FlowInterface $flow, array $nodes, $param, $expected)
    {
        foreach ($nodes as $key => $nodeSetup) {
            /** @var NodeInterface $node */
            $node = new $nodeSetup['nodeClass']($nodeSetup['payload'], $nodeSetup['isAReturningVal'], $nodeSetup['isATraversable']);
            $this->validateNode($node, $nodeSetup['isAReturningVal'], $nodeSetup['isATraversable'], $nodeSetup['validate']);

            $flow->add($node);
            $nodes[$key]['hash'] = $node->getId();
        }

        $result  = $flow->exec($param);
        $nodeMap = $flow->getNodeMap();

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

                if ($nodeStats['num_iterate']) {
                    // make sure we iterated as expected
                    $this->assertSame($nodeStats['num_iterate'], $this->traversableIterations * $nodeStats['num_exec'], "Node num_iterate {$nodeStats['num_iterate']} does not match expected \$this->traversableIterations * num_exec = $this->traversableIterations * {$nodeStats['num_exec']}");
                }
            }
        }

        $this->assertSame($expected, $result);

        // Flow must be repeatable
        $this->assertSame($expected, $flow->exec($param));
    }

    /**
     * @return array
     */
    protected function getFlowCases()
    {
        /*
         * * single 1 to 4 covers all possible combos with two nodes in a flow
         * * single 5 to 7 covers all possible combos with three nodes in a flow
         *
         */
        $cases = [
            'single1' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['traversableInstance', 'execInstance'],
                // expectations are setting combos for this flow
                'expectations' => [
                    [
                        // will test all nodes with isAReturningVal
                        'isAReturningVal' => [true, true],
                        // and two cases, one with a param an one without
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations + $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations + $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [true, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => null,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->flowParam,
                            ],
                        ],
                    ],
                ],
            ],
            'single2' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['execInstance', 'traversableInstance'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations + $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations + $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [true, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->flowParam + $this->ExecConst,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => null,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->flowParam,
                            ],
                        ],
                    ],
                ],
            ],
            'single3' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['traversableInstance', 'traversableInstance'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * 2,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * 2 + $this->flowParam,
                            ],
                        ],
                    ],
                   [
                        'isAReturningVal' => [true, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => null,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->flowParam,
                            ],
                        ],
                    ],
                ],
            ],
            'single4' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['execInstance', 'execInstance'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->ExecConst + $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->ExecConst + $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [true, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => null,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->flowParam,
                            ],
                        ],
                    ],
                ],
            ],
            'single5' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['traversableInstance', 'traversableInstance', 'execInstance'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * 2 + $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * 2 + $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => ($this->traversableIterations + $this->ExecConst) * $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => ($this->traversableIterations + $this->ExecConst) * $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => ($this->traversableIterations * $this->ExecConst) * $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => ($this->traversableIterations * $this->ExecConst) * $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => null,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [true, false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                ],
            ],
            'single6' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['traversableInstance', 'execInstance', 'traversableInstance'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * 2 + $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * 2 + $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => ($this->traversableIterations + $this->ExecConst) * $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => ($this->traversableIterations + $this->ExecConst) * $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => null,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [true, false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                ],
            ],
            'single7' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['execInstance', 'traversableInstance', 'traversableInstance'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * 2 + $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * 2 + $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * 2,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * 2 + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->traversableIterations * $this->traversableIterations,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->traversableIterations * $this->traversableIterations + $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => null,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->flowParam,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [true, false, false],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => $this->ExecConst,
                            ],
                            [
                                'param'    => $this->flowParam,
                                'expected' => $this->ExecConst + $this->flowParam,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $cases;
    }
}
