<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Flows;

/**
 * Interface FlowIdInterface
 */
interface FlowIdInterface
{
    /**
     * Return the immutable unique Flow / Node id
     *
     * @return string Immutable unique id
     */
    public function getId(): string;
}
