<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\Callbacks\CallbackInterface;
use fab2s\NodalFlow\Events\CallbackWrapper;
use fab2s\NodalFlow\Events\FlowEvent;
use fab2s\NodalFlow\Events\FlowEventInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Abstract Class FlowEventAbstract
 */
abstract class FlowEventAbstract extends FlowAncestryAbstract
{
    /**
     * Progress modulo to apply
     * Set to x if you want to trigger
     * progress every x iterations in flow
     *
     * @var int
     */
    protected $progressMod = 1024;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $activeEvents;

    /**
     * @var FlowEventInterface
     */
    protected $sharedEvent;

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
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new EventDispatcher;
        }

        return $this->dispatcher;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     *
     * @return FlowEventAbstract
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Register callback class
     *
     * @deprecated Use Flow events & dispatcher instead
     *
     * @param CallbackInterface $callBack
     *
     * @return $this
     */
    public function setCallBack(CallbackInterface $callBack)
    {
        $this->getDispatcher()->addSubscriber(new CallbackWrapper($callBack));

        return $this;
    }

    /**
     * @param string             $eventName
     * @param NodeInterface|null $node
     *
     * @return $this
     */
    protected function triggerEvent($eventName, NodeInterface $node = null)
    {
        if (isset($this->activeEvents[$eventName])) {
            $this->dispatcher->dispatch($eventName, $this->sharedEvent->setNode($node));
        }

        return $this;
    }

    /**
     * @param bool $reload
     *
     * @return $this
     */
    protected function listActiveEvent($reload = false)
    {
        if (!isset($this->dispatcher) || (isset($this->activeEvents) && !$reload)) {
            return $this;
        }

        $this->activeEvents = [];
        $eventList          = FlowEvent::getEventList();
        $sortedListeners    = $this->dispatcher->getListeners();
        foreach ($sortedListeners as $eventName => $listeners) {
            if (isset($eventList[$eventName]) && !empty($listeners)) {
                $this->activeEvents[$eventName] = 1;
            }
        }

        return $this;
    }
}
