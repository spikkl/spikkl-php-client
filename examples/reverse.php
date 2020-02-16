<?php

try {

    // Initialize the Mollie API library with your API key
    require './initialize.php';

    $results = $spikkl->reverse('nld', '53.109018', '7.084949');

    print_r($results);

} catch (\Spikkl\Api\Exceptions\ApiException $exception) {
    echo 'API call failed: ' . htmlspecialchars($exception->getMessage());
}