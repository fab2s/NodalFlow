<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow\Nodes;

use fab2s\NodalFlow\NodalFlowException;

/**
 * Class ControlNode
 */
class ControlNode extends PayloadNodeAbstract implements ExecNodeInterface
{
    /**
     * Instantiate a Callable Node
     *
     * @param callable $payload
     * @param bool     $isAReturningVal
     * @param bool     $isATraversable
     *
     * @throws NodalFlowException
     */
    public function __construct(callable $payload, $isAReturningVal = true, $isATraversable = false)
    {
        // since a qualifier is supposed to only control the flow, not to alter it,
        // we will force it to return a value and to be a scalar node
        parent::__construct($payload, true, false);
    }

    /**
     * The ControlNode's payload interface is simple :
     *      - return false to break
     *      - return true to continue
     *      - return void|null (whatever) to proceed with the flow
     *
     * @param mixed $param
     *
     * @return mixed
     */
    public function exec($param)
    {
        switch (\call_user_func($this->payload, $param)) {
            case false:
                $this->carrier->continueFlow();
                break;
            case true:
                $this->carrier->breakFlow();
                break;
        }

        return $param;
    }
}
