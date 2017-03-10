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
 * Class FlowAbstract
 */
abstract class FlowAbstract implements FlowInterface
{
    /**
     * @var string
     */
    public $branchId;
    /**
     * @var array
     */
    protected $nodes = [];

    /**
     * @var int
     */
    protected $nodeIdx = 0;

    /**
     * @var int
     */
    protected $lastIdx = 0;

    /**
     * @var int
     */
    protected $nodeCount = 0;

    /**
     * @var array
     */
    protected $nodeMapDefault = [
        'class'        => null,
        'branchId'     => null,
        'hash'         => null,
        'index'        => 0,
        'num_exec'     => 0,
        'num_iterate'  => 0,
    ];

    /**
     * @var array
     */
    protected $nodeStatsDefault = [
        'num_exec'     => 0,
        'num_iterate'  => 0,
    ];

    /**
     * @var array
     */
    protected $nodeStats = [];

    /**
     * @var array
     */
    protected $objectMap = [];

    /**
     * @var array
     */
    protected $nodeMap = [];

    /**
     * @var int
     */
    private static $nonce = 0;

    public function __construct()
    {
        $this->branchId = $this->uniqId();
    }

    /**
     * @param NodeInterface $node
     *
     * @return $this
     */
    public function addNode(NodeInterface $node)
    {
        $nodeHash = $this->objectHash($node);
        $node->setBranchId($this->branchId)->setNodeHash($nodeHash);

        $this->nodes[$this->nodeIdx] = $node;
        $this->nodeMap[$nodeHash]    = array_replace($this->nodeMapDefault, [
            'class'    => get_class($node),
            'branchId' => $this->branchId,
            'hash'     => $nodeHash,
            'index'    => $this->nodeIdx,
        ]);

        // regsiter references to nodeStats to increment faster
        foreach ($this->nodeStatsDefault as $incrementKey => $ignore) {
            $this->nodeStats[$this->nodeIdx][$incrementKey] = &$this->nodeMap[$nodeHash][$incrementKey];
        }

        // expose node hash to also use nodeStat as a reverse lookup table
        $this->nodeStats[$this->nodeIdx]['hash'] = $nodeHash;

        ++$this->nodeIdx;

        return $this;
    }

    /**
     * spl_object_hash seems tempting here, but we
     * want to garanty both this and the position
     * and two object may be reused in a branch at
     * a different location
     *
     * @param object $object
     *
     * @return string
     */
    public function uniqId()
    {
        // while we're at it, drop any doubt about
        // colliding from here
        return \sha1(uniqid() . $this->getNonce());
    }

    /**
     * @param object $object
     *
     * @return string
     */
    public function objectHash($object)
    {
        return \sha1(\spl_object_hash($object));
    }

    /**
     * @param null|mixed $param
     *
     * @return mixed the last result of the
     *               last returing value node
     */
    public function exec($param = null)
    {
        return $this->rewind()->recurse($param);
    }

    /**
     * @return $this
     */
    public function resetNodeStats()
    {
        foreach ($this->nodeStats as &$nodeStat) {
            $nodeStat = array_replace($nodeStat, $this->nodeStatDefault);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function getNodeMap()
    {
        return $this->nodeMap;
    }

    /**
     * @return $this
     */
    public function getNodeStats()
    {
        return $this->nodeStats;
    }

    /**
     * @staticvar int $nonce
     *
     * @return type
     */
    protected function getNonce()
    {
        return self::$nonce++;
    }

    /**
     * Rewinds current branch
     *
     * @return $this
     */
    protected function rewind()
    {
        $this->nodeCount = count($this->nodes);
        $this->lastIdx   = $this->nodeCount - 1;
        $this->nodeIdx   = 0;

        return $this;
    }

    /**
     * Recurse over flows and nodes which may
     * as well be Traversable we recurse on.
     * Welcome to the abysses of recursion ^^
     *
     * @param mixed $param
     * @param int   $nodeIdx
     *
     * @return mixed, the last value returned in the chain
     */
    protected function recurse($param = null, $nodeIdx = 0)
    {
        // in case we'v consumed all nodes,
        // let the last retuned value pop up to
        // upstream calls in the recursive abyss
        if ($nodeIdx > $this->lastIdx) {
            return $param;
        }

        $node      = $this->nodes[$nodeIdx];
        $nodeStat  = &$this->nodeStats[$nodeIdx];
        $returnVal = $node->isReturningVal();
        if ($node->isFlow()) {
            // exec branch
            $value = $node->getPayload()->exec($param);
            ++$nodeStat['num_exec'];

            if ($returnVal) {
                // pass current $value as next param
                $param = $value;
            }

            // continue with this flow
            $param = $this->recurse($param, $nodeIdx + 1);
        } elseif ($node->isTraversable()) {
            $recurseToIdx = $nodeIdx + 1;
            foreach ($node->getTraversable($param) as $value) {
                if ($returnVal) {
                    // pass current $value as next param
                    // else keep last $param
                    $param = $value;
                }

                ++$nodeStat['num_iterate'];
                // here this means that if a deeper child does return something
                // it will buble up to the first node as param in case first node
                // is a Traversable
                $param = $this->recurse($param, $recurseToIdx);
            }
            ++$nodeStat['num_exec'];
        } else {
            $value = $node->exec($param);
            ++$nodeStat['num_exec'];
            if ($returnVal) {
                // pass current $value as next param
                $param = $value;
            }

            $param = $this->recurse($param, $nodeIdx + 1);
        }

        return $param;
    }
}
