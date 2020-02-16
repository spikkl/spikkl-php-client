<?php

namespace Spikkl\Api\Tests\Exceptions;


use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Spikkl\Api\Exceptions\ApiException;

class ApiExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function transforms_guzzle_exception_into_api_exception()
    {
        $response = new Response(422, [], '{
            "status": "failed",
            "status_code": "INVALID_REQUEST"
        }');

        $guzzleException = new RequestException(
            'Something went wrong...',
            new Request('GET', 'https://api.spikkl.nl/geo/nld/lookup.json'),
            $response
        );

        $exception = ApiException::createFromGuzzleException($guzzleException);

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(Response::class, $exception->getResponse());

        $this->assertEquals($response, $exception->getResponse());
        $this->assertTrue($exception->hasResponse());
    }
}