<?php

namespace Spikkl\Api\Exceptions;

use stdClass;
use Throwable;
use Exception;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;

class ApiException extends Exception
{
    protected const ACCESS_RESTRICTED_STATUS = 'ACCESS_RESTRICTED';
    protected const INVALID_API_KEY_STATUS = 'INVALID_API_KEY';
    protected const REVOKED_API_KEY_STATUS = 'REVOKED_API_KEY';
    protected const INVALID_REQUEST_STATUS = 'INVALID_REQUEST';
    protected const OUT_OF_RANGE_STATUS = 'OUT_OF_RANGE';
    protected const QUOTA_REACHED_STATUS = 'QUOTA_REACHED';
    protected const ZERO_RESULTS_STATUS = 'ZERO_RESULTS';

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

        if (static::isErrorApiResponse($object)) {
            switch ($object->status_code) {
                case self::ACCESS_RESTRICTED_STATUS:
                    return new AccessRestrictedException('Access restricted for this origin.', $response->getStatusCode(), $response, $previous);

                case self::INVALID_API_KEY_STATUS:
                    return new InvalidApiKeyException('Invalid API key provided.', $response->getStatusCode(), $response, $previous);

                case self::REVOKED_API_KEY_STATUS:
                    return new RevokedApiKeyException('Provided API key revoked.', $response->getStatusCode(), $response, $previous);

                case self::ZERO_RESULTS_STATUS:
                    return new ZeroResultsException('No results found.', $response->getStatusCode(), $response, $previous);

                case self::QUOTA_REACHED_STATUS:
                    return new QuotaReachedException('No requests left, consider upgrading.', $response->getStatusCode(), $response, $previous);

                case self::OUT_OF_RANGE_STATUS:
                    return new OutOfRangeException('Provided coordinate not in range.', $response->getStatusCode(), $response, $previous);

                case self::INVALID_REQUEST_STATUS:
                    return new InvalidRequestException('Invalid parameters provided.', $response->getStatusCode(), $response, $previous);
            }
        }

        switch ($response->getStatusCode()) {
            case 400:
                return new BadRequestException('Bad request.', $response->getStatusCode(), $response, $previous);

            case 401:
                return new UnauthorizedException('Unauthorized.', $response->getStatusCode(), $response, $previous);

            case 403:
                return new AccessDeniedException('Access denied.', $response->getStatusCode(), $response, $previous);

            case 404:
                return new PageNotFoundException('Not found.', $response->getStatusCode(), $response, $previous);

            case 500:
                return new ServerErrorException('Server error.', $response->getStatusCode(), $response, $previous);
        }

        return new static('API Error.', $response->getStatusCode(), $response, $previous);
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
     * Determine if the response contains error data.
     *
     * @param stdClass $response
     *
     * @return bool
     */
    protected static function isErrorApiResponse($response)
    {
        return property_exists($response, 'status_code') &&
               property_exists($response, 'status') &&
               $response->status === 'failed';
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