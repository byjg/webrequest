<?php

namespace ByJG\Util;

use ByJG\Util\Exception\CurlException;
use Closure;
use Exception;
use Psr\Http\Message\RequestInterface;
use stdClass;

class HttpClientParallel
{
    use ParseCurlTrait;

    /**
     * @var array
     */
    protected $curlClients = [];

    /**
     * @var HttpClient
     */
    protected $httpClient = null;

    /**
     * @var Closure
     */
    protected $defaultOnSuccess = null;

    /**
     * @var Closure
     */
    protected $defaultOnError = null;

    /**
     * @var array
     */
    protected $errorList = [];

    public function __construct(HttpClient $httpClient, Closure $defaultOnSuccess = null, Closure $defaultOnError = null)
    {
        $this->httpClient = $httpClient;

        $this->defaultOnSuccess = $defaultOnSuccess;
        if (is_null($defaultOnSuccess)) {
            $this->defaultOnSuccess = function () {};
        }

        $this->defaultOnError = $defaultOnError;
        if (is_null($defaultOnError)) {
            $this->defaultOnError = function () {};
        }
    }

    /**
     * @param RequestInterface $request
     * @param Closure|null $onSuccess
     * @param Closure|null $onError
     * @return $this
     */
    public function addRequest(
        RequestInterface $request,
        Closure $onSuccess = null,
        Closure $onError = null
    ): HttpClientParallel
    {
        if (is_null($onSuccess)) {
            $onSuccess = $this->defaultOnSuccess;
        }

        if (is_null($onError)) {
            $onError = $this->defaultOnError;
        }

        $data = new stdClass();
        $data->request = $request;
        $data->onSuccess = $onSuccess;
        $data->onError = $onError;
        $data->handle = null;
        $data->id = count($this->curlClients);

        $this->curlClients[] = $data;

        return $this;
    }

    /**
     * @throws CurlException
     */
    public function execute(): void
    {
        // multi handle
        $multiInitHandle = curl_multi_init();

        // loop through $data and create curl handles
        // then add them to the multi-handle
        foreach ($this->curlClients as $id => $object) {
            $object->handle = $this->httpClient->createCurlHandle($object->request);
            $this->curlClients["ch;" . ((int)$object->handle)] = $object;
            unset($this->curlClients[$id]);
            curl_multi_add_handle($multiInitHandle, $object->handle);
        }

        // execute the handles
        $running = null;
        $this->errorList = [];
        do {
            $status = curl_multi_exec($multiInitHandle, $running);

            // Check status
            switch ($status) {
                case CURLM_BAD_HANDLE:
                    throw new CurlException('Bad Handle');

                case CURLM_BAD_EASY_HANDLE:
                    throw new CurlException('Bad Easy Handle');

                case CURLM_OUT_OF_MEMORY:
                    throw new CurlException('Out of memory');

                case CURLM_INTERNAL_ERROR:
                    throw new CurlException('Internal Error');

            }

            $done = curl_multi_info_read($multiInitHandle);
            if ($done) {
                try {
                    $this->getContent($multiInitHandle, $done["handle"]);
                } catch (Exception $ex) {
                    $this->errorList[] = get_class($ex) . ": " . $ex->getMessage();
                }
            }

        } while ($running > 0);

        foreach ($this->curlClients as $object) {
            try {
                $this->getContent($multiInitHandle, $object->handle);
            } catch (Exception $ex) {
                $this->errorList[] = get_class($ex) . ": " . $ex->getMessage();
            }
        }

        // all done
        curl_multi_close($multiInitHandle);

        if (count($this->errorList) > 0) {
            throw new CurlException("Raised " . count($this->errorList) . " error(s). \n" . implode("\n", $this->errorList));
        }
    }

    /**
     * @param $multiInitHandle
     * @param $handle
     * @throws Psr7\MessageException
     */
    protected function getContent($multiInitHandle, $handle): void
    {
        $object = $this->curlClients["ch;" . ((int)$handle)];

        $body = curl_multi_getcontent($object->handle);
        $error = curl_error($object->handle);
        if (!empty($error)) {
            curl_multi_remove_handle($multiInitHandle, $object->handle);
            $closure = $object->onError;
            try {
                $closure($error, $object->id);
            } catch (Exception $ex) {
                $this->errorList[] = $ex;
            }
        }

        $response = $this->parseCurl($body, $object->handle, false);
        $closure = $object->onSuccess;

        try {
            $closure($response, $object->id);
        } catch (Exception $ex) {
            $this->errorList[] = $ex;
        }

        curl_multi_remove_handle($multiInitHandle, $object->handle);

        unset($this->curlClients["ch;" . ((int)$handle)]);
    }

    public function getErrorList(): array
    {
        return $this->errorList;
    }
}
