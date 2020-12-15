<?php

namespace Spikkl\Api;

use stdClass;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use Spikkl\Api\Exceptions\ApiException;
use Spikkl\Api\Exceptions\AccessRestrictedException;
use Spikkl\Api\Exceptions\InvalidApiKeyException;
use Spikkl\Api\Exceptions\InvalidRequestException;
use Spikkl\Api\Exceptions\OutOfRangeException;
use Spikkl\Api\Exceptions\QuotaReachedException;
use Spikkl\Api\Exceptions\RevokedApiKeyException;
use Spikkl\Api\Exceptions\ZeroResultsException;

class ApiClient
{
    /**
     * Version of the client.
     */
    const CLIENT_VERSION = '1.2.2';

    /**
     * Endpoint of the remote API.
     */
    const API_ENDPOINT = 'https://api.spikkl.nl/geo';

    /**
     * Default response timeout (in seconds).
     */
    const DEFAULT_TIMEOUT = 10;

    /**
     * HTTP Client handling the request.
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * Default API endpoint.
     *
     * @var string
     */
    protected $apiEndpoint = self::API_ENDPOINT;

    /**
     * The API key to access the service.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @var array
     */
    protected $versionStrings = [];

    /**
     * ApiClient constructor.
     *
     * @param ClientInterface|null $httpClient
     *
     * @throws ApiException
     */
    public function __construct(ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?
            $httpClient :
            new Client([
                RequestOptions::TIMEOUT => self::DEFAULT_TIMEOUT
            ]);

        // Check compatibility of the environment
        // - PHP >= 5.6
        // - JSON extension enabled
        $compatibilityChecker = new CompatibilityChecker();
        $compatibilityChecker->checkCompatibility();

        $this->addVersionString("Spikkl/" . self::CLIENT_VERSION);
        $this->addVersionString("PHP/" . phpversion());

        if (defined('\GuzzleHttp\ClientInterface::MAJOR_VERSION')) {
            $this->addVersionString('Guzzle/' . ClientInterface::MAJOR_VERSION);
        } elseif (defined('\GuzzleHttp\ClientInterface::VERSION')) {
            $this->addVersionString('Guzzle/' . ClientInterface::VERSION);
        }
    }

    /**
     * Set and validate the API key.
     *
     * @param string $apiKey
     *
     * @return ApiClient
     *
     * @throws ApiException
     */
    public function setApiKey($apiKey)
    {
        $apiKey = trim($apiKey);

        if ( ! preg_match('/^[0-9a-f]{32}$/', $apiKey)) {
            throw ApiException::create('Invalid api key: "' . $apiKey . '". Your API key should contain alpha-numeric characters only and must be 32 characters long.');
        }

        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Set the API endpoint.
     *
     * @param string $apiEndpoint
     *
     * @return ApiClient
     */
    public function setApiEndpoint($apiEndpoint)
    {
        $this->apiEndpoint = rtrim(trim($apiEndpoint), '/');

        return $this;
    }

    /**
     * Get the API endpoint.
     *
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->apiEndpoint;
    }

    /**
     * @param string $versionString
     *
     * @return ApiClient
     */
    public function addVersionString($versionString)
    {
        $this->versionStrings[] = str_replace([ " ", "\t", "\n", "\r" ], '-', $versionString);
        return $this;
    }

    /**
     * @param string $countryIso3Code
     * @param string $postalCode
     * @param int|null $streetNumber
     * @param string|null $streetNumberSuffix
     *
     * @return stdClass
     *
     * @throws ApiException
     */
    public function lookup($countryIso3Code, $postalCode, $streetNumber = null, $streetNumberSuffix = null)
    {
        $validator = new Validator($countryIso3Code);
        $postalCode = $validator->validateAndNormalizePostalCode($postalCode);

        if ($streetNumberSuffix !== null) {
            $streetNumberSuffix = $validator->validateAndNormalizeStreetNumberSuffix($streetNumberSuffix);
        }

        if ($streetNumber !== null) {
            list($streetNumber, $streetNumberSuffix) = $validator->validateAndNormalizeStreetNumber($streetNumber, $streetNumberSuffix);
        }

        $response = $this->performRequest(
            'GET',
            strtolower($countryIso3Code) . '/lookup.json',
            [
                'postal_code' => $postalCode,
                'street_number' => $streetNumber,
                'street_number_suffix' => $streetNumberSuffix
            ]
        );

        return $response->results;
    }

    /**
     * @param string $countryIso3Code
     * @param string|float $longitude
     * @param string|float $latitude
     *
     * @return stdClass
     *
     * @throws ApiException
     */
    public function reverse($countryIso3Code, $longitude, $latitude)
    {
        $validator = new Validator($countryIso3Code);
        list($longitude, $latitude) = $validator->validateAndNormalizeCoordinate($longitude, $latitude);

        $response = $this->performRequest(
            'GET',
            strtolower($countryIso3Code) . '/reverse.json',
            [
                'longitude' => $longitude,
                'latitude' => $latitude
            ]
        );

        return $response->results;
    }

    /**
     * Perform the request.
     *
     * @param string $httpMethod
     * @param string $apiMethod
     * @param array $httpParams
     * @param string|null $httpBody
     *
     * @return stdClass
     *
     * @throws ApiException
     */
    public function performRequest($httpMethod, $apiMethod, $httpParams = [], $httpBody = null)
    {
        if (empty($this->apiKey)) {
            throw ApiException::create('You have not set an API key. Please use setApiKey() to set the API key.');
        }

        $url = $this->apiEndpoint . '/' . $apiMethod;

        $httpParams = array_merge([
            'key' => $this->apiKey
        ], $httpParams);

        $userAgent = implode(' ', $this->versionStrings);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $userAgent
        ];

        // Send PHP client information to the service
        if (function_exists('php_uname')) {
            $headers['X-Spikkl-Client-Info'] = php_uname();
        }

        $url .= '?'.urldecode(http_build_query($httpParams));

        $request = new Request($httpMethod, $url, $headers, $httpBody);

        try {
            $response = $this->httpClient->send($request, [ RequestOptions::HTTP_ERRORS => false ]);
        } catch (GuzzleException $exception) {
            throw ApiException::createFromGuzzleException($exception);
        }

        if ( ! $response) {
            throw ApiException::create('Did not receive any API response.');
        }

        return $this->parseResponseBody($response);
    }

    /**
     * Parse the PSR-7 Response body.
     *
     * @param ResponseInterface $response
     *
     * @return stdClass|null
     *
     * @throws ApiException
     */
    private function parseResponseBody(ResponseInterface $response)
    {
        $body = (string) $response->getBody();

        if (empty($body)) {
            throw ApiException::create('No response body found.');
        }

        $object = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ApiException::create('Unable to decode Spikkl response: "' . $body . '".');
        }

        if ($response->getStatusCode() >= 400) {
            throw ApiException::createFromResponse($response);
        }

        return $object;
    }
}