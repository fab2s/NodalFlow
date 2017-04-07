<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\FlowInterface;

/**
 * abstract Class NodeAbstract
 */
abstract class NodeAbstract implements NodeInterface
{
    /**
     * @var FlowInterface
     */
    public $carrier;

    /**
     * @var string
     */
    public $nodeHash;

    /**
     * @var bool
     */
    protected $isATraversable;

    /**
     * @var bool
     */
    protected $isAReturningVal;

    /**
     * @var bool
     */
    protected $isAFlow;

    public function __construct()
    {
        $this->enforceIsATraversable();
    }

    /**
     * @return bool
     */
    public function isTraversable()
    {
        return (bool) $this->isATraversable;
    }

    /**
     * @return bool true if this node instanceof FlowInterface
     */
    public function isFlow()
    {
        return (bool) $this->isAFlow;
    }

    /**
     * @return bool true if this node is expected to return
     *              something to pass on next node as param.
     *              If nothing is returned, the previously
     *              returned value will be use as param
     *              for next nodes.
     */
    public function isReturningVal()
    {
        return (bool) $this->isAReturningVal;
    }

    /**
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setCarrier(FlowInterface $flow)
    {
        $this->carrier = $flow;

        return $this;
    }

    /**
     * @return FlowInterface
     */
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * @param string $nodeHash
     *
     * @return $this
     */
    public function setNodeHash($nodeHash)
    {
        $this->nodeHash = $nodeHash;

        return $this;
    }

    /**
     * @return string
     */
    public function getNodeHash()
    {
        return $this->nodeHash;
    }

    /**
     * @throws \Exception
     *
     * @return $this
     */
    protected function enforceIsATraversable()
    {
        if ($this->isFlow()) {
            if ($this->isATraversable) {
                throw new \Exception('Cannot Traverse a Branch');
            }

            return $this;
        }

        if ($this->isATraversable) {
            if (!($this instanceof TraversableNodeInterface)) {
                throw new \Exception('Cannot Traverse a Node that does not implement TraversableNodeInterface');
            }

            return $this;
        }

        if (!($this instanceof ExecNodeInterface)) {
            throw new \Exception('Cannot Exec a Node that does not implement ExecNodeInterface');
        }

        return $this;
    }
}
