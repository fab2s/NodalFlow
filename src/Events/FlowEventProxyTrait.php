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
 * trait FlowEventProxyTrait
 *
 * A good example on how BC could use the S from Symfony
 */
trait FlowEventProxyTrait
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
     * @return FlowEventInterface
     */
    public function setNode(NodeInterface $node = null): FlowEventInterface
    {
        $this->node = $node;

        /* @var FlowEventInterface $this */
        return $this;
    }

    /**
     * @return array
     */
    public static function getEventList(): array
    {
        /* @var FlowEventInterface $this */
        if (!isset(static::$eventList)) {
            static::$eventList = [
                FlowEventInterface::FLOW_START    => FlowEventInterface::FLOW_START,
                FlowEventInterface::FLOW_PROGRESS => FlowEventInterface::FLOW_PROGRESS,
                FlowEventInterface::FLOW_CONTINUE => FlowEventInterface::FLOW_CONTINUE,
                FlowEventInterface::FLOW_BREAK    => FlowEventInterface::FLOW_BREAK,
                FlowEventInterface::FLOW_SUCCESS  => FlowEventInterface::FLOW_SUCCESS,
                FlowEventInterface::FLOW_FAIL     => FlowEventInterface::FLOW_FAIL,
            ];
        }

        return static::$eventList;
    }
}
