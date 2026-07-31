<?php

/*
 * This file is part of NodalFlow
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
     */
    public function __construct(CallbackInterface $callBack)
    {
        $this->callBack = $callBack;
    }

    /**
     * Symfony 8 added the array return type to EventSubscriberInterface,
     * declaring it here stays compatible with symfony 6.4 and 7.x
     */
    public static function getSubscribedEvents(): array
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
     */
    public function start(FlowEventInterface $event)
    {
        $this->callBack->start($event->getFlow());
    }

    /**
     * Triggered when a Flow progresses,
     * eg exec once or generates once
     */
    public function progress(FlowEventInterface $event)
    {
        $this->callBack->progress($event->getFlow(), /* @scrutinizer ignore-type */ $event->getNode());
    }

    /**
     * Triggered when a Flow completes without exceptions
     */
    public function success(FlowEventInterface $event)
    {
        $this->callBack->success($event->getFlow());
    }

    /**
     * Triggered when a Flow fails
     */
    public function fail(FlowEventInterface $event)
    {
        $this->callBack->fail($event->getFlow());
    }
}
