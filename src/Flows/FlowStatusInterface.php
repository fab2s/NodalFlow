<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

/**
 * Interface FlowStatusInterface
 */
interface FlowStatusInterface
{
    /**
     * @param string $status The flow status
     */
    public function __construct($status);

    /**
     * @return string The flow status
     */
    public function __toString();

    /**
     * @return bool True If everything went well during the flow
     */
    public function isClean();

    /**
     * @return bool True If the flow was interrupted without exception
     */
    public function isDirty();

    /**
     * @return bool True If the flow was interrupted with exception
     */
    public function isException();

    /**
     * @return string The flow status
     */
    public function getStatus();
}
