<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\PayloadNodeFactory;

/**
 * Class SendToTest
 */
class SendToTest extends \TestCase
{
    /**
     * @throws NodalFlowException
     */
    public function testSendFlow()
    {
        $noOpNode1 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $node1Id   = $noOpNode1->getId();
        $noOpNode2 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $node2Id   = $noOpNode2->getId();
        $noOpNode3 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $node3Id   = $noOpNode3->getId();

        $flow = (new NodalFlow)->add($noOpNode1)
            ->add($noOpNode2)
            ->add($noOpNode3);

        $this->assertSame(42, $flow->sendTo($node2Id, 42));
        $nodeMap   = $flow->getNodeMap();
        $flowStats = $flow->getStats();

        $this->assertSame(1, $nodeMap[$node2Id]['num_exec']);
        $this->assertSame(1, $nodeMap[$node3Id]['num_exec']);
        $this->assertSame(0, $nodeMap[$node1Id]['num_exec']);
        $this->assertSame(0, $flowStats['num_exec']);
    }

    /**
     * @throws NodalFlowException
     * @throws Exception
     */
    public function testSendNode()
    {
        $flow   = new NodalFlow;
        $sendTo = function ($record) use ($flow) {
            $nodes = $flow->getNodes();
            $node1 = $nodes[0];
            $this->assertSame(1337, $node1->sendTo($flow->getId(), $nodes[2]->getId(), 1337));

            return $record;
        };

        $noOpNode1 = PayloadNodeFactory::create($sendTo, true, false);
        $node1Id   = $noOpNode1->getId();
        $noOpNode2 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $node2Id   = $noOpNode2->getId();
        $noOpNode3 = PayloadNodeFactory::create($this->getNoOpClosure(), true, false);
        $node3Id   = $noOpNode3->getId();

        $this->assertSame(42, $flow->add($noOpNode1)
            ->add($noOpNode2)
            ->add($noOpNode3)
            ->exec(42));

        $nodeMap   = $flow->getNodeMap();
        $flowStats = $flow->getStats();

        $this->assertSame(1, $nodeMap[$node2Id]['num_exec']);
        $this->assertSame(2, $nodeMap[$node3Id]['num_exec']);
        $this->assertSame(1, $nodeMap[$node1Id]['num_exec']);
        $this->assertSame(1, $flowStats['num_exec']);

        $this->assertSame(42, $sendTo(42));

        $nodeMap   = $flow->getNodeMap();
        $flowStats = $flow->getStats();

        $this->assertSame(1, $nodeMap[$node2Id]['num_exec']);
        $this->assertSame(3, $nodeMap[$node3Id]['num_exec']);
        $this->assertSame(1, $nodeMap[$node1Id]['num_exec']);
        $this->assertSame(1, $flowStats['num_exec']);
    }
}
