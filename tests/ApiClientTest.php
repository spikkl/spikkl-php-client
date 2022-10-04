<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spikkl\Api\ApiClient;
use Spikkl\Api\Exceptions\ApiException;

class ApiClientTest extends TestCase
{
    /**
     * @var ClientInterface|MockObject
     */
    private $guzzleClient;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * Set up.
     *
     * @throws ApiException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->guzzleClient = $this->createMock(Client::class);
        $this->apiClient = new ApiClient($this->guzzleClient);

        $this->apiClient->setApiKey('7da3eda72a52d350f2c6aabe4a414502');
    }

    /**
     * @test
     */
    public function providing_invalid_api_key_throws_exception()
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid api key: "some_api_key". Your API key should contain alpha-numeric characters only and must be 32 characters long.');

        $apiClient = new ApiClient($this->guzzleClient);
        $apiClient->setApiKey('some_api_key');
    }

    /**
     * @test
     */
    public function can_set_custom_endpoint()
    {
        $apiClient = new ApiClient($this->guzzleClient);
        $apiClient->setApiEndpoint('some_custom_endpoint');

        $this->assertEquals('some_custom_endpoint', $apiClient->getApiEndpoint());
    }

    /**
     * @test
     */
    public function performing_a_request_before_setting_the_api_key_throws_exception()
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('You have not set an API key. Please use setApiKey() to set the API key.');

        $apiClient = new ApiClient($this->guzzleClient);
        $apiClient->performRequest('GET', 'lookup');
    }

    /**
     * @test
     */
    public function perform_request_will_return_body_as_object()
    {
        $response = new Response(200, [], '{"results":[]}');

        $this->guzzleClient
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $parsedResponse = $this->apiClient->performRequest('GET', '');

        $this->assertEquals(
            (object) [ 'results' => [] ],
            $parsedResponse
        );
    }

    /**
     * @test
     */
    public function perform_http_call_creates_api_exception_correctly()
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Invalid parameters provided.');
        $this->expectExceptionCode(422);

        $response = new Response(422, [], '{
            "status": "failed",
            "status_code": "INVALID_REQUEST"
        }');

        $this->guzzleClient
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        try {
            $this->apiClient->performRequest('GET', '');
        } catch (ApiException $exception) {
            $this->assertEquals($response, $exception->getResponse());

            throw $exception;
        }
    }

    /**
     * @test
     */
    public function response_body_can_be_read_multiple_time_if_middleware_reads_it_first()
    {
        $response = new Response(200, [], '{"results":[]}');

        $bodyAsReadFromMiddleware = (string) $response->getBody();

        $this->guzzleClient
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $parsedResponse = $this->apiClient->performRequest('GET', '');

        $this->assertEquals(
            '{"results":[]}',
            $bodyAsReadFromMiddleware
        );

        $this->assertEquals(
            (object) ['results' => []],
            $parsedResponse
        );
    }

    /**
     * @test
     */
    public function empty_response_body_throws_exception()
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('No response body found.');

        $response = new Response(200, [], null);

        $this->guzzleClient
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $this->apiClient->performRequest('GET', '');
    }

    /**
     * @test
     */
    public function invalid_json_response_throws_exception()
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Unable to decode Spikkl response: "some_body".');

        $response = new Response(200, [], 'some_body');

        $this->guzzleClient
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $this->apiClient->performRequest('GET', '');
    }

    /**
     * @test
     */
    public function perform_valid_lookup_request_returns_valid_response_body()
    {
        $response = new Response(200, [], '{
            "status": "ok",
            "meta": {"trace_id":"some_trace_id","timestamp":1234},
            "results":[{
                "postal_code": "2611KL",
                "street_name": "Trompetstraat",
                "street_number": "2",
                "street_number_affix": "a",
                "city": "Delft",
                "formatted_address": "Trompetstraat, 2611KL Delft, Nederland"
            }]
        }');

        $this->guzzleClient
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $response = $this->apiClient->lookup('nld', '2611KL', 2, 'a');

        $this->assertEquals('2611KL', $response[0]->postal_code);
        $this->assertEquals('Trompetstraat', $response[0]->street_name);
        $this->assertEquals('Delft', $response[0]->city);
        $this->assertEquals('Trompetstraat, 2611KL Delft, Nederland', $response[0]->formatted_address);
    }

    /**
     * @test
     */
    public function perform_valid_reverse_request_returns_valid_response_body()
    {
        $response = new Response(200, [], '{
            "status": "ok",
            "meta": {"trace_id":"some_trace_id","timestamp":1234},
            "results":[{
                "postal_code": "2611KL",
                "street_name": "Trompetstraat",
                "street_number": "2",
                "street_number_affix": "a",
                "city": "Delft",
                "formatted_address": "Trompetstraat, 2611KL Delft, Nederland"
            }]
        }');

        $this->guzzleClient
            ->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $response = $this->apiClient->reverse('nld', 4.35556, 52.00667);

        $this->assertEquals('2611KL', $response[0]->postal_code);
        $this->assertEquals('Trompetstraat', $response[0]->street_name);
        $this->assertEquals('Delft', $response[0]->city);
        $this->assertEquals('Trompetstraat, 2611KL Delft, Nederland', $response[0]->formatted_address);
    }
}