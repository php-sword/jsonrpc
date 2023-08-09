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
use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

class MessageFactory implements MessageFactoryInterface
{
    /**
     * @param string            $method
     * @param string            $uri
     * @param array             $headers
     * @param array             $options
     *
     * @return RequestInterface
     */
    public function createRequest($method, $uri, array $headers = [], array $options = [])
    {
        $body = Json::encode($this->addIdToRequest($method, $options));

        return new Request('POST', $uri, $headers, $body === false ? null : $body);
    }

    /**
     * @param int                $statusCode
     * @param array              $headers
     * @param array              $options
     *
     * @return ResponseInterface
     */
    public function createResponse($statusCode, array $headers = [], array $options = [])
    {
        $body = Json::encode($options);

        return new Response($statusCode, $headers, $body === false ? null : $body);
    }

    /**
     * @param  HttpRequestInterface $request
     *
     * @return RequestInterface
     */
    public function fromRequest(HttpRequestInterface $request)
    {
        return $this->createRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            Json::decode((string) $request->getBody(), true) ?: []
        );
    }

    /**
     * @param  HttpResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function fromResponse(HttpResponseInterface $response)
    {
        return $this->createResponse(
            $response->getStatusCode(),
            $response->getHeaders(),
            Json::decode((string) $response->getBody(), true) ?: []
        );
    }

    /**
     * @param string $method
     * @param  array  $data
     *
     * @return array
     */
    protected function addIdToRequest(string $method, array $data): array
    {
        if (RequestInterface::REQUEST === $method && ! isset($data['id'])) {
            $data['id'] = uniqid(true);
        }

        return $data;
    }
}
