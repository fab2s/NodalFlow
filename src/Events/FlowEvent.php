<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Events;

if (class_exists('Symfony\Component\EventDispatcher\Event')) {
    class FlowEvent extends /* @scrutinizer ignore-deprecated */ \Symfony\Component\EventDispatcher\Event implements FlowEventInterface
    {
        use FlowEventProxyTrait;
    }
} else {
    class FlowEvent extends \Symfony\Contracts\EventDispatcher\Event implements FlowEventInterface
    {
        use FlowEventProxyTrait;
    }
}
