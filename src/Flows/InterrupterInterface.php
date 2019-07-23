<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

use fab2s\NodalFlow\NodalFlowException;
use InvalidArgumentException;

/**
 * Interface InterrupterInterface
 */
interface InterrupterInterface
{
    /**
     * Enforce a value that will never match a SHA1 hash
     * Only used in practice to be able to detect when
     * interrupt signal bubbles to the top after having
     * missed it's target (Flow id)
     */
    const TARGET_TOP = 'top';

    /**
     * For the sake of completion, let's also support
     * a target to self. It would usually be simpler
     * to just not use a FlowInterrupt to interrupt
     * just this Flow, but it may prove useful in some
     * situation to just parametrize the FlowInterrupt
     * and always set one
     */
    const TARGET_SELF = 'self';

    /**
     * Continue type
     */
    const TYPE_CONTINUE = 'continue';

    /**
     * Break type
     */
    const TYPE_BREAK = 'break';

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     *
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function setType(string $type): self;

    /**
     * Trigger the Interrupt of each ancestor Flows up to a specific one, the root one
     * or none if :
     * - No FlowInterrupt is set
     * - FlowInterrupt is set at this Flow's Id
     * - FlowInterrupt is set as InterrupterInterface::TARGET_TOP and this has no parent
     *
     * Throw an exception if we reach the top after bubbling and FlowInterrupt != InterrupterInterface::TARGET_TOP
     *
     * @param FlowInterface $flow
     *
     * @throws NodalFlowException
     *
     * @return FlowInterface
     */
    public function propagate(FlowInterface $flow): FlowInterface;
}
