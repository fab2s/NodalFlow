<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\FlowIdInterface;
use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\NodalFlowException;

/**
 * Interface NodeInterface
 */
interface NodeInterface extends FlowIdInterface
{
    /**
     * Indicate if the Node is Traversable
     *
     * @return bool
     */
    public function isTraversable(): bool;

    /**
     * Indicate if the Node is a Flow (branch)
     *
     * @return bool true if this node is an instanceof FlowInterface
     */
    public function isFlow(): bool;

    /**
     * Indicate if the Node is returning a value
     *
     * @return bool true if this node is expected to return
     *              something to pass to the next node as param.
     *              If nothing is returned, the previously
     *              returned value will be use as param
     *              for next nodes.
     */
    public function isReturningVal(): bool;

    /**
     * Set/Reset carrying Flow
     *
     * @param FlowInterface|null $flow
     *
     * @return $this
     */
    public function setCarrier(FlowInterface $flow = null): self;

    /**
     * Return the carrying Flow
     *
     * @return FlowInterface|null
     */
    public function getCarrier(): ? FlowInterface;

    /**
     * Get this Node's hash, must be deterministic and unique
     *
     * @deprecated use `getId` instead
     *
     * @return string
     */
    public function getNodeHash(): string;

    /**
     * @param string      $flowId
     * @param string|null $nodeId
     * @param mixed|null  $param
     *
     * @throws NodalFlowException
     *
     * @return mixed
     */
    public function sendTo(string $flowId, string $nodeId = null, $param = null);

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
    public function getNodeIncrements(): array;
}
