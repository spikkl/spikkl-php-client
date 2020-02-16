<?php

namespace Spikkl\Api\Exceptions;

use stdClass;
use Throwable;
use Exception;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;

class ApiException extends Exception
{
    /**
     * @var ResponseInterface|null;
     */
    protected $response;

    /**
     * ApiException constructor.
     *
     * @param string $message
     * @param int $code
     * @param ResponseInterface|null $response
     * @param Throwable|null $previous
     */
    public function __construct(
        $message = '',
        $code = 0,
        ResponseInterface $response = null,
        Throwable $previous = null
    ) {
        $this->response = $response;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a new instance of the API exception class.
     *
     * @param string $message
     * @param int|null $code
     * @param Throwable|null $previous
     *
     * @return ApiException
     */
    public static function create(
        $message,
        $code = null,
        Throwable $previous = null
    ) {
        return new static($message, $code, null, $previous);
    }

    /**
     * Create a new instance from the given response.
     *
     * @param ResponseInterface $response
     * @param Throwable|null $previous
     *
     * @return ApiException
     *
     * @throws ApiException
     */
    public static function createFromResponse($response, Throwable $previous = null)
    {
        $object = static::parseResponseBody($response);

        return new static(
            "Error executing API call ({$object->status_code}).",
            $response->getStatusCode(),
            $response,
            $previous
        );
    }

    /**
     * @param GuzzleException $exception
     * @param Throwable|null $previous
     *
     * @return ApiException
     *
     * @throws ApiException
     */
    public static function createFromGuzzleException($exception, Throwable $previous = null)
    {
        if (method_exists($exception, 'hasResponse') && method_exists($exception, 'getResponse')) {
            if ($exception->hasResponse()) {
                return static::createFromResponse($exception->getResponse());
            }
        }

        return new static($exception->getMessage(), $exception->getCode(), null, $previous);
    }

    /**
     * Parse the response body.
     *
     * @param ResponseInterface $response
     *
     * @return stdClass
     *
     * @throws ApiException
     */
    protected static function parseResponseBody($response)
    {
        $body = (string) $response->getBody();

        $object = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new static("Unable to decode Spikkl response: '{body}'.");
        }

        return $object;
    }

    /**
     * Get the response attached to the exception.
     *
     * @return ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Determine if the exception has a response attached.
     *
     * @return bool
     */
    public function hasResponse()
    {
        return ! ($this->response === null);
    }
}