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

namespace Sword\JsonRpc;

use Sword\JsonRpc\Message\MessageFactory;
use Sword\JsonRpc\Message\MessageFactoryInterface;
use Sword\JsonRpc\Message\RequestInterface;
use Sword\JsonRpc\Message\ResponseInterface;
use Sword\JsonRpc\Middleware\RequestFactoryMiddleware;
use Sword\JsonRpc\Middleware\RequestHeaderMiddleware;
use Sword\JsonRpc\Middleware\ResponseFactoryMiddleware;
use Sword\JsonRpc\Middleware\RpcErrorMiddleware;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Promise\PromiseInterface;

class Client implements ClientInterface
{
    /**
     * @var HttpClientInterface
     */
    protected HttpClientInterface $httpClient;

    /**
     * @var MessageFactoryInterface
     */
    protected MessageFactoryInterface $messageFactory;

    /**
     * @param HttpClientInterface     $httpClient
     * @param MessageFactoryInterface $factory
     */
    public function __construct(HttpClientInterface $httpClient, MessageFactoryInterface $factory)
    {
        $this->httpClient = $httpClient;
        $this->messageFactory = $factory;

        $handler = $this->httpClient->getConfig('handler');
        $handler->push(new RequestFactoryMiddleware($factory));
        $handler->push(new RequestHeaderMiddleware());
        $handler->push(new RpcErrorMiddleware());
        $handler->push(new ResponseFactoryMiddleware($factory));
    }

    /**
     * @param string $uri
     * @param  array  $config
     *
     * @return Client
     */
    public static function factory(string $uri, array $config = []): Client
    {
        if (isset($config['message_factory'])) {
            $factory = $config['message_factory'];
            unset($config['message_factory']);
        } else {
            $factory = new MessageFactory();
        }

        return new self(new HttpClient(array_merge($config, [
            'base_uri' => $uri,
        ])), $factory);
    }

    /**
     * {@inheritdoc}
     *
     * @link   http://www.jsonrpc.org/specification#notification
     *
     * @param string $method
     * @param  array|null       $params
     *
     * @return RequestInterface
     */
    public function notification(string $method, array $params = null): RequestInterface
    {
        return $this->createRequest(RequestInterface::NOTIFICATION, array_filter([
            'jsonrpc' => self::SPEC,
            'method' => $method,
            'params' => $params,
        ]));
    }

    /**
     * {@inheritdoc}
     *
     * @link   http://www.jsonrpc.org/specification#request_object
     *
     * @param  mixed            $id
     * @param string $method
     * @param  array|null       $params
     *
     * @return RequestInterface
     */
    public function request($id, string $method, array $params = null): RequestInterface
    {
        return $this->createRequest(RequestInterface::REQUEST, array_filter([
            'jsonrpc' => self::SPEC,
            'method' => $method,
            'params' => $params,
            'id' => $id,
        ]));
    }

    /**
     * {@inheritdoc}
     *
     * @param  RequestInterface       $request
     *
     * @return ResponseInterface|null
     */
    public function send(RequestInterface $request): ?ResponseInterface
    {
        $promise = $this->sendAsync($request);

        return $promise->wait();
    }

    /**
     * {@inheritdoc}
     *
     * @param  RequestInterface       $request
     *
     * @return PromiseInterface
     */
    public function sendAsync(RequestInterface $request): PromiseInterface
    {
        return $this->httpClient->sendAsync($request)->then(
            function (ResponseInterface $response) use ($request) {
                return $request->getRpcId() ? $response : null;
            }
        );
    }

    /**
     * {@inheritdoc}
     *
     * @link   http://www.jsonrpc.org/specification#batch
     *
     * @param  RequestInterface[]  $requests
     *
     * @return ResponseInterface[]
     */
    public function sendAll(array $requests): array
    {
        $promise = $this->sendAllAsync($requests);

        return $promise->wait();
    }

    /**
     * {@inheritdoc}
     *
     * @link   http://www.jsonrpc.org/specification#batch
     *
     * @param  RequestInterface[]  $requests
     *
     * @return PromiseInterface
     */
    public function sendAllAsync(array $requests): PromiseInterface
    {
        return $this->httpClient->sendAsync($this->createRequest(
            RequestInterface::BATCH,
            $this->getBatchRequestOptions($requests)
        ))->then(function (ResponseInterface $response) {
            return $this->getBatchResponses($response);
        });
    }

    /**
     * @param string $method
     * @param  array            $options
     *
     * @return RequestInterface
     */
    protected function createRequest(string $method, array $options): RequestInterface
    {
        $uri = $this->httpClient->getConfig('base_uri');
        $defaults = $this->httpClient->getConfig('defaults');
        $headers = $defaults['headers'] ?? [];

        return $this->messageFactory->createRequest($method, $uri, $headers, $options);
    }

    /**
     * @param  RequestInterface[] $requests
     *
     * @return array
     */
    protected function getBatchRequestOptions(array $requests): array
    {
        return array_map(function (RequestInterface $request) {
            return Json::decode((string) $request->getBody());
        }, $requests);
    }

    /**
     * @param  ResponseInterface $response
     *
     * @return ResponseInterface[]
     */
    protected function getBatchResponses(ResponseInterface $response): array
    {
        $results = Json::decode((string) $response->getBody(), true);

        return array_map(function (array $result) use ($response) {
            return $this->messageFactory->createResponse(
                $response->getStatusCode(),
                $response->getHeaders(),
                $result
            );
        }, $results);
    }
}
