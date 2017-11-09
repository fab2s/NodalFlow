<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow;

use fab2s\NodalFlow\Flows\FlowInterface;
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
    public function __construct($flowTarget = null, $nodeTarget = null, $type = null)
    {
        if ($flowTarget instanceof FlowInterface) {
            $flowTarget = $flowTarget->getId();
        }

        $this->flowTarget = $flowTarget;

        if ($nodeTarget instanceof NodeInterface) {
            $nodeTarget = $nodeTarget->getNodeHash();
        }

        $this->nodeTarget = $nodeTarget;

        if ($type !== null) {
            $this->setType($type);
        }
    }

    /**
     * @return string
     */
    public function getType()
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
    public function setType($type)
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
    public function propagate(FlowInterface $flow)
    {
        $InterrupterFlowId = $flow->getId();
        // evacuate border cases
        if (
            // no target = InterrupterInterface::TARGET_SELF
            !$this->flowTarget ||
            (
                // asked to stop right here
                $this->flowTarget === InterrupterInterface::TARGET_SELF ||
                $this->flowTarget === $InterrupterFlowId ||
                (
                    // target root when this Flow is root already
                    $this->flowTarget === InterrupterInterface::TARGET_TOP &&
                    !$flow->hasParent()
                )
            )
        ) {
            // if anything had to be done, it was done first hand already
            // just make sure we propagate the eventual nodeTarget
            return $flow->setInterruptNodeId($this->nodeTarget);
        }

        if (!$this->type) {
            throw new NodalFlowException('No interrupt type set', 1, null, [
                'InterrupterFlowId' => $InterrupterFlowId,
            ]);
        }

        do {
            // keep this for later
            $lastFlowId = $flow->getId();
            if ($this->flowTarget === $lastFlowId) {
                // interrupting $flow
                return $flow->setInterruptNodeId($this->nodeTarget)->interruptFlow($this->type);
            }

            // by not bubbling the FlowInterrupt we make sure that each
            // eventual upstream Flow will only interrupt themselves, the
            // actual management is performed by this Flow which first
            // received the interrupt signal
            // also set interruptNodeId to true in order to make sure
            // we do not match any nodes in this flow (as it is not the target)
            $flow->setInterruptNodeId(true)->interruptFlow($this->type);
        } while ($flow = $flow->getParent());

        // Here the target was either InterrupterInterface::TARGET_TOP or
        // anything else that did not match any of the Flow ancestor's
        // ids.
        // `$lastFlowId` is the root Flow id, which may as well be this
        // very Flow.
        // This implies that the only legit value for `$interruptAt` is
        // `InterrupterInterface::TARGET_TOP` since :
        // - `$interruptAt` matching this Flow id is caught above
        //    when triggering `isReached` the first time
        // - `$interruptAt` being null is also caught above
        // - `$interruptAt`  matching `InterrupterInterface::TARGET_TOP`
        //    with this having no parent is also caught above
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
    public function interruptNode(NodeInterface $node = null)
    {
        return $node ? $this->nodeTarget === $node->getNodeHash() : false;
    }
}
