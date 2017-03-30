<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\Nodes\NodeInterface;

/**
 * Interface FlowInterface
 */
interface FlowInterface
{
    /**
     * @param NodeInterface $node
     *
     * @return $this
     */
    public function add(NodeInterface $node);

    /**
     * Execute the Flow
     *
     * @param mixed $param The first param to apply, mostly
     *                     usefull for branches when values have
     *                     already been generated
     *
     * @return mixed, the last value returned in the chain
     */
    public function exec($param = null);

    /**
     * Rewinds the Flow
     *
     * @return $this
     */
    public function rewind();

    /**
     * The Flow status can either indicate be:
     *      - clean (isClean()): everything went well
     *      - dirty (isDirty()): one Node broke the flow
     *      - exception (isException()): an exception was raised during the flow
     *
     * @return FlowStatusInterface
     */
    public function getFlowStatus();

    /**
     * get the underlying node array
     *
     * @return array
     */
    public function getNodes();

    /**
     * Nodes may call breakFlow() on their carrier to
     * break the flow
     *
     * @return $this
     */
    public function breakFlow();

    /**
     * Nodes may call breakFlow() on their carrier to
     * skip the rest of the nodes and continue with next
     * value fromt the first upstram traversable if any
     *
     * @return $this
     */
    public function continueFlow();
}
