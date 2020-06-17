<?php

try {

    // Initialize the Mollie API library with your API key
    require './initialize.php';

    $results = $spikkl->lookup('nld', '2611HB', '175');

    print_r($results);

} catch (\Spikkl\Api\Exceptions\ApiException $exception) {
    echo 'API call failed: ' . htmlspecialchars($exception->getMessage());
}