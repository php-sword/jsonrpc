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

namespace Sword\JsonRpc\Message;

use Sword\JsonRpc\Json;
use GuzzleHttp\Psr7\Response as HttpResponse;

class Response extends HttpResponse implements ResponseInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRpcErrorCode()
    {
        $error = $this->getFieldFromBody('error');

        return $error['code'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRpcErrorMessage()
    {
        $error = $this->getFieldFromBody('error');

        return $error['message'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRpcErrorData()
    {
        $error = $this->getFieldFromBody('error');

        return $error['data'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRpcId()
    {
        return $this->getFieldFromBody('id');
    }

    /**
     * @return mixed
     */
    public function getRpcResult()
    {
        return $this->getFieldFromBody('result');
    }

    /**
     * {@inheritdoc}
     */
    public function getRpcVersion()
    {
        return $this->getFieldFromBody('jsonrpc');
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function getFieldFromBody(string $key)
    {
        $rpc = Json::decode((string) $this->getBody(), true);

        return $rpc[$key] ?? null;
    }
}
