<?php

/*
 * This file is part of Guzzle HTTP JSON-RPC
 *
 * Copyright (c) 2014 Nature Delivered Ltd. <http://graze.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @see  http://github.com/graze/guzzle-jsonrpc/blob/master/LICENSE
 * @link http://github.com/graze/guzzle-jsonrpc
 */

namespace Sword\JsonRpc\Exception;

use InvalidArgumentException;
use Throwable;

class JsonDecodeException extends InvalidArgumentException
{
    /**
     * @var string
     */
    private string $json;

    /**
     * @param string $message The Exception message to throw
     * @param int $code The Exception code
     * @param Throwable|null $previous The previous throwable used for the exception chaining
     * @param string $json The JSON data.
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null, string $json = "")
    {
        parent::__construct($message, $code, $previous);
        $this->json = $json;
    }

    /**
     * @return string
     */
    public function getJson(): string
    {
        return $this->json;
    }
}
