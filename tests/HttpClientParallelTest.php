<?php

use ByJG\Util\Psr7\Request;
use ByJG\Util\Uri;
use PHPUnit\Framework\TestCase;

class HttpClientParallelTest extends TestCase
{

    const SERVER_TEST = 'http://localhost:8080/multirequest.php';

    protected function setUp(): void
    {

    }

    public function testMultiRequest()
    {
        $httpClient = \ByJG\Util\HttpClient::getInstance();

        $count = 0;
        $results = [];
        $fail = [];

        $onSucess = function ($response, $id) use (&$count, &$results) {
            $results[] = $response->getStatusCode() . "-" . $response->getBody();
            $count++;
        };

        $onError = function ($error, $id) use (&$fail) {
            $fail[] = $error;
        };

        $multi = new \ByJG\Util\HttpClientParallel(
            $httpClient,
            $onSucess,
            $onError
        );

        $request1 = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("param=1"));
        $request2 = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("param=2"));
        $request3 = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("param=3"));

        $multi
            ->addRequest($request1)
            ->addRequest($request2)
            ->addRequest($request3);

        $multi->execute();

        sort($results);

        $this->assertEquals(3, $count);
        $this->assertEquals(["200-1", "200-2", "200-3"], $results);
    }
}
