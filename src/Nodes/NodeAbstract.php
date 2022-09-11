<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\FlowIdTrait;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\Flows\FlowRegistry;
use fab2s\NodalFlow\Flows\FlowRegistryInterface;
use fab2s\NodalFlow\NodalFlowException;

/**
 * abstract Class NodeAbstract
 */
abstract class NodeAbstract implements NodeInterface
{
    use FlowIdTrait;

    /**
     * The carrying Flow
     *
     * @var FlowInterface
     */
    public $carrier;

    /**
     * Indicate if this Node is traversable
     *
     * @var bool
     */
    protected $isATraversable;

    /**
     * Indicate if this Node is returning a value
     *
     * @var bool
     */
    protected $isAReturningVal;

    /**
     * Indicate if this Node is a Flow (Branch)
     *
     * @var bool
     */
    protected $isAFlow;

    /**
     * @var FlowRegistryInterface
     */
    protected $registry;

    /**
     * @var array
     */
    protected $nodeIncrements = [];

    /**
     * NodeAbstract constructor.
     *
     * @throws NodalFlowException
     */
    public function __construct()
    {
        $this->enforceIsATraversable();
        $this->registry = new FlowRegistry;
    }

    /**
     * Indicate if this Node is Traversable
     *
     * @return bool
     */
    public function isTraversable(): bool
    {
        return (bool) $this->isATraversable;
    }

    /**
     * Indicate if this Node is a Flow (Branch)
     *
     * @return bool true if this node instanceof FlowInterface
     */
    public function isFlow(): bool
    {
        return (bool) $this->isAFlow;
    }

    /**
     * Indicate if this Node is returning a value
     *
     * @return bool true if this node is expected to return
     *              something to pass on next node as param.
     *              If nothing is returned, the previously
     *              returned value will be use as param
     *              for next nodes.
     */
    public function isReturningVal(): bool
    {
        return (bool) $this->isAReturningVal;
    }

    /**
     * Set/Reset carrying Flow
     *
     * @param FlowInterface|null $flow
     *
     * @return $this
     */
    public function setCarrier(FlowInterface $flow = null): NodeInterface
    {
        $this->carrier = $flow;

        return $this;
    }

    /**
     * Get carrying Flow
     *
     * @return FlowInterface
     */
    public function getCarrier(): ? FlowInterface
    {
        return $this->carrier;
    }

    /**
     * Get this Node's hash, must be deterministic and unique
     *
     * @throws \Exception
     *
     * @return string
     *
     * @deprecated use `getId` instead
     */
    public function getNodeHash(): string
    {
        return $this->getId();
    }

    /**
     * Get the custom Node increments to be considered during
     * Flow execution
     * To set additional increment keys, use :
     *      'keyName' => int
     * to add keyName as increment, starting at int
     * or :
     *      'keyName' => 'existingIncrement'
     * to assign keyName as a reference to an existingIncrement
     *
     * @return array
     */
    public function getNodeIncrements(): array
    {
        return $this->nodeIncrements;
    }

    /**
     * @param string      $flowId
     * @param string|null $nodeId
     * @param mixed|null  $param
     *
     * @throws NodalFlowException
     *
     * @return mixed
     */
    public function sendTo(string $flowId, string $nodeId = null, $param = null)
    {
        if (!($flow = $this->registry->getFlow($flowId))) {
            throw new NodalFlowException('Cannot sendTo without valid Flow target', 1, null, [
                'flowId' => $flowId,
                'nodeId' => $nodeId,
            ]);
        }

        return $flow->sendTo($nodeId, $param);
    }

    /**
     * Make sure this Node is consistent
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    protected function enforceIsATraversable(): self
    {
        if ($this->isFlow()) {
            if ($this->isATraversable) {
                throw new NodalFlowException('Cannot Traverse a Branch');
            }

            return $this;
        }

        if ($this->isATraversable) {
            if (!($this instanceof TraversableNodeInterface)) {
                throw new NodalFlowException('Cannot Traverse a Node that does not implement TraversableNodeInterface');
            }

            return $this;
        }

        if (!($this instanceof ExecNodeInterface)) {
            throw new NodalFlowException('Cannot Exec a Node that does not implement ExecNodeInterface');
        }

        return $this;
    }
}
