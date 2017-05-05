<?php

/*
 * This file is part of NodalFlow.
 *     (c) Fabrice de Stefanis / https://github.com/fab2s/NodalFlow
 * This source file is licensed under the MIT license which you will
 * find in the LICENSE file or at https://opensource.org/licenses/MIT
 */

namespace fab2s\NodalFlow;

/**
 * Class NodalFlowException
 */
class NodalFlowException extends \Exception
{
    /**
     * Exception context
     *
     * @var array
     */
    protected $context = [];

    /**
     * Instantiate an exception
     *
     * @param string          $message
     * @param int             $code
     * @param null|\Exception $previous
     * @param array           $context
     */
    public function __construct($message, $code = 0, \Exception $previous = null, array $context = [])
    {
        $this->context = $context;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get current exception context, useful for logging
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
