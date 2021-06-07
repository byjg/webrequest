<?php


namespace ByJG\Util;


use ByJG\Util\Psr7\Response;
use ByJG\Util\Psr7\MemoryStream;
use Psr\Http\Message\MessageInterface;

trait ParseCurlTrait
{
    /**
     * @param string $body
     * @param $curlHandle
     * @param bool $close
     * @return Response|MessageInterface
     * @throws Psr7\MessageException
     */
    public function parseCurl(string $body, $curlHandle, bool $close = true): Response
    {
        $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
        if ($close) {
            curl_close($curlHandle);
        }

        $response = Response::getInstance($status)
            ->withBody(new MemoryStream(substr($body, $headerSize)))
            ->withHeader("X-Effective-Url", $effectiveUrl);

        return $this->parseHeader($response, substr($body, 0, $headerSize));
    }

    /**
     * @param Response $response
     * @param string $rawHeaders
     * @return Response
     * @throws Psr7\MessageException
     */
    protected function parseHeader(Response $response, string $rawHeaders): Response
    {
        foreach (preg_split("/\r?\n/", $rawHeaders) as $headerLine) {
            $headerLine = explode(':', $headerLine, 2);

            if (isset($headerLine[1])) {
                $response = $response->withHeader($headerLine[0], preg_replace("/^\s+/", "", $headerLine[1]));
            }
        }

        return $response;
    }

}