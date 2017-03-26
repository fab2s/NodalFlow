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
 * class AggregateNode
 */
class AggregateNode extends NodeAbstract implements AggregateNodeInterface
{
    /**
     * @var bool
     */
    protected $isAReturningVal = true;

    /**
     * @var bool
     */
    protected $isATraversable = true;

    /**
     * @var array
     */
    protected $nodeCollection = [];

    /**
     * @param bool $isAReturningVal
     */
    public function __construct($isAReturningVal)
    {
        $this->isAReturningVal = (bool) $isAReturningVal;

        parent::__construct();
    }

    /**
     * get the traversable to traverse within the Flow
     *
     * TraversableNodeInterface $node
     *
     * @return $this
     */
    public function addTraversable(TraversableNodeInterface $node)
    {
        if ($this->carrier) {
            $node->setCarrier($this->carrier)->setNodeHash($this->carrier->objectHash($node));
        }

        $this->nodeCollection[] = $node;

        return $this;
    }

    /**
     * @param FlowInterface $flow
     *
     * @return $this
     */
    public function setCarrier(FlowInterface $flow)
    {
        // maintain carrier among aggregated nodes
        foreach ($this->nodeCollection as $node) {
            $node->setCarrier($flow)->setNodeHash($flow->objectHash($node));
        }

        parent::setCarrier($flow);

        return $this;
    }

    /**
     * @return array
     */
    public function getNodeCollection()
    {
        return $this->nodeCollection;
    }

    /**
     * get the traversable to traverse within the Flow
     *
     * @param mixed $param
     *
     * @return \Traversable
     */
    public function getTraversable($param)
    {
        $value = null;

        foreach ($this->nodeCollection as $node) {
            $returnVal = $node->isReturningVal();
            foreach ($node->getTraversable($param) as $value) {
                if ($returnVal) {
                    yield $value;
                } else {
                    yield $param;
                }
            }

            if ($returnVal) {
                // since this node is returning somehting
                // we will pass its last vield to the next
                // traversable. It will be up to him to
                // do whatever is necessary with it, including
                // nothing
                $param = $value;
            }
        }
    }
}
