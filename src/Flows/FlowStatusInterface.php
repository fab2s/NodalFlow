<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use Exception;

/**
 * Interface FlowStatusInterface
 */
interface FlowStatusInterface
{
    /**
     * Instantiate a Flow status
     *
     * @param string         $status The flow status
     * @param Exception|null $e
     */
    public function __construct(string $status, Exception $e = null);

    /**
     * Get a string representation of the Flow status
     *
     * @return string The flow status
     */
    public function __toString(): string;

    /**
     * Indicate that the flow is currently running
     * useful for branched flow to find out what is
     * their parent up to and distinguish between top
     * parent end and branch end
     *
     * @return bool True If the flow is currently running
     */
    public function isRunning(): bool;

    /**
     * Tells if the Flow went smoothly
     *
     * @return bool True If everything went well during the flow
     */
    public function isClean(): bool;

    /**
     * Indicate that the flow was interrupted by a Node
     *
     * @return bool True If the flow was interrupted without exception
     */
    public function isDirty(): bool;

    /**
     * Indicate that an exception was raised during the Flow execution
     *
     * @return bool True If the flow was interrupted with exception
     */
    public function isException(): bool;

    /**
     * Return the Flow status
     *
     * @return string The flow status
     */
    public function getStatus(): string;

    /**
     * Return the eventual exception throw during the flow execution
     *
     * @return Exception|null
     */
    public function getException(): ? Exception;
}
