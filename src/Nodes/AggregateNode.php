<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\NodalFlowException;

/**
 * class AggregateNode
 */
class AggregateNode extends NodeAbstract implements AggregateNodeInterface
{
    /**
     * Returning val status
     *
     * @var bool
     */
    protected $isAReturningVal = true;

    /**
     * Traversable status
     *
     * @var bool
     */
    protected $isATraversable = true;

    /**
     * The underlying Node structure
     *
     * @var array
     */
    protected $nodeCollection = [];

    /**
     * Instantiate an Aggregate Node
     *
     * @param bool $isAReturningVal
     *
     * @throws NodalFlowException
     */
    public function __construct($isAReturningVal)
    {
        $this->isAReturningVal = (bool) $isAReturningVal;

        parent::__construct();
    }

    /**
     * Get the traversable to traverse within the Flow
     *
     * @param TraversableNodeInterface $node
     *
     * @return $this
     */
    public function addTraversable(TraversableNodeInterface $node)
    {
        if ($this->carrier) {
            $node->setCarrier($this->carrier);
        }

        $this->nodeCollection[] = $node;

        return $this;
    }

    /**
     * Set carrier (eg the Flow this Node is attached to)
     *
     * @param FlowInterface|null $flow
     *
     * @return $this
     */
    public function setCarrier(FlowInterface $flow = null)
    {
        // maintain carrier among aggregated nodes
        foreach ($this->nodeCollection as $node) {
            $node->setCarrier($flow);
        }

        parent::setCarrier($flow);

        return $this;
    }

    /**
     * Return the underlying Node collection
     *
     * @return NodeInterface[]
     */
    public function getNodeCollection()
    {
        return $this->nodeCollection;
    }

    /**
     * Get the traversable to traverse within the Flow
     *
     * @param mixed $param
     *
     * @return \Generator
     */
    public function getTraversable($param)
    {
        $value = null;
        foreach ($this->nodeCollection as $node) {
            $returnVal = $node->isReturningVal();
            foreach ($node->getTraversable($param) as $value) {
                if ($returnVal) {
                    yield $value;
                    continue;
                }

                yield $param;
            }

            if ($returnVal) {
                // since this node is returning something
                // we will pass its last yield to the next
                // traversable. It will be up to him to
                // do whatever is necessary with it, including
                // nothing
                $param = $value;
            }
        }
    }
}
