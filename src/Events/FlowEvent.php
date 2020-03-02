<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Events;

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * trait FlowEventProxy
 *
 * A good example on how BC could use the S from Symfony
 */
trait FlowEventProxy
{
    /**
     * @var array
     */
    protected static $eventList;

    /**
     * @var FlowInterface
     */
    protected $flow;

    /**
     * @var NodeInterface|null
     */
    protected $node;

    /**
     * FlowEvent constructor.
     *
     * @param FlowInterface      $flow
     * @param NodeInterface|null $node
     */
    public function __construct(FlowInterface $flow, NodeInterface $node = null)
    {
        $this->flow = $flow;
        $this->node = $node;
    }

    /**
     * @return FlowInterface
     */
    public function getFlow(): FlowInterface
    {
        return $this->flow;
    }

    /**
     * @return NodeInterface|null
     */
    public function getNode(): ? NodeInterface
    {
        return $this->node;
    }

    /**
     * @param NodeInterface|null $node
     *
     * @return $this
     */
    public function setNode(NodeInterface $node = null): FlowEventInterface
    {
        $this->node = $node;

        return $this;
    }

    /**
     * @return array
     */
    public static function getEventList(): array
    {
        if (!isset(static::$eventList)) {
            static::$eventList = [
                static::FLOW_START    => static::FLOW_START,
                static::FLOW_PROGRESS => static::FLOW_PROGRESS,
                static::FLOW_CONTINUE => static::FLOW_CONTINUE,
                static::FLOW_BREAK    => static::FLOW_BREAK,
                static::FLOW_SUCCESS  => static::FLOW_SUCCESS,
                static::FLOW_FAIL     => static::FLOW_FAIL,
            ];
        }

        return static::$eventList;
    }
}

if (class_exists('Symfony\Component\EventDispatcher\Event')) {
    class FlowEvent extends \Symfony\Component\EventDispatcher\Event implements FlowEventInterface
    {
        use FlowEventProxy;
    }
} else {
    class FlowEvent extends \Symfony\Contracts\EventDispatcher\Event implements FlowEventInterface
    {
        use FlowEventProxy;
    }
}
