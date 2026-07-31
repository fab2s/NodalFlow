<?php

/*
 * This file is part of NodalFlow
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Callbacks\CallbackAbstract;
use fab2s\NodalFlow\Events\CallbackWrapper;
use fab2s\NodalFlow\Events\FlowEvent;
use fab2s\NodalFlow\Flows\FlowMapInterface;
use fab2s\NodalFlow\Flows\FlowStatus;
use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\AggregateNode;
use fab2s\NodalFlow\Nodes\BranchNode;
use fab2s\NodalFlow\Nodes\CallableInterruptNode;
use fab2s\NodalFlow\Nodes\CallableNode;
use fab2s\NodalFlow\Nodes\ClosureNode;
use fab2s\NodalFlow\PayloadNodeFactory;

/**
 * Covers the smaller citizens on their own, the Flow scenarios
 * are covered by the Flow* test cases
 */
class UnitTest extends TestCase
{
    /**
     * @throws NodalFlowException
     */
    public function test_payload_node_factory_string_and_array_payloads()
    {
        $this->assertInstanceOf(CallableNode::class, PayloadNodeFactory::create('trim', true));
        $this->assertInstanceOf(CallableNode::class, PayloadNodeFactory::create([new DummyClass, 'dummyMethod'], true));
    }

    /**
     * @throws NodalFlowException
     */
    public function test_payload_node_factory_closure_and_flow_payloads()
    {
        $this->assertInstanceOf(ClosureNode::class, PayloadNodeFactory::create(function ($param = null) {
            return $param;
        }, true));

        $this->assertInstanceOf(BranchNode::class, PayloadNodeFactory::create(new NodalFlow, true));
    }

    /**
     * @throws NodalFlowException
     */
    public function test_payload_node_factory_rejects_unsupported_payload()
    {
        $this->expectException(NodalFlowException::class);
        PayloadNodeFactory::create(new stdClass, true);
    }

    public function test_flow_exposes_its_map_and_id()
    {
        $flow = new NodalFlow;

        $this->assertInstanceOf(FlowMapInterface::class, $flow->getFlowMap());
        $this->assertSame($flow->getId(), $flow->getFlowId());
    }

    /**
     * @throws NodalFlowException
     */
    public function test_cloning_resets_ids_and_detaches_nodes()
    {
        $flow = new NodalFlow;
        $node = new CallableNode('trim', true);
        $flow->add($node);

        $this->assertSame($flow, $node->getCarrier());

        // cloning a node detaches it from its carrier
        $nodeClone = clone $node;
        $this->assertNull($nodeClone->getCarrier());
        $this->assertNotSame($node->getId(), $nodeClone->getId());

        // cloning a flow only resets its id
        $flowClone = clone $flow;
        $this->assertNotSame($flow->getId(), $flowClone->getId());
    }

    /**
     * @throws NodalFlowException
     */
    public function test_callable_interrupt_node_is_instantiable()
    {
        $node = new CallableInterruptNode(function () {
            return true;
        });

        $this->assertNotEmpty($node->getId());
    }

    public function test_callback_abstract_defaults_are_no_ops()
    {
        $flow     = new NodalFlow;
        $callback = new class extends CallbackAbstract {};

        // the default implementations do nothing, they are there to be overridden
        $this->assertNull($callback->start($flow));
        $this->assertNull($callback->success($flow));
        $this->assertNull($callback->fail($flow));
    }

    /**
     * @throws NodalFlowException
     */
    public function test_callback_wrapper_forwards_every_event()
    {
        $flow     = new NodalFlow;
        $callback = new DummyCallback;
        $wrapper  = new CallbackWrapper($callback);
        $event    = new FlowEvent($flow);

        $wrapper->start($event);
        $wrapper->success($event);
        $wrapper->fail($event);

        $this->assertTrue($callback->hasStarted());
        $this->assertTrue($callback->hasSucceeded());
        $this->assertTrue($callback->hasFailed());
    }

    /**
     * @throws NodalFlowException
     */
    public function test_flow_status_reports_each_state()
    {
        $clean = new FlowStatus(FlowStatus::FLOW_CLEAN);
        $this->assertTrue($clean->isClean());
        $this->assertFalse($clean->isDirty());
        $this->assertSame(FlowStatus::FLOW_CLEAN, (string) $clean);
        $this->assertNull($clean->getException());

        $dirty = new FlowStatus(FlowStatus::FLOW_DIRTY);
        $this->assertTrue($dirty->isDirty());

        $exception = new NodalFlowException('broken');
        $failed    = new FlowStatus(FlowStatus::FLOW_EXCEPTION, $exception);
        $this->assertTrue($failed->isException());
        $this->assertSame($exception, $failed->getException());
    }

    /**
     * @throws NodalFlowException
     */
    public function test_flow_status_rejects_unknown_status()
    {
        $this->expectException(NodalFlowException::class);
        new FlowStatus('not a status');
    }

    /**
     * @throws NodalFlowException
     */
    public function test_aggregate_node_aggregates_traversables()
    {
        $aggregate = new AggregateNode(true);
        $this->assertTrue($aggregate->isTraversable());

        $this->assertSame($aggregate, $aggregate->addTraversable(new CallableNode(function ($param = null) {
            yield $param;
        }, true, true)));
    }

    /**
     * @throws NodalFlowException
     */
    public function test_aggregate_node_cannot_be_executed()
    {
        $this->expectException(NodalFlowException::class);
        (new AggregateNode(true))->exec();
    }
}
