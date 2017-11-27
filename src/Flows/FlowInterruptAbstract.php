<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Abstract Class FlowInterruptAbstract
 */
abstract class FlowInterruptAbstract implements FlowInterface
{
    /**
     * Continue flag
     *
     * @var bool
     */
    protected $continue = false;

    /**
     * Break Flag
     *
     * @var bool
     */
    protected $break = false;

    /**
     * @var string|bool
     */
    protected $interruptNodeId;

    /**
     * @var FlowMapInterface
     */
    protected $flowMap;

    /**
     * Current Flow Status
     *
     * @var FlowStatusInterface
     */
    protected $flowStatus;

    /**
     * Break the flow's execution, conceptually similar to breaking
     * a regular loop
     *
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @return $this
     */
    public function breakFlow(InterrupterInterface $flowInterrupt = null)
    {
        return $this->interruptFlow(InterrupterInterface::TYPE_BREAK, $flowInterrupt);
    }

    /**
     * Continue the flow's execution, conceptually similar to continuing
     * a regular loop
     *
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @return $this
     */
    public function continueFlow(InterrupterInterface $flowInterrupt = null)
    {
        return $this->interruptFlow(InterrupterInterface::TYPE_CONTINUE, $flowInterrupt);
    }

    /**
     * @param string                    $interruptType
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function interruptFlow($interruptType, InterrupterInterface $flowInterrupt = null)
    {
        switch ($interruptType) {
            case InterrupterInterface::TYPE_CONTINUE:
                $this->continue = true;
                $this->flowMap->incrementFlow('num_continue');
                break;
            case InterrupterInterface::TYPE_BREAK:
                $this->flowStatus = new FlowStatus(FlowStatus::FLOW_DIRTY);
                $this->break      = true;
                $this->flowMap->incrementFlow('num_break');
                break;
            default:
                throw new NodalFlowException('FlowInterrupt Type missing');
        }

        if ($flowInterrupt) {
            $flowInterrupt->setType($interruptType)->propagate($this);
        }

        return $this;
    }

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
    public function setInterruptNodeId($interruptNodeId)
    {
        $this->interruptNodeId = $interruptNodeId;

        return $this;
    }

    /**
     * @param NodeInterface $node
     *
     * @return bool
     */
    protected function interruptNode(NodeInterface $node)
    {
        // if we have an interruptNodeId, bubble up until we match a node
        // else stop propagation
        return $this->interruptNodeId ? $this->interruptNodeId !== $node->getId() : false;
    }
}
