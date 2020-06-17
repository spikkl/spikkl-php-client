<?php

namespace Spikkl\Api\Tests\Exceptions;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Spikkl\Api\Exceptions\AccessDeniedException;
use Spikkl\Api\Exceptions\AccessRestrictedException;
use Spikkl\Api\Exceptions\ApiException;
use Spikkl\Api\Exceptions\BadRequestException;
use Spikkl\Api\Exceptions\InvalidApiKeyException;
use Spikkl\Api\Exceptions\InvalidRequestException;
use Spikkl\Api\Exceptions\OutOfRangeException;
use Spikkl\Api\Exceptions\PageNotFoundException;
use Spikkl\Api\Exceptions\QuotaReachedException;
use Spikkl\Api\Exceptions\RevokedApiKeyException;
use Spikkl\Api\Exceptions\ServerErrorException;
use Spikkl\Api\Exceptions\UnauthorizedException;
use Spikkl\Api\Exceptions\ZeroResultsException;

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

    /**
     * @test
     *
     * @dataProvider invalid_api_responses
     */
    public function transforms_api_response_exception_into_valid_api_exception_sub_class($statusCode, $status, $exception)
    {
        $body = $status ?
            json_encode([ 'status' => 'failed', 'status_code' => $status ]) :
            json_encode('');

        $response = new Response($statusCode, [], $body);

        $this->assertInstanceOf($exception, ApiException::createFromResponse($response));
    }

    /**
     *
     * @return array
     */
    public function invalid_api_responses()
    {
        return [
            [ 403, 'ACCESS_RESTRICTED', AccessRestrictedException::class ],
            [ 401, 'INVALID_API_KEY', InvalidApiKeyException::class ],
            [ 403, 'REVOKED_API_KEY', RevokedApiKeyException::class ],
            [ 404, 'ZERO_RESULTS', ZeroResultsException::class ],
            [ 429, 'QUOTA_REACHED', QuotaReachedException::class ],
            [ 400, 'OUT_OF_RANGE', OutOfRangeException::class ],
            [ 400, 'INVALID_REQUEST', InvalidRequestException::class ],
            [ 400, null, BadRequestException::class ],
            [ 401, null, UnauthorizedException::class ],
            [ 403, null, AccessDeniedException::class ],
            [ 404, null, PageNotFoundException::class ],
            [ 500, null, ServerErrorException::class ],
        ];
    }
}