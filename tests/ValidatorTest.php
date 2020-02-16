<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Spikkl\Api\Exceptions\UnsupportedCountryException;
use Spikkl\Api\Exceptions\ValidationException;
use Spikkl\Api\Validator;

class ValidatorTest extends TestCase
{
    const DEFAULT_COUNTRY_ISO3_CODE = 'nld';

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * Set up
     */
    protected function setUp()
    {
        parent::setUp();

        $this->validator = new Validator(self::DEFAULT_COUNTRY_ISO3_CODE);
    }

    /**
     * @test
     */
    public function unsupported_country_code_should_throw_exception()
    {
        $this->expectException(UnsupportedCountryException::class);
        $this->expectExceptionMessage('Unsupported country iso3 code provided: BEL.');

        new Validator('bel');
    }

    /**
     * @test
     */
    public function invalid_country_code_should_throw_exception()
    {
        $this->expectException(UnsupportedCountryException::class);
        $this->expectExceptionMessage('Unsupported country iso3 code provided: BELGIUM.');

        new Validator('belgium');
    }

    /**
     * @test
     */
    public function iso2_country_code_should_throw_exception()
    {
        $this->expectException(UnsupportedCountryException::class);
        $this->expectExceptionMessage('Unsupported country iso3 code provided: BE.');

        new Validator('be');
    }

    /**
     * @test
     */
    public function provided_country_code_will_be_lower_cased()
    {
        $validator = new Validator('NlD');

        $this->assertEquals('nld', $validator->countryIso3Code);
    }

    /**
     * @test
     */
    public function postal_code_validation_returns_postal_code()
    {
        $normalizedPostalCode = $this->validator->validateAndNormalizePostalCode('2611KL');

        $this->assertEquals('2611KL', $normalizedPostalCode);
    }

    /**
     * @test
     *
     * @dataProvider validPostalCodesToBeNormalized
     */
    public function postal_code_validation_returns_normalized_postal_code($postalCode)
    {
        $normalizedPostalCode = $this->validator->validateAndNormalizePostalCode($postalCode);

        $this->assertEquals('2611KL', $normalizedPostalCode);
    }

    /**
     * @test
     *
     * @dataProvider invalidPostalCodesToBeNormalized
     */
    public function invalid_postal_code_throws_exception($postalCode)
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid postal code provided [' . $postalCode . '] for country [nld].');

        $this->validator->validateAndNormalizePostalCode($postalCode);
    }

    /**
     * @test
     */
    public function street_number_validation_returns_street_number()
    {
        $normalizedStreetNumber = $this->validator->validateAndNormalizeStreetNumber('1');

        $this->assertEquals([ '1', '' ], $normalizedStreetNumber);
    }

    /**
     * @test
     *
     * @dataProvider validStreetNumbersToBeNormalized
     */
    public function street_number_validation_returns_normalized_street_number($streetNumber, $expected)
    {
        $normalizedStreetNumber = $this->validator->validateAndNormalizeStreetNumber($streetNumber);

        $this->assertEquals($expected, $normalizedStreetNumber);
    }

    /**
     * @test
     *
     * @dataProvider invalidPostalCodesToBeNormalized
     */
    public function invalid_street_number_throws_exception($streetNumber)
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid street number provided [' . $streetNumber . '] for country [nld].');

        $this->validator->validateAndNormalizeStreetNumber($streetNumber);
    }

    /**
     * @test
     */
    public function street_number_suffix_validation_returns_street_number_suffix()
    {
        $normalizedStreetNumber = $this->validator->validateAndNormalizeStreetNumberSuffix('a');

        $this->assertEquals('a', $normalizedStreetNumber);
    }

    /**
     * @test
     *
     * @dataProvider invalidStreetNumberSuffixToBeNormalized
     */
    public function invalid_street_number_suffix_throws_exception($streetNumberSuffix)
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid street number suffix provided [' . $streetNumberSuffix . '] for country [nld].');

        $this->validator->validateAndNormalizeStreetNumberSuffix($streetNumberSuffix);
    }

    /**
     * @test
     */
    public function coordinate_validation_returns_normalized_coordinate()
    {
        list($latitude, $longitude) = $this->validator->validateAndNormalizeCoordinate(4.35556, 52.00667);

        $this->assertEquals('4.35556', $latitude);
        $this->assertEquals('52.00667', $longitude);
    }

    /**
     * @test
     */
    public function invalid_latitude_throws_exception()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid latitude provided [invalid_latitude].');

        $this->validator->validateAndNormalizeCoordinate('invalid_latitude', 52.00667);
    }

    /**
     * @test
     */
    public function invalid_longitude_throws_exception()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid longitude provided [invalid_longitude].');

        $this->validator->validateAndNormalizeCoordinate(4.35556, 'invalid_longitude');
    }

    /**
     * @return array
     */
    public function validPostalCodesToBeNormalized()
    {
        return [
            [ '2611 KL' ], [ '2611kl' ], [ 'NL-2611KL' ], [ 'NLD-2611KL' ], [ '2611   KL' ]
        ];
    }

    /**
     * @return array
     */
    public function invalidPostalCodesToBeNormalized()
    {
        return [
            [ '2611 KLE' ], [ '2611SS' ], [ 'BE-2611KL' ], [ 'BEL-2611KL' ], [ '2611   KL   ' ]
        ];
    }

    /**
     * @return array
     */
    public function validStreetNumbersToBeNormalized()
    {
        return [
            [ 1, [ '1', '' ] ],
            [ '1', [ '1', '' ] ],
            [ '1a',  [ '1', 'a' ] ],
            [ '1abcde', [ '1', 'abcde' ] ],
            [ '1a1b2c', [ '1', 'a1b2c' ] ]
        ];
    }

    /**
     * @return array
     */
    public function invalidStreetNumbersToBeNormalized()
    {
        return [
            [ 'e' ], [ '01' ], [ '1 * b' ], [ '1abcdef' ], [ '12356' ]
        ];
    }

    /**
     * @return array
     */
    public function invalidStreetNumberSuffixToBeNormalized()
    {
        return [
            [ 'abcdef' ]
        ];
    }
}