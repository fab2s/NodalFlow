<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Events;

use fab2s\NodalFlow\Callbacks\CallbackInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CallbackWrapper
 */
class CallbackWrapper implements EventSubscriberInterface
{
    /**
     * The registered Callback class
     *
     * @var CallbackInterface
     */
    protected $callBack;

    /**
     * CallbackWrapper constructor.
     *
     * @param CallbackInterface $callBack
     */
    public function __construct(CallbackInterface $callBack)
    {
        $this->callBack = $callBack;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FlowEventInterface::FLOW_START    => ['start', 0],
            FlowEventInterface::FLOW_PROGRESS => ['progress', 0],
            FlowEventInterface::FLOW_SUCCESS  => ['success', 0],
            FlowEventInterface::FLOW_FAIL     => ['fail', 0],
        ];
    }

    /**
     * Triggered when a Flow starts
     *
     * @param FlowEventInterface $event
     */
    public function start(FlowEventInterface $event)
    {
        $this->callBack->start($event->getFlow());
    }

    /**
     * Triggered when a Flow progresses,
     * eg exec once or generates once
     *
     * @param FlowEventInterface $event
     */
    public function progress(FlowEventInterface $event)
    {
        $this->callBack->progress($event->getFlow(), /* @scrutinizer ignore-type */ $event->getNode());
    }

    /**
     * Triggered when a Flow completes without exceptions
     *
     * @param FlowEventInterface $event
     */
    public function success(FlowEventInterface $event)
    {
        $this->callBack->success($event->getFlow());
    }

    /**
     * Triggered when a Flow fails
     *
     * @param FlowEventInterface $event
     */
    public function fail(FlowEventInterface $event)
    {
        $this->callBack->fail($event->getFlow());
    }
}
