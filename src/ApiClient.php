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

class ApiClient
{
    const CLIENT_VERSION = '1.3.0';

    const API_ENDPOINT = 'https://api.spikkl.nl/geo';

    const DEFAULT_TIMEOUT = 10;

    protected ClientInterface $httpClient;

    protected string $apiEndpoint = self::API_ENDPOINT;

    protected string $apiKey;

    protected array $versionStrings = [];

    /**
     * ApiClient constructor.
     *
     * @param ClientInterface|null $httpClient
     *
     * @throws ApiException
     */
    public function __construct(ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ??
            new Client([
                RequestOptions::TIMEOUT => self::DEFAULT_TIMEOUT
            ]);

        // Check compatibility of the environment
        // - PHP >= 5.6
        // - JSON extension enabled
        $compatibilityChecker = new CompatibilityChecker();
        $compatibilityChecker->checkCompatibility();

        $this->addVersionString('Spikkl/' . self::CLIENT_VERSION);
        $this->addVersionString('PHP/' . phpversion());

        if (defined('\GuzzleHttp\ClientInterface::MAJOR_VERSION')) {
            $this->addVersionString('Guzzle/' . ClientInterface::MAJOR_VERSION);
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
    public function setApiKey(string $apiKey): self
    {
        $apiKey = trim($apiKey);

        if ( ! preg_match('/^[0-9a-f]{32}$/', $apiKey)) {
            throw ApiException::create(sprintf('Invalid api key: "%s". Your API key should contain alpha-numeric characters only and must be 32 characters long.', $apiKey));
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
    public function setApiEndpoint(string $apiEndpoint): self
    {
        $this->apiEndpoint = rtrim(trim($apiEndpoint), '/');

        return $this;
    }

    /**
     * Get the API endpoint.
     *
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * @param string $versionString
     *
     * @return ApiClient
     */
    public function addVersionString(string $versionString): self
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
    public function lookup(string $countryIso3Code, string $postalCode, ?int $streetNumber = null, ?string $streetNumberSuffix = null)
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
     * @param float $longitude
     * @param float $latitude
     *
     * @return stdClass
     *
     * @throws ApiException
     */
    public function reverse(string $countryIso3Code, $longitude, $latitude)
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
    public function performRequest(string $httpMethod, string $apiMethod, array $httpParams = [], ?string $httpBody = null)
    {
        if (empty($this->apiKey)) {
            throw ApiException::create('You have not set an API key. Please use setApiKey() to set the API key.');
        }

        $url = $this->apiEndpoint . '/' . $apiMethod;

        $httpParams = array_merge([
            'key' => $this->apiKey
        ], $httpParams);

        $userAgent = implode(';', $this->versionStrings);

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
    private function parseResponseBody(ResponseInterface $response): ?stdClass
    {
        $body = (string) $response->getBody();

        if (empty($body)) {
            throw ApiException::create('No response body found.');
        }

        $object = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ApiException::create(sprintf('Unable to decode Spikkl response: "%s".', $body));
        }

        if ($response->getStatusCode() >= 400) {
            throw ApiException::createFromResponse($response);
        }

        return $object;
    }
}