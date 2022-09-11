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
use fab2s\SoUuid\SoUuid;

/**
 * Trait FlowIdTrait
 */
trait FlowIdTrait
{
    /**
     * This Flow / Node id
     *
     * @var string
     */
    protected $id;

    /**
     * Current nonce, fully valid within each thread
     *
     * @var int
     */
    protected static $nonce = 0;

    /**
     * We need to reset the id when being cloned
     * to guarantee immutable uniqueness
     */
    public function __clone()
    {
        $this->id = null;
        // need to detach node from carrier
        if ($this instanceof NodeInterface) {
            $this->setCarrier(null);
        }
    }

    /**
     * Return the immutable unique Flow / Node id
     * Since this method is not used in the actual
     * flow execution loop, but only when an interruption
     * is raised, it's not a performance issue to add an if.
     * And it's more convenient to lazy generate as this
     * trait does not need any init/construct logic.
     *
     * @throws Exception
     *
     * @return string Immutable unique id
     */
    public function getId(): string
    {
        if ($this->id === null) {
            return $this->id = SoUuid::generate()->getString();
        }

        return $this->id;
    }
}
