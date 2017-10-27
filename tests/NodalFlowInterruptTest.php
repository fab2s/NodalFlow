<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Flows\FlowInterface;

class NodalFlowInterruptTest extends \TestCase
{
    /**
     * @dataProvider flowCasesProvider
     *
     * @param FlowInterface $flow
     * @param array         $nodes
     * @param mixed         $param
     * @param mixed         $expected
     * @param array         $case
     */
    public function testInterruptFlows(FlowInterface $flow, array $nodes, $param, $expected, $case)
    {
        foreach ($nodes as $key => $nodeSetup) {
            $node = new $nodeSetup['nodeClass']($nodeSetup['payload'], $nodeSetup['isAReturningVal'], $nodeSetup['isATraversable']);
            $this->validateNode($node, $nodeSetup['isAReturningVal'], $nodeSetup['isATraversable'], $nodeSetup['validate'], $case);

            $flow->add($node);
            $nodes[$key]['hash'] = $node->getNodeHash();
        }

        $result    = $flow->exec($param);
        $nodeMap   = $flow->getNodeMap();
        $flowStats = $flow->getStats();

        foreach ($nodes as $nodeSetup) {
            if (isset($nodeSetup['payloadSetup'])) {
                $payloadSetup   = $nodeSetup['payloadSetup'];
                // get spy's invocations
                $invocations    = $payloadSetup['spy']->getInvocations();
                $spyInvocations = count($invocations);
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

                    $this->assertSame($nodeStats['num_iterate'], $nodeSetup['num_iterate'], "Node num_iterate {$nodeStats['num_iterate']} does not match expected: {$nodeSetup['num_iterate']}");
                }
            }
        }

        $this->assertSame($expected, $result);
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
                        $interrupt === 'continue' &&
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
        /*
         *
         */

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
            'single3' => [
                'flowName'     => 'NodalFlow',
                'nodes'        => ['traversableInstance', 'traversableInstance', 'execInterruptClosure'],
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true, true],
                        'cases'           => [
                            [
                                'param'       => null,
                                'expected'    => $_expected = $this->interruptPos
                                    ?
                                        (
                                            $this->interruptPos % $this->traversableIterations
                                            ?
                                                $this->interruptPos % $this->traversableIterations + 1
                                            :
                                                $this->traversableIterations
                                        )
                                        + intval($this->interruptPos / $this->traversableIterations)
                                    :
                                        $traversedInterrupt * 2 + $this->ExecConst,
                                'interrupt'   => 'break',
                                'interruptAt' => $this->interruptPos,
                            ],
                            [
                                'param'       => $this->flowParam,
                                'expected'    => $_expected + $this->flowParam,
                                'interrupt'   => 'break',
                                'interruptAt' => $this->interruptPos,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $cases;
    }
}
