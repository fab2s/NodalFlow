<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\Events\FlowEventInterface;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Abstract Class FlowInterruptAbstract
 */
abstract class FlowInterruptAbstract extends FlowEventAbstract
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
     * @var FlowRegistryInterface
     */
    protected $registry;

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
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function breakFlow(InterrupterInterface $flowInterrupt = null): FlowInterface
    {
        return $this->interruptFlow(InterrupterInterface::TYPE_BREAK, $flowInterrupt);
    }

    /**
     * Continue the flow's execution, conceptually similar to continuing
     * a regular loop
     *
     * @param InterrupterInterface|null $flowInterrupt
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function continueFlow(InterrupterInterface $flowInterrupt = null): FlowInterface
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
    public function interruptFlow(string $interruptType, InterrupterInterface $flowInterrupt = null): FlowInterface
    {
        $node = isset($this->nodes[$this->nodeIdx]) ? $this->nodes[$this->nodeIdx] : null;
        switch ($interruptType) {
            case InterrupterInterface::TYPE_CONTINUE:
                $this->continue = true;
                $this->flowMap->incrementFlow('num_continue');
                $this->triggerEvent(FlowEventInterface::FLOW_CONTINUE, $node);
                break;
            case InterrupterInterface::TYPE_BREAK:
                $this->flowStatus = new FlowStatus(FlowStatus::FLOW_DIRTY);
                $this->break      = true;
                $this->flowMap->incrementFlow('num_break');
                $this->triggerEvent(FlowEventInterface::FLOW_BREAK, $node);
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
     * - A Node Id to target
     * - true to interrupt every upstream nodes
     *     in this Flow
     * - false to only interrupt up to the first
     *     upstream Traversable in this Flow
     *
     * @param null|string|bool $interruptNodeId
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function setInterruptNodeId($interruptNodeId): FlowInterface
    {
        if ($interruptNodeId !== null && !is_bool($interruptNodeId) && !$this->registry->getNode($interruptNodeId)) {
            throw new NodalFlowException('Targeted Node not found in target Flow for Interruption', 1, null, [
                'targetFlow' => $this->getId(),
                'targetNode' => $interruptNodeId,
            ]);
        }

        $this->interruptNodeId = $interruptNodeId;

        return $this;
    }

    /**
     * @param NodeInterface $node
     *
     * @return bool
     */
    protected function interruptNode(NodeInterface $node): bool
    {
        // if we have an interruptNodeId, bubble up until we match a node
        // else stop propagation
        return $this->interruptNodeId ? $this->interruptNodeId !== $node->getId() : false;
    }
}
