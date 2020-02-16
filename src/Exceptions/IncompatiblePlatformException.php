<?php

namespace Spikkl\Api\Exceptions;

class IncompatiblePlatformException extends ApiException
{
    const INCOMPATIBLE_PHP_VERSION = 1001;
    const INCOMPATIBLE_JSON_EXTENSION = 1002;
}