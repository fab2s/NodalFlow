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
 * Abstract Class FlowAbstract
 */
abstract class FlowAbstract extends FlowInterruptAbstract
{
    use FlowIdTrait;

    /**
     * Get the stats array with latest Node stats
     *
     * @return array<string,integer|string>
     */
    public function getStats()
    {
        return $this->flowMap->getStats();
    }

    /**
     * Get the stats array with latest Node stats
     *
     * @return FlowMapInterface
     */
    public function getFlowMap()
    {
        return $this->flowMap;
    }

    /**
     * Get the Node array
     *
     * @return NodeInterface[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * Get/Generate Node Map
     *
     * @return array
     */
    public function getNodeMap()
    {
        return $this->flowMap->getNodeMap();
    }

    /**
     * The Flow status can either indicate be:
     *      - clean (isClean()): everything went well
     *      - dirty (isDirty()): one Node broke the flow
     *      - exception (isException()): an exception was raised during the flow
     *
     * @return FlowStatusInterface
     */
    public function getFlowStatus()
    {
        return $this->flowStatus;
    }

    /**
     * getId() alias for backward compatibility
     *
     * @deprecated use `getId` instead
     *
     * @return string
     */
    public function getFlowId()
    {
        return $this->getId();
    }
}
