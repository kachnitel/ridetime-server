<?php

use Kachnitel\RideTimeServer\Database\Connector;

require_once(__DIR__ . '/vendor/autoload.php');

$secretsFile = file_get_contents(__DIR__ . '/.secrets.json');
$secrets = json_decode($secretsFile);

$db = new Connector();
$db->init($secrets->db);


echo json_encode($db->getUsers());
