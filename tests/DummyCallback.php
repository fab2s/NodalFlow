<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

use fab2s\NodalFlow\Callbacks\CallbackAbstract;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Class DummyCallback
 */
class DummyCallback extends CallbackAbstract
{
    /**
     * @var bool
     */
    protected $hasStarted = false;

    /**
     * @var bool
     */
    protected $hasSucceeded = false;

    /**
     * @var bool
     */
    protected $hasFailed = false;

    /**
     * @var int
     */
    protected $numProgress = 0;

    /**
     * Triggered when a Flow starts
     *
     * @param FlowInterface $flow
     */
    public function start(FlowInterface $flow)
    {
        $this->hasStarted = true;
    }

    /**
     * Triggered when a Flow progresses,
     * eg exec once or generates once
     *
     * @param FlowInterface $flow
     * @param NodeInterface $node
     */
    public function progress(FlowInterface $flow, NodeInterface $node)
    {
        ++$this->numProgress;
    }

    /**
     * Triggered when a Flow completes without exceptions
     *
     * @param FlowInterface $flow
     */
    public function success(FlowInterface $flow)
    {
        $this->hasSucceeded = true;
    }

    /**
     * Triggered when a Flow fails
     *
     * @param FlowInterface $flow
     */
    public function fail(FlowInterface $flow)
    {
        $this->hasFailed = true;
    }

    /**
     * @return bool
     */
    public function hasStarted()
    {
        return $this->hasStarted;
    }

    /**
     * @return bool
     */
    public function hasSucceeded()
    {
        return $this->hasSucceeded;
    }

    /**
     * @return bool
     */
    public function hasFailed()
    {
        return $this->hasFailed;
    }

    /**
     * @return int
     */
    public function getNumProgress()
    {
        return $this->numProgress;
    }
}
