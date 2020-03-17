<?php

namespace Spikkl\Api;

use Spikkl\Api\Exceptions\ApiException;
use Spikkl\Api\Exceptions\UnsupportedCountryException;
use Spikkl\Api\Exceptions\ValidationException;

class Validator
{
    /**
     * @var array
     */
    private static $regexPostalCode = [
        'nld' => '/^(?:(?:nld|nl)\-?)?[1-9][0-9]{3}\s*(?!sa|sd|ss)[a-z]{2}$/i'
    ];

    /**
     * @var array
     */
    private static $regexStreetNumber = [
        'nld' => '/^([1-9][0-9]{0,4})\s?(?:[a-z])?\s?(?:[a-z0-9]{1,4})?$/'
    ];

    /**
     * @var array
     */
    private static $regexStreetNumberSuffix = [
        'nld' => '/^(?:[a-z])?\s?(?:[a-z0-9]{1,4})?$/i'
    ];

    /**
     * @var string
     */
    private static $regexWhitelistedCountryIso3Codes = '/^nld$/i';

    /**
     * @var string
     */
    public $countryIso3Code;

    /**
     * Validator constructor.
     *
     * @param $countryIso3Code
     *
     * @throws ApiException
     */
    public function __construct($countryIso3Code)
    {
        $this->validateCountryIso3Code($countryIso3Code);

        $this->countryIso3Code = strtolower($countryIso3Code);
    }

    /**
     * Validate the given postal code by the country
     * code depended regular expression, normalize the
     * postal code after a successful validation.
     *
     * @param string $postalCode
     *
     * @return string
     *
     * @throws ApiException
     */
    public function validateAndNormalizePostalCode($postalCode)
    {
        if ( ! preg_match(static::$regexPostalCode[$this->countryIso3Code], $postalCode)) {
            throw ValidationException::create("Invalid postal code provided [{$postalCode}] for country [{$this->countryIso3Code}].");
        }

        $postalCode = strtoupper($postalCode);

        // Replace attached country codes
        $postalCode = preg_replace('/^[a-z]{1,3}\-?/i', '', $postalCode);

        return preg_replace('/\s+/', '', $postalCode);
    }

    /**
     * @param string|int $streetNumber
     * @param string|null $streetNumberSuffix
     *
     * @return array
     *
     * @throws ApiException
     */
    public function validateAndNormalizeStreetNumber($streetNumber, $streetNumberSuffix = null)
    {
        if ( ! preg_match(static::$regexStreetNumber[$this->countryIso3Code], $streetNumber)) {
            throw ValidationException::create("Invalid street number provided [{$streetNumber}] for country [{$this->countryIso3Code}].");
        }

        if (preg_match('/^(?<number>[1-9][0-9]{0,4})\s*(?<suffix>(?:[a-z])?(?:[a-z0-9]{1,4})?)$/i', $streetNumber, $matches)) {
            return array_map('trim', [ $matches['number'], $matches['suffix'] ? $matches['suffix'] : $streetNumberSuffix ]);
        };

        return array_map('trim', [ $streetNumber, $streetNumberSuffix ]);
    }

    /**
     * @param string $streetNumberSuffix
     *
     * @return string
     *
     * @throws ApiException
     */
    public function validateAndNormalizeStreetNumberSuffix($streetNumberSuffix)
    {
        if ( ! preg_match(static::$regexStreetNumberSuffix[$this->countryIso3Code], $streetNumberSuffix)) {
            throw ValidationException::create("Invalid street number suffix provided [{$streetNumberSuffix}] for country [{$this->countryIso3Code}].");
        }

        return trim($streetNumberSuffix);
    }

    /**
     * Validate the coordinate. Length of longitude or latitude is limited to 14 characters.
     *
     * @param string|float $longitude
     * @param string|float $latitude
     *
     * @return array
     *
     * @throws ApiException
     */
    public function validateAndNormalizeCoordinate($longitude, $latitude)
    {
        if ( ! preg_match('/^(\+|-)?((\d((\.)|\.\d+)?)|(0*?\d\d((\.)|\.\d+)?)|(0*?1[0-7]\d((\.)|\.\d+)?)|(0*?180((\.)|\.0+)?))$/', $longitude)) {
            throw ValidationException::create("Invalid longitude provided [{$longitude}].");
        }

        if ( ! preg_match('/^(\+|-)?((\d((\.)|\.\d+)?)|(0*?[0-8]\d((\.)|\.\d+)?)|(0*?90((\.)|\.0+)?))$/', $latitude)) {
            throw ValidationException::create("Invalid latitude provided [{$latitude}].");
        }

        return [ number_format($longitude, 9), number_format($latitude, 9) ];
    }

    /**
     * Validate the country iso3 code provided.
     *
     * @param $countryIso3Code
     *
     * @return void
     *
     * @throws ApiException
     */
    protected function validateCountryIso3Code($countryIso3Code)
    {
        if ( ! preg_match(static::$regexWhitelistedCountryIso3Codes, $countryIso3Code)) {

            $countryIso3Code = strtoupper($countryIso3Code);

            throw UnsupportedCountryException::create(
                "Unsupported country iso3 code provided: {$countryIso3Code}.",
                UnsupportedCountryException::UNSUPPORTED_COUNTRY_ISO3_CODE
            );
        }
    }
}