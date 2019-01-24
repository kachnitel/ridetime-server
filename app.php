<?php

declare(strict_types=1);

use Kachnitel\RideTimeServer\Database\Connector;
use Kachnitel\RideTimeServer\Database\Users;

// TODO: use guzzle?
header('Content-Type: application/json');

require_once(__DIR__ . '/vendor/autoload.php');

$secretsFile = file_get_contents(__DIR__ . '/.secrets.json');
$secrets = json_decode($secretsFile);

$db = new Connector();
$db->init($secrets->db);

$users = new Users($db);

echo json_encode(
    // $users->getUsers(),
    $users->getUser(1016)
);
