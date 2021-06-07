<?php

namespace ByJG\Util;

use ByJG\Util\Psr7\Response;
use ByJG\Util\Psr7\MemoryStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockClient extends HttpClient
{
    /**
     * @var Response
     */
    protected $expectedResponse;


    /**
     * MockClient constructor.
     * @param Response|MessageInterface $expectedResponse
     */
    public function __construct(Response $expectedResponse = null)
    {
        if (is_null($expectedResponse)) {
            $expectedResponse = (new Response(200))
                                    ->withBody(new MemoryStream('{"key":"value"}'));
        }
        $this->expectedResponse = $expectedResponse;
    }

    /**
     * @return MockClient
     */
    public static function getInstance()
    {
        return new MockClient(new Response(200));
    }

    /**
     * @param RequestInterface $request
     * @return Response
     * @throws Psr7\MessageException
     */
    public function sendRequest(RequestInterface $request): Response
    {
        $curlHandle = $this->createCurlHandle($request);

        return $this->parseCurl("", $curlHandle);
    }

    public function parseCurl(string $body, $curlHandle, $close = true): Response
    {
        return $this->expectedResponse;
    }

    /**
     * @param RequestInterface $request
     * @return resource
     * @throws Exception\CurlException
     */
    public function createCurlHandle(RequestInterface $request)
    {
        $this->request = clone $request;
        $this->curlOptions = [];
        $this->clearRequestMethod();
        $this->defaultCurlOptions();

        $this->setCredentials();
        $this->setHeaders();
        $this->setMethod();
        $this->setBody();

        return $this->curlInit();
    }



    /**
     * Request the method using the CURLOPT defined previously;
     *
     * @return resource
     */
    protected function curlInit()
    {
        return 65535;
    }

    /**
     * @return array
     */
    public function getCurlConfiguration(): array
    {
        return $this->curlOptions;
    }

    /**
     * @return RequestInterface
     */
    public function getRequestedObject(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getExpectedResponse(): ResponseInterface
    {
        return $this->expectedResponse;
    }
}
