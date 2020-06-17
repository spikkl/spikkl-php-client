<p align="center">
    <img src="https://spikkl.nl/images/hub/github/php.png" width="128" height="128" />
</p>

<h1 align="center">Spikkl API client for PHP</h1>

[![Build Status](https://travis-ci.org/spikkl/spikkl-php-client.png)](https://travis-ci.org/spikkl/spikkl-php-client)

## Requirements ##
To use the Spikkl API client, the following things are required:
+ Get yourself a free [Spikkl account](https://www.spikkl.nl/signup). No sign up costs.
+ Follow [a few steps](https://www.spikkl.nl/account/billing) to enable a suitable subscription to talk to the API.
+ A valid API key which can be generated from your [Spikkl dashboard](https://www.spikkl.nl/account/credentials).
+ PHP >= 5.6.

## Installation
The easiest way to install the Spikkl API client is to require it with [Composer](https://getcomposer.org/doc/00-intro.md).

```bash
$ composer require spikkl/spikkl-php-client:^1.1
```

```json
{
  "require": {
    "spikkl/spikkl-php-client": "^1.2"
  }
}
```

### Manual ###
If you are not familiar with using composer, we have added a ZIP file to the release containing the API client and all the package normally installed by composer. Download ``spikkl-php-client.zip`` from the [release page](https://github.com/spikkl/spikkl-php-client/releases).

Include the ``vendor/autoload.php`` as shown in [Initialize example]((https://github.com/spikkl/spikkl-php-client/blob/master/examples/initialize.php)).

## Getting Started ##
Initializing the Spikkl API Client, and setting up your API key.

```php
$spikkl = new \Spikkl\Api\ApiClient();
$spikkl->setApiKey("API_KEY");
```

Perform a postal code lookup request.
```php
$results = $spikkl->lookup("NLD", "2611HB", "175");
```

Perform a lookup by coordinate (longitude, latitude).
```php
$results = $spikkl->reverse("NLD", 4.899431, 52.379189);
```

Note: longitude and latitude values will be rounded to 9 decimal places.

## Exception handling
The Spikkl API uses conventional HTTP response codes to indicate or failure of an API request. Code in the `2xx` range indicate success and code in the `4xx` range indicate failure. The Spikkl API client uses specific exceptions for specific failure responses.

```php
try {
    $results = $spikkl->lookup("NLD", "2611HB", "175");
} catch (\Spikkl\Api\Exceptions\AccessRestrictedException $exception) {
    // The API key is restricted for designated origins
} catch (\Spikkl\Api\Exceptions\InvalidApiKeyException $exception) {
    // The authentication with the Spikkl API failed
} catch (\Spikkl\Api\Exceptions\RevokedApiKeyException $exception) {
    // The provided API key is restricted.
} catch (\Spikkl\Api\Exceptions\ZeroResultsException $exception) {
    // The API call is successful but the API cannot find any results
} catch (\Spikkl\Api\Exceptions\QuotaReachedException $exception) {
    // The quota is reached for your current plan.
} catch (\Spikkl\Api\Exceptions\InvalidRequestException $exception) {
    // One of the query parameters (postal_code, street_number, or street_number_suffix)
    // might be invalid or missing
}
```

The reverse lookup request could throw additional exceptions.
```php
try {
    $results = $spikkl->reverse("NLD", 4.899431, 52.379189);
} catch (\Spikkl\Api\Exceptions\OutOfRangeException $exception) {
    // The coordinates provided do not correspond with the country code
} 
```

## API documentation ##
If you wish to learn more about our API, please visit the [Spikkl API Documentation](https://www.spikkl.nl/documentation).

## License ##
[BSD (Berkeley Software Distribution) License](https://opensource.org/licenses/bsd-license.php).
Copyright (c) 2020, Spikkl

## Support ##
Contact: [www.spikkl.nl](https://www.spikkl.nl) — support@spikkl.nl