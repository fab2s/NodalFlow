<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

class FLowTest extends \TestCase
{
    public function flowCasesPovider()
    {
        $validateTraversableNode = function (NodeInterface $node, $param = null) {
            static $invocations = 1;
            $result             = true;
            $i                  = max(1, (int) $param);
            //echo "validateTraversableNode invocations: $invocations, param: $param\n";
            ++$invocations;
            foreach ($node->getTraversable() as $value) {
                $result = $result && ($i === $value);
                //echo "validateTraversableNode value: $value, i: $i, result:\n";
                //var_dump($result);
                ++$i;
            }

            //echo "validateTraversableNode result:\n";

            return $result ? true : false;
        };

        $traversablePayload = function ($param = null) {
            static $invocations = 0;
            //echo "traversablePayload invocations: $invocations, param :$param\n";
            ++$invocations;
            $param = max(1, (int) $param);
            $limit = $param + 5;
            for ($i = $param; $i < $limit; ++$i) {
                echo "traversablePayload invocations: $invocations, param :$param, yield: $i\n";
                yield $i;
            }
        };
        $traversablePayload2 = function ($param = null) {
            static $invocations = 0;
            //echo "traversablePayload invocations: $invocations, param :$param\n";
            ++$invocations;
            $param = max(1, (int) $param);
            $limit = $param + 5;
            for ($i = $param; $i < $limit; ++$i) {
                echo "**traversablePayload2 invocations: $invocations, param :$param, yield: $i\n";
                yield $i;
            }
        };

        $validateExecNode = function (NodeInterface $node, $param = null) {
            static $invocations = 0;
            ++$invocations;
            $param = max(0, (int) $param);
            //echo "validateExecNode invocations: $invocations, param: $param, value: " . ($param + 1) . "\n";
            return $param + 1;
        };

        $validateExecNode = null;

        $execPayload = function ($param = null) {
            static $invocations = 0;
            ++$invocations;
            $param = max(0, (int) $param);
            echo "\texecPayload invocations: $invocations, param: $param, value: " . ($param + 1) . "\n";

            return $param + 1;
        };

        $execPayload2 = function ($param = null) {
            static $invocations = 0;
            ++$invocations;
            $param = max(0, (int) $param);
            echo "\t**execPayload2 invocations: $invocations, param: $param, value: " . ($param + 1) . "\n";

            return $param + 1;
        };

        $testNodes = [
            [
                'nodeName' => 'CallableNode',
                'payload'  => $traversablePayload,
             //   'validate' => $validateTraversableNode,
                'isATraversable' => true,
            ],
            [
                'nodeName' => 'CallableNode',
                'payload'  => $execPayload,
              //  'validate' => $validateExecNode,
            ],
            [
                'nodeName' => 'CallableNode',
                'payload'  => $execPayload2,
              //  'validate' => $validateExecNode,
            ],
            [
                'nodeName' => 'CallableNode',
                'payload'  => $traversablePayload2,
               // 'validate' => $validateTraversableNode,
                'isATraversable' => true,
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

        $cases = [
            'root' => [
                'flowName'     => 'CallableFlow',
                'nodes'        => $testNodes,
                'expectations' => [
                    [
                        'isAReturningVal' => [true, true, true, true],
                        'cases'           => [
                            [
                                'param'    => null,
                                'expected' => 11,
                            ],
                            [
                                'param'    => 2,
                                'expected' => 12,
                            ],
                        ],
                    ],
                    /*[
                        'isAReturningVal' => [false, true, true, true],
                        'cases' => [
                            [
                                'param' => null,
                                'expected' => 6,
                            ],
                            [
                                'param' => 2,
                                'expected' => 7,
                            ],
                        ],
                    ],*/
                    /*[
                        'isAReturningVal' => [true, false, true, true],
                        'cases' => [
                            [
                                'param' => null,
                                'expected' => 10,
                            ],
                            [
                                'param' => 2,
                                'expected' => 11,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [true, true, false, true],
                        'cases' => [
                            [
                                'param' => null,
                                'expected' => 10,
                            ],
                            [
                                'param' => 2,
                                'expected' => 11,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [true, true, false, false],
                        'cases' => [
                            [
                                'param' => null,
                                'expected' => 6,
                            ],
                            [
                                'param' => 2,
                                'expected' => 7,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true, false, false],
                        'cases' => [
                            [
                                'param' => null,
                                'expected' => 5,
                            ],
                            [
                                'param' => 2,
                                'expected' => 7,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false, true, false],
                        'cases' => [
                            [
                                'param' => null,
                                'expected' => 5,
                            ],
                            [
                                'param' => 2,
                                'expected' => 7,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, false, false, false],
                        'cases' => [
                            [
                                'param' => null,
                                'expected' => null,
                            ],
                            [
                                'param' => 2,
                                'expected' => 2,
                            ],
                        ],
                    ],
                    [
                        'isAReturningVal' => [false, true, true, false],
                        'cases' => [
                            [
                                'param' => null,
                                'expected' => 10,
                            ],
                            [
                                'param' => 2,
                                'expected' => 12,
                            ],
                        ],
                    ],*/
                ],
            ],
        ];

        $cases['branchStartExec'] = $cases['root'];
        $branchFlow               = $this->getFlow('CallableFlow');
        $branchNodes              = $testNodes;
        unset($branchNodes[0], $branchNodes[1]);
        foreach ($branchNodes as $branchNode) {
            $node = new $branchNode['nodeClass']($branchNode['payload'], $branchNode['isAReturningVal'], $branchNode['isATraversable']);
            $branchFlow->addNode($node);
        }

        $branchNodes = [
            array_replace($nodeDefault, [
                'nodeName'       => 'BranchNode',
                'nodeClass'      => $this->nodes['BranchNode'],
                'payload'        => $branchFlow,
                'validate'       => null,
                'isATraversable' => false,
            ]),
            $testNodes[2],
            $testNodes[3],
        ];

        $cases['branchStartExec']['nodes']        = $branchNodes;
        $cases['branchStartExec']['expectations'] = [
            [
                'isAReturningVal' => [false, true, true, true, true],
                'cases'           => [
                    [
                        'param'    => null,
                        'expected' => 11,
                    ],
                    [
                        'param'    => 2,
                        'expected' => 12,
                    ],
                ],
            ],
            [
                'isAReturningVal' => [false, false, false, false, false],
                'cases'           => [
                    [
                        'param'    => null,
                        'expected' => null,
                    ],
                    [
                        'param'    => 2,
                        'expected' => 2,
                    ],
                ],
            ],
            [
                'isAReturningVal' => [true, false, false, false, false],
                'cases'           => [
                    [
                        'param'    => null,
                        'expected' => 11,
                    ],
                    [
                        'param'    => 2,
                        'expected' => 12,
                    ],
                ],
            ],
        ];

        $cases['branchEndExec'] = $cases['root'];
        $branchFlow             = $this->getFlow('CallableFlow');
        $branchNodes            = $testNodes;
        unset($branchNodes[2], $branchNodes[3]);
        $branchNodes = array_values($branchNodes);
        foreach ($branchNodes as $branchNode) {
            $node = new $branchNode['nodeClass']($branchNode['payload'], $branchNode['isAReturningVal'], $branchNode['isATraversable']);
            $branchFlow->addNode($node);
        }

        $branchNodes = [
            $testNodes[0],
            $testNodes[1],
            array_replace($nodeDefault, [
                'nodeName'       => 'BranchNode',
                'nodeClass'      => $this->nodes['BranchNode'],
                'payload'        => $branchFlow,
                'validate'       => null,
                'isATraversable' => false,
            ]),
        ];

        //var_dump(count($branchNodes)); die;
        $cases['branchEndExec']['nodes']        = $branchNodes;
        $cases['branchEndExec']['expectations'] = [
            [
                'isAReturningVal' => [true, true, true],
                'cases'           => [
                    [
                        'param'    => null,
                        'expected' => 11,
                    ],
                    [
                        'param'    => 2,
                        'expected' => 12,
                    ],
                ],
            ],
            [
                'isAReturningVal' => [true, true, false],
                'cases'           => [
                    [
                        'param'    => null,
                        'expected' => 6,
                    ],
                    [
                        'param'    => 2,
                        'expected' => 7,
                    ],
                ],
            ],
            [
                'isAReturningVal' => [false, true, false],
                'cases'           => [
                    [
                        'param'    => null,
                        'expected' => 5,
                    ],
                    [
                        'param'    => 2,
                        'expected' => 7,
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
                        'param'    => 2,
                        'expected' => 2,
                    ],
                ],
            ],
        ];

        unset($cases['branchStartExec'], $cases['branchEndExec']);
        $entryDefault = [
            'flow'     => null,
            'nodes'    => [],
            'param'    => null,
            'expected' => null,
        ];

        $result = [];
        foreach ($cases as $flowName => $case) {
            $flowName = $case['flowName'];
            foreach ($case['expectations'] as $expectation) {
                foreach ($expectation['cases'] as $expectationCase) {
                    $entry         = $entryDefault;
                    $entry['flow'] = $this->getFlow($flowName);
                    foreach ($case['nodes'] as $idx => $nodeSetup) {
                        $nodeSetup['isAReturningVal'] = $expectation['isAReturningVal'][$idx];
                        //unset($nodeSetup['payload'], $nodeSetup['validate']);
                        $entry['nodes'][] = $nodeSetup;
                    }

                    $entry['param']    = $expectationCase['param'];
                    $entry['expected'] = $expectationCase['expected'];
                    $result[]          = $entry;
                }
            }
        }
//var_dump(count($result));die;
        return $result;
    }

    /**
     * @dataProvider flowCasesPovider
     *
     * @param string $flowClass
     * @param mixed  $param
     * @param mixed  $expected
     */
    public function testFlows(FlowInterface $flow, array $nodes, $param, $expected)
    {
        foreach ($nodes as $nodeSetup) {
            $node = new $nodeSetup['nodeClass']($nodeSetup['payload'], $nodeSetup['isAReturningVal'], $nodeSetup['isATraversable']);
            $this->validateNode($node, $nodeSetup['isAReturningVal'], $nodeSetup['isATraversable'], $nodeSetup['validate']);
            $flow->addNode($node);
        }

        $result  = $flow->exec($param);
        $nodeMap = $flow->getNodeMap();
        var_dump($nodeMap);
        $this->assertSame($expected, $result);

        // executing a flow should be repeatable
        //$this->assertSame($expected, $flow->exec($param));
    }
}
