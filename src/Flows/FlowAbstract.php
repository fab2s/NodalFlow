<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\Callbacks\CallbackInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Abstract Class FlowAbstract
 */
abstract class FlowAbstract extends FlowAncestryAbstract
{
    use FlowIdTrait;

    /**
     * The underlying node structure
     *
     * @var NodeInterface[]
     */
    protected $nodes = [];

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
     * Progress modulo to apply
     * Set to x if you want to trigger
     * progress every x iterations in flow
     *
     * @var int
     */
    protected $progressMod = 1024;

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
