<?php

/**
 * Make sure to disable displaying errors in production mode.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Initialize the Spikkl API library with your API key.
 *
 * @see https://www.spikkl.nl/credentials
 */
$spikkl = new \Spikkl\Api\ApiClient();
$spikkl->setApiKey('a10cb02f50cded6fa34c98891c3c5777');