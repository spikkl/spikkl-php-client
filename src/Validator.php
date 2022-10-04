<?php

namespace Spikkl\Api;

use Spikkl\Api\Exceptions\ApiException;
use Spikkl\Api\Exceptions\UnsupportedCountryException;
use Spikkl\Api\Exceptions\ValidationException;

class Validator
{
    private static array $regexPostalCode = [
        'nld' => '/^(?:(?:nld|nl)\-?)?[1-9][0-9]{3}\s*(?!sa|sd|ss)[a-z]{2}$/i'
    ];

    private static array $regexStreetNumber = [
        'nld' => '/^([1-9][0-9]{0,4})\s?(?:[a-z])?\s?(?:[a-z0-9]{1,4})?$/'
    ];

    private static array $regexStreetNumberSuffix = [
        'nld' => '/^(?:[a-z])?\s?(?:[a-z0-9]{1,4})?$/i'
    ];

    private static string $regexWhitelistedCountryIso3Codes = '/^nld$/i';

    public string $countryIso3Code;

    /**
     * Validator constructor.
     *
     * @param string $countryIso3Code
     *
     * @throws ApiException
     */
    public function __construct(string $countryIso3Code)
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
    public function validateAndNormalizePostalCode(string $postalCode): string
    {
        if ( ! preg_match(static::$regexPostalCode[$this->countryIso3Code], $postalCode)) {
            throw ValidationException::create(sprintf('Invalid postal code provided [%s] for country [%s].', $postalCode, $this->countryIso3Code));
        }

        $postalCode = strtoupper($postalCode);

        // Replace attached country codes
        $postalCode = preg_replace('/^[a-z]{1,3}-?/i', '', $postalCode);

        return preg_replace('/\s+/', '', $postalCode);
    }

    /**
     * @param string $streetNumber
     * @param string|null $streetNumberSuffix
     *
     * @return array
     *
     * @throws ApiException
     */
    public function validateAndNormalizeStreetNumber(string $streetNumber, ?string $streetNumberSuffix = null): array
    {
        if ( ! preg_match(static::$regexStreetNumber[$this->countryIso3Code], $streetNumber)) {
            throw ValidationException::create(sprintf('Invalid street number provided [%s] for country [%s].', $streetNumber, $this->countryIso3Code));
        }

        if (preg_match('/^(?<number>[1-9][0-9]{0,4})\s*(?<suffix>(?:[a-z])?(?:[a-z0-9]{1,4})?)$/i', $streetNumber, $matches)) {
            return array_map('trim', [ $matches['number'], $matches['suffix'] ?? $streetNumberSuffix ]);
        }

        return array_map('trim', [ $streetNumber, $streetNumberSuffix ]);
    }

    /**
     * @param string $streetNumberSuffix
     *
     * @return string
     *
     * @throws ApiException
     */
    public function validateAndNormalizeStreetNumberSuffix(string $streetNumberSuffix): string
    {
        if ( ! preg_match(static::$regexStreetNumberSuffix[$this->countryIso3Code], $streetNumberSuffix)) {
            throw ValidationException::create(sprintf('Invalid street number suffix provided [%s] for country [%s].', $streetNumberSuffix, $this->countryIso3Code));
        }

        return trim($streetNumberSuffix);
    }

    /**
     * Validate the coordinate. Length of longitude or latitude is limited to 14 characters.
     *
     * @param string $longitude
     * @param string $latitude
     *
     * @return array
     *
     * @throws ApiException
     */
    public function validateAndNormalizeCoordinate($longitude, $latitude): array
    {
        if ( ! preg_match('/^([+\-])?((\d((\.)|\.\d+)?)|(0*?\d\d((\.)|\.\d+)?)|(0*?1[0-7]\d((\.)|\.\d+)?)|(0*?180((\.)|\.0+)?))$/', $longitude)) {
            throw ValidationException::create(sprintf('Invalid longitude provided [%s].', $longitude));
        }

        if ( ! preg_match('/^([+\-])?((\d((\.)|\.\d+)?)|(0*?[0-8]\d((\.)|\.\d+)?)|(0*?90((\.)|\.0+)?))$/', $latitude)) {
            throw ValidationException::create(sprintf('Invalid latitude provided [%s].', $latitude));
        }

        return [ number_format($longitude, 9), number_format($latitude, 9) ];
    }

    /**
     * Validate the country iso3 code provided.
     *
     * @param string $countryIso3Code
     *
     * @return void
     *
     * @throws ApiException
     */
    protected function validateCountryIso3Code(string $countryIso3Code): void
    {
        if ( ! preg_match(static::$regexWhitelistedCountryIso3Codes, $countryIso3Code)) {

            $countryIso3Code = strtoupper($countryIso3Code);

            throw UnsupportedCountryException::create(
                sprintf('Unsupported country iso3 code provided: %s.', $countryIso3Code),
                UnsupportedCountryException::UNSUPPORTED_COUNTRY_ISO3_CODE
            );
        }
    }
}