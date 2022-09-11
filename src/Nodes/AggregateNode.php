<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\FlowInterface;
use fab2s\NodalFlow\NodalFlow;
use fab2s\NodalFlow\NodalFlowException;
use Generator;

/**
 * class AggregateNode
 */
class AggregateNode extends PayloadNodeAbstract implements AggregateNodeInterface
{
    /**
     * The underlying pseudo Flow
     *
     * @var FlowInterface
     */
    protected $payload;

    /**
     * Instantiate an Aggregate Node
     *
     * @param bool $isAReturningVal
     *
     * @throws NodalFlowException
     */
    public function __construct(bool $isAReturningVal)
    {
        parent::__construct(new NodalFlow, $isAReturningVal);
        $this->isATraversable = true;
    }

    /**
     * Add a traversable to the aggregate
     *
     * @param TraversableNodeInterface $node
     *
     * @throws NodalFlowException
     *
     * @return $this
     */
    public function addTraversable(TraversableNodeInterface $node): AggregateNodeInterface
    {
        $this->payload->add($node);

        return $this;
    }

    /**
     * Get the traversable to traverse within the Flow
     *
     * @param mixed $param
     *
     * @return Generator
     */
    public function getTraversable($param = null): iterable
    {
        $value = null;
        /** @var $nodes TraversableNodeInterface[] */
        $nodes = $this->payload->getNodes();
        foreach ($nodes as $node) {
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

    /**
     * Execute the BranchNode
     *
     * @param mixed $param
     *
     * @throws NodalFlowException
     */
    public function exec($param = null)
    {
        throw new NodalFlowException('AggregateNode cannot be executed, use getTraversable to iterate instead');
    }
}
