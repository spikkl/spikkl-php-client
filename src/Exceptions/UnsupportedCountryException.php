<?php

namespace Spikkl\Api\Exceptions;

class UnsupportedCountryException extends ApiException
{
    const UNSUPPORTED_VALIDATION_REGEX = 2001;
    const UNSUPPORTED_COUNTRY_ISO3_CODE = 2002;

}