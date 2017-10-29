<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\InterrupterInterface;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Interface FlowInterface
 */
interface FlowInterface
{
    /**
     * Return the Flow id as set during instantiation
     *
     * @return string
     */
    public function getId();

    /**
     * Adds a Node to the Flow
     *
     * @param NodeInterface $node
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function add(NodeInterface $node);

    /**
     * Execute the Flow
     *
     * @param mixed $param The first param to apply, mostly
     *                     useful for branches when values have
     *                     already been generated
     *
     * @return mixed the last value returned in the chain
     */
    public function exec($param = null);

    /**
     * Rewinds the Flow
     *
     * @return $this
     */
    public function rewind();

    /**
     * Used to set the eventual Node Target of an Interrupt signal
     * set to :
     * - A node hash to target
     * - true to interrupt every upstream nodes
     *     in this Flow
     * - false to only interrupt up to the first
     *     upstream Traversable in this Flow
     *
     * @param string|bool $interruptNodeId
     *
     * @return $this
     */
    public function setInterruptNodeId($interruptNodeId);

    /**
     * Set parent Flow, happens only when branched
     *
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setParent(FlowInterface $flow);

    /**
     * Get eventual parent Flow
     *
     * @return FlowInterface
     */
    public function getParent();

    /**
     * Tells if this flow has a parent
     *
     * @return bool
     */
    public function hasParent();

    /**
     * The Flow status can either indicate be:
     *      - clean (isClean()): everything went well
     *      - dirty (isDirty()): one Node broke the flow
     *      - exception (isException()): an exception was raised during the flow
     *
     * @return FlowStatusInterface
     */
    public function getFlowStatus();

    /**
     * Get the underlying node array
     *
     * @return NodeInterface[]
     */
    public function getNodes();

    /**
     * Nodes may call breakFlow() on their carrier to
     * break the flow
     *
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @return $this
     */
    public function breakFlow(InterrupterInterface $flowInterrupt = null);

    /**
     * Nodes may call breakFlow() on their carrier to
     * skip the rest of the nodes and continue with next
     * value from the first upstream traversable if any
     *
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @return $this
     */
    public function continueFlow(InterrupterInterface $flowInterrupt = null);

    /**
     * @param string                    $interruptType
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @return $this
     */
    public function interruptFlow($interruptType, InterrupterInterface $flowInterrupt = null);
}
