<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\PayloadNodeFactory;

class StructuralTest extends \TestCase
{
    /**
     * @throws NodalFlowException
     */
    public function testFlowReuseSelf()
    {
        $this->expectException(NodalFlowException::class);
        $flow = new NodalFlow;
        $flow->add(new BranchNode($flow, true));
    }

    /**
     * @throws NodalFlowException
     */
    public function testFlowReuse1()
    {
        $this->expectException(NodalFlowException::class);
        $rootFlow   = new NodalFlow;
        $branchFlow = new NodalFlow;
        $rootFlow->add(new BranchNode($branchFlow, true));
        $rootFlow->add(new BranchNode($branchFlow, true));
    }

    /**
     * @throws NodalFlowException
     */
    public function testFlowReuse2()
    {
        $this->expectException(NodalFlowException::class);
        $rootFlow    = new NodalFlow;
        $branchFlow1 = new NodalFlow;
        $branchFlow2 = new NodalFlow;
        $rootFlow->add(new BranchNode($branchFlow1, true));
        $rootFlow->add(new BranchNode($branchFlow2, true));
        $branchFlow2->add(new BranchNode($rootFlow, true));
    }

    /**
     * @throws NodalFlowException
     */
    public function testNodeReuse()
    {
        $this->expectException(NodalFlowException::class);
        $flow = new NodalFlow;
        $node = PayloadNodeFactory::create(function ($record) {
            return $record;
        }, true, false);

        $flow->add($node);
        $flow->add($node);
    }

    /**
     * @throws NodalFlowException
     */
    public function testNodeReuseInBranch1()
    {
        $this->expectException(NodalFlowException::class);
        $flow       = new NodalFlow;
        $branchFlow = new NodalFlow;
        $node       = PayloadNodeFactory::create(function ($record) {
            return $record;
        }, true, false);

        $flow->add($node);
        $branchFlow->add($node);
    }

    /**
     * @throws NodalFlowException
     */
    public function testNodeReuseInBranch2()
    {
        $this->expectException(NodalFlowException::class);
        $flow       = new NodalFlow;
        $branchFlow = new NodalFlow;
        $node       = PayloadNodeFactory::create(function ($record) {
            return $record;
        }, true, false);

        $flow->add($node)->add(new BranchNode($branchFlow, true));
        $branchFlow->add($node);
    }

    /**
     * @throws NodalFlowException
     */
    public function testNodeReuseInBranch3()
    {
        $this->expectException(NodalFlowException::class);
        $flow        = new NodalFlow;
        $branchFlow1 = new NodalFlow;
        $branchFlow2 = new NodalFlow;
        $node        = PayloadNodeFactory::create(function ($record) {
            return $record;
        }, true, false);

        $flow->add($node)->add(new BranchNode($branchFlow1, true));
        $branchFlow1->add(new BranchNode($branchFlow2, true));
        $branchFlow2->add($node);
    }
}
