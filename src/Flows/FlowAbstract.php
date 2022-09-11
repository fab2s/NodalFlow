<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use Exception;
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
    public function getStats(): array
    {
        return $this->flowMap->getStats();
    }

    /**
     * Get the stats array with latest Node stats
     *
     * @return FlowMapInterface
     */
    public function getFlowMap(): FlowMapInterface
    {
        return $this->flowMap;
    }

    /**
     * Get the Node array
     *
     * @return NodeInterface[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * Get/Generate Node Map
     *
     * @return array
     */
    public function getNodeMap(): array
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
    public function getFlowStatus(): ? FlowStatusInterface
    {
        return $this->flowStatus;
    }

    /**
     * getId() alias for backward compatibility
     *
     * @throws Exception
     *
     * @return string
     *
     * @deprecated use `getId` instead
     */
    public function getFlowId(): string
    {
        return $this->getId();
    }
}
