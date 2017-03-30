<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

/**
 * class FlowStatus
 */
class FlowStatus implements FlowStatusInterface
{
    const FLOW_CLEAN     = 'clean';
    const FLOW_DIRTY     = 'dirty';
    const FLOW_EXCEPTION = 'exception';

    /**
     * @var string
     */
    protected $flowStatus;

    /**
     * @var array
     */
    protected $flowStatuses = [
        self::FLOW_CLEAN     => self::FLOW_CLEAN,
        self::FLOW_DIRTY     => self::FLOW_DIRTY,
        self::FLOW_EXCEPTION => self::FLOW_EXCEPTION,
    ];

    /**
     * @param string $flowStatus The flow status
     */
    public function __construct($flowStatus)
    {
        if (!isset($this->flowStatuses[$flowStatus])) {
            throw new \InvalidArgumentException('[NodalFlow] $flowStatus must be one of :' . \implode(', ', $this->flowStatuses));
        }

        $this->flowStatus = \trim($flowStatus);
    }

    /**
     * @return string The flow status
     */
    public function __toString()
    {
        return $this->getStatus();
    }

    /**
     * @return bool True If everything went well during the flow
     */
    public function isClean()
    {
        return $this->flowStatus === static::FLOW_CLEAN;
    }

    /**
     * @return bool True If the flow was interrupted without exception
     */
    public function isDirty()
    {
        return $this->flowStatus === static::FLOW_DIRTY;
    }

    /**
     * @return bool True If the flow was interrupted with exception
     */
    public function isException()
    {
        return $this->flowStatus === static::FLOW_EXCEPTION;
    }

    /**
     * @return string The flow status
     */
    public function getFlowStatus()
    {
        return $this->flowStatus;
    }
}
