<?php

namespace Spikkl\Api;

use Spikkl\Api\Exceptions\ApiException;
use Spikkl\Api\Exceptions\IncompatiblePlatformException;

class CompatibilityChecker
{
    const MIN_PHP_VERSION = '7.2.0';

    /**
     * Check whether the platform meets the PHP requirements
     * and has the JSON extension enabled.
     *
     * @return void
     *
     * @throws ApiException
     */
    public function checkCompatibility(): void
    {
        if ( ! $this->satisfiesPHPVersion()) {
            throw IncompatiblePlatformException::create(
                'The client required PHP version >= ' . self::MIN_PHP_VERSION . ', you have ' . PHP_VERSION . '.',
                IncompatiblePlatformException::INCOMPATIBLE_PHP_VERSION
            );
        }

        if ( ! $this->satisfiesJSONExtension()) {
            throw IncompatiblePlatformException::create(
                'PHP extension json is not enabled. Please make sure to enable "json" in your PHP configuration.',
                IncompatiblePlatformException::INCOMPATIBLE_JSON_EXTENSION
            );
        }
    }

    /**
     * Check whether the platform meets the PHP requirements.
     *
     * @return bool
     */
    public function satisfiesPHPVersion(): bool
    {
        return (bool) version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>=');
    }

    /**
     * Check if the JSON extension is enabled.
     *
     * @return bool
     */
    public function satisfiesJSONExtension(): bool
    {
        if (function_exists('extension_loaded') && extension_loaded('json')) {
            return true;
        } else if (function_exists('json_encode')) {
            return true;
        }

        return false;
    }
}