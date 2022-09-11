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
use fab2s\NodalFlow\Nodes\NodeInterface;
use ReflectionException;
use ReflectionMethod;
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
     * @var array
     */
    protected $dispatchArgs = [];

    /**
     * @var int
     */
    protected $eventInstanceKey = 0;

    /**
     * @var int
     */
    protected $eventNameKey = 1;

    /**
     * Get current $progressMod
     *
     * @return int
     */
    public function getProgressMod(): int
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
    public function setProgressMod(int $progressMod): self
    {
        $this->progressMod = max(1, $progressMod);

        return $this;
    }

    /**
     * @throws ReflectionException
     *
     * @return EventDispatcherInterface
     */
    public function getDispatcher(): EventDispatcherInterface
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new EventDispatcher;
            $this->initDispatchArgs(EventDispatcher::class);
        }

        return $this->dispatcher;
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws ReflectionException
     *
     * @return $this
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher): self
    {
        $this->dispatcher = $dispatcher;

        return $this->initDispatchArgs(\get_class($dispatcher));
    }

    /**
     * Register callback class
     *
     * @param CallbackInterface $callBack
     *
     * @throws ReflectionException
     *
     * @return $this
     *
     * @deprecated Use Flow events & dispatcher instead
     */
    public function setCallBack(CallbackInterface $callBack): self
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
    protected function triggerEvent(string $eventName, NodeInterface $node = null): self
    {
        if (isset($this->activeEvents[$eventName])) {
            $this->dispatchArgs[$this->eventNameKey] = $eventName;
            $this->dispatchArgs[$this->eventInstanceKey]->setNode($node);
            $this->dispatcher->dispatch(/* @scrutinizer ignore-type */ ...$this->dispatchArgs);
        }

        return $this;
    }

    /**
     * @param bool $reload
     *
     * @return $this
     */
    protected function listActiveEvent(bool $reload = false): self
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

    /**
     * I am really wondering wtf happening in their mind when
     * they decided to flip argument order on such a low level
     * foundation.
     *
     * This is just one of those cases where usability should win
     * over academic principles. Setting name upon event instance is
     * just not more convenient than setting it at call time, it's
     * just a useless mutation in most IRL cases where the event is
     * the same throughout many event slots (you know, practice).
     * It's not even so obvious that coupling event with their
     * usage is such a good idea, academically speaking.
     *
     * Now if you add that this results in:
     *  - duplicated code in symfony itself
     *  - hackish tricks to maintain BC
     *  - loss of argument type hinting
     *  - making it harder to support multiple version
     *    while this is supposed to achieve better compatibility
     *    AND no actual feature was added for so long ...
     *
     * This is pretty close to achieving the opposite of the
     * original purpose IMHO
     *
     * PS:
     * Okay, this is also a tribute to Linus memorable rants, but ...
     *
     * @param string $class
     *
     * @throws ReflectionException
     *
     * @return FlowEventAbstract
     */
    protected function initDispatchArgs(string $class): self
    {
        $reflection         = new ReflectionMethod($class, 'dispatch');
        $firstParam         = $reflection->getParameters()[0];
        $this->dispatchArgs = [
            new FlowEvent($this),
            null,
        ];

        if ($firstParam->getName() !== 'event') {
            $this->eventInstanceKey = 1;
            $this->eventNameKey     = 0;
            $this->dispatchArgs     = array_reverse($this->dispatchArgs);
        }

        return $this;
    }
}
