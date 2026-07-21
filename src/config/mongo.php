<?php
$autoload = __DIR__ . '/../vendor/autoload.php';

if (!is_file($autoload)) {
    throw new RuntimeException("Composer autoload not found: " . $autoload);
}

require_once $autoload;

$client = new MongoDB\Client('mongodb://mongodb:27017');
$db = $client->selectDatabase('madaratrade');
