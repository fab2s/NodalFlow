<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow;

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Flows\InterrupterInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;
use InvalidArgumentException;

/**
 * Class Interrupter
 */
class Interrupter implements InterrupterInterface
{
    /**
     * @var string
     */
    protected $flowTarget;

    /**
     * @var string
     */
    protected $nodeTarget;

    /**
     * interrupt type
     *
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $types = [
        InterrupterInterface::TYPE_CONTINUE => 1,
        InterrupterInterface::TYPE_BREAK    => 1,
    ];

    /**
     * Interrupter constructor.
     *
     * @param null|string|FlowInterface $flowTarget , target up to Targeted Flow id or InterrupterInterface::TARGET_TOP to interrupt every parent
     * @param null|string|NodeInterface $nodeTarget
     * @param null|string               $type
     */
    public function __construct($flowTarget = null, $nodeTarget = null, ?string $type = null)
    {
        $this->flowTarget = $flowTarget instanceof FlowInterface ? $flowTarget->getId() : $flowTarget;
        $this->nodeTarget = $nodeTarget instanceof NodeInterface ? $nodeTarget->getId() : $nodeTarget;

        if ($type !== null) {
            $this->setType($type);
        }
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function setType(string $type): InterrupterInterface
    {
        if (!isset($this->types[$type])) {
            throw new InvalidArgumentException('type must be one of:' . implode(', ', array_keys($this->types)));
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Trigger the Interrupt of each ancestor Flows up to a specific one, the root one
     * or none if :
     * - No FlowInterrupt is set
     * - FlowInterrupt is set at InterrupterInterface::TARGET_SELF
     * - FlowInterrupt is set at this Flow's Id
     * - FlowInterrupt is set as InterrupterInterface::TARGET_TOP and this has no parent
     *
     * Throw an exception if we reach the top after bubbling and FlowInterrupt != InterrupterInterface::TARGET_TOP
     *
     * @param FlowInterface $flow
     *
     * @throws NodalFlowException
     *
     * @return FlowInterface
     */
    public function propagate(FlowInterface $flow): FlowInterface
    {
        // evacuate edge cases
        if ($this->isEdgeInterruptCase($flow)) {
            // if anything had to be done, it was done first hand already
            // just make sure we propagate the eventual nodeTarget
            return $flow->setInterruptNodeId($this->nodeTarget);
        }

        $InterrupterFlowId = $flow->getId();
        if (!$this->type) {
            throw new NodalFlowException('No interrupt type set', 1, null, [
                'InterrupterFlowId' => $InterrupterFlowId,
            ]);
        }

        do {
            $lastFlowId = $flow->getId();
            if ($this->flowTarget === $lastFlowId) {
                // interrupting $flow
                return $flow->setInterruptNodeId($this->nodeTarget)->interruptFlow($this->type);
            }

            // Set interruptNodeId to true in order to make sure
            // we do not match any nodes in this flow (as it is not the target)
            $flow->setInterruptNodeId(true)->interruptFlow($this->type);
        } while ($flow->hasParent() && $flow = $flow->getParent());

        if ($this->flowTarget !== InterrupterInterface::TARGET_TOP) {
            throw new NodalFlowException('Interruption target missed', 1, null, [
                'interruptAt'       => $this->flowTarget,
                'InterrupterFlowId' => $InterrupterFlowId,
                'lastFlowId'        => $lastFlowId,
            ]);
        }

        return $flow;
    }

    /**
     * @param NodeInterface|null $node
     *
     * @return bool
     */
    public function interruptNode(NodeInterface $node = null): bool
    {
        return $node ? $this->nodeTarget === $node->getId() : false;
    }

    /**
     * @param FlowInterface $flow
     *
     * @return bool
     */
    protected function isEdgeInterruptCase(FlowInterface $flow): bool
    {
        return !$this->flowTarget ||
            (
                // asked to stop right here
                $this->flowTarget === InterrupterInterface::TARGET_SELF ||
                $this->flowTarget === $flow->getId()                    ||
                (
                    // target root when this Flow is root already
                    $this->flowTarget === InterrupterInterface::TARGET_TOP &&
                    !$flow->hasParent()
                )
            );
    }
}
