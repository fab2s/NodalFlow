<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\Flows\FlowInterface;

/**
 * abstract Class NodeAbstract
 * Dummily implementing methods saves a lot of interfaces,
 * KISS wins again ^^
 */
abstract class NodeAbstract implements NodeInterface
{
    /**
     * @var string
     */
    public $branchId;

    /**
     * @var string
     */
    public $nodeHash;
    /**
     * @var bool
     */
    protected $isATraversable;

    /**
     * @var bool
     */
    protected $isAReturningVal;

    /**
     * @var bool
     */
    protected $isAFlow;

    /**
     *
     */
    public function __construct()
    {
        $this->enforceIsATraversable();
    }

    /**
     * @param type $isATraversable
     *
     * @throws \Exception
     */
    public function enforceIsATraversable()
    {
        if ($this->isFlow()) {
            if ($this->isATraversable) {
                throw new \Exception('Cannot Traverse a Branch');
            }
        } else {
            if ($this->isATraversable && !($this instanceof TraversableNodeInterface)) {
                throw new \Exception('Cannot Traverse a Node that does not implement TraversableNodeInterface');
            }

            if (!$this->isATraversable && !$this->isFlow() && !($this instanceof ExecNodeInterface)) {
                throw new \Exception('Cannot Exec a Node that does not implement ExecNodeInterface');
            }
        }
    }

    /**
     * check is this very instance did implement
     * NodeInterface::getTraversable() itself
     * (and not heritated it from NodeAbstract)
     *
     * @return bool
     */
    public function canBeTraversed()
    {
        $reflexion = new ReflectionClass(static::class);
        foreach ($reflexion->getMethods() as $method) {
            if (
                $method->class === static::class &&
                $method->name === 'getTraversable'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isTraversable()
    {
        return (bool) $this->isATraversable;
    }

    /**
     * @return bool true if payload instanceof FlowInterface
     */
    public function isFlow()
    {
        return (bool) $this->isAFlow;
    }

    /**
     * @return bool true if payload is expected to return
     *              something to pass on next node as param.
     *              If nothing is returned, the previously
     *              returned value will be use as param
     *              for next nodes.
     */
    public function isReturningVal()
    {
        return (bool) $this->isAReturningVal;
    }

    /**
     * @param string $branchId
     *
     * @return $this
     */
    public function setBranchId($branchId)
    {
        $this->branchId = trim($branchId);

        return $this;
    }

    /**
     * @return string
     */
    public function getBranchId()
    {
        return $this->branchId;
    }

    /**
     * @param mixed $nodeHash
     *
     * @return $this
     */
    public function setNodeHash($nodeHash)
    {
        $this->nodeHash = $nodeHash;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNodeHash()
    {
        return $this->nodeHash;
    }
}
