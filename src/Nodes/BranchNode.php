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
 * Class BranchNode
 */
class BranchNode extends PayloadNodeAbstract implements BranchNodeInterface
{
    /**
     * This Node is a Branch
     *
     * @var bool
     */
    protected $isAFlow = true;

    /**
     * @var FlowInterface
     */
    protected $payload;

    /**
     * Instantiate the BranchNode
     *
     * @param FlowInterface $payload
     * @param bool          $isAReturningVal
     *
     * @throws NodalFlowException
     */
    public function __construct(FlowInterface $payload, bool $isAReturningVal)
    {
        // branch Node does not (yet) support traversing
        parent::__construct($payload, $isAReturningVal, false);
    }

    /**
     * Execute the BranchNode
     *
     * @param mixed|null $param
     *
     * @return mixed
     */
    public function exec($param = null)
    {
        // in the branch case, we actually exec a Flow
        return $this->payload->exec($param);
    }
}
