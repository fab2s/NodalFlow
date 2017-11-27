<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\Callbacks\CallbackInterface;
use fab2s\NodalFlow\NodalFlowException;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Abstract Class FlowAbstract
 */
abstract class FlowAbstract implements FlowInterface
{
    use FlowIdTrait;

    /**
     * The parent Flow, only set when branched
     *
     * @var FlowInterface
     */
    public $parent;

    /**
     * Current Flow Status
     *
     * @var FlowStatusInterface
     */
    protected $flowStatus;

    /**
     * The underlying node structure
     *
     * @var NodeInterface[]
     */
    protected $nodes = [];

    /**
     * @var FlowMapInterface
     */
    protected $flowMap;

    /**
     * @var FlowRegistryInterface
     */
    protected $registry;

    /**
     * The current registered Callback class if any
     *
     * @var CallbackInterface|null
     */
    protected $callBack;

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
     * Progress modulo to apply
     * Set to x if you want to trigger
     * progress every x iterations in flow
     *
     * @var int
     */
    protected $progressMod = 1024;

    /**
     * @var string|bool
     */
    protected $interruptNodeId;

    /**
     * Get the stats array with latest Node stats
     *
     * @return array
     */
    public function getStats()
    {
        return $this->flowMap->getStats();
    }

    /**
     * Get the stats array with latest Node stats
     *
     * @return FlowMapInterface
     */
    public function getFlowMap()
    {
        return $this->flowMap;
    }

    /**
     * Get the Node array
     *
     * @return NodeInterface[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Get/Generate Node Map
     *
     * @return array
     */
    public function getNodeMap()
    {
        return $this->flowMap->getNodeMap();
    }

    /**
     * Get current $progressMod
     *
     * @return int
     */
    public function getProgressMod()
    {
        return $this->progressMod;
    }

    /**
     * The Flow status can either indicate be:
     *      - clean (isClean()): everything went well
     *      - dirty (isDirty()): one Node broke the flow
     *      - exception (isException()): an exception was raised during the flow
     *
     * @return FlowStatusInterface
     */
    public function getFlowStatus()
    {
        return $this->flowStatus;
    }

    /**
     * getId() alias for backward compatibility
     *
     * @deprecated use `getId` instead
     *
     * @return string
     */
    public function getFlowId()
    {
        return $this->getId();
    }

    /**
     * Set parent Flow, happens only when branched
     *
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setParent(FlowInterface $flow)
    {
        $this->parent = $flow;

        return $this;
    }

    /**
     * Get eventual parent Flow
     *
     * @return FlowInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Tells if this flow has a parent
     *
     * @return bool
     */
    public function hasParent()
    {
        return !empty($this->parent);
    }

    /**
     * Define the progress modulo, Progress Callback will be
     * triggered upon each iteration in the flow modulo $progressMod
     *
     * @param int $progressMod
     *
     * @return $this
     */
    public function setProgressMod($progressMod)
    {
        $this->progressMod = max(1, (int) $progressMod);

        return $this;
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
     * Register callback class
     *
     * @param CallbackInterface $callBack
     *
     * @return $this
     */
    public function setCallBack(CallbackInterface $callBack)
    {
        $this->callBack = $callBack;

        return $this;
    }

    /**
     * Get this Flow's root Flow
     *
     * @param FlowInterface $flow Root Flow, or self if root flow
     *
     * @return FlowInterface
     */
    public function getRootFlow(FlowInterface $flow)
    {
        while ($flow->hasParent()) {
            $flow = $flow->getParent();
        }

        return $flow;
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

    /**
     * KISS helper to trigger Callback slots
     *
     * @param string             $which
     * @param null|NodeInterface $node
     *
     * @return $this
     */
    protected function triggerCallback($which, NodeInterface $node = null)
    {
        if (null !== $this->callBack) {
            $this->callBack->$which($this, $node);
        }

        return $this;
    }
}
