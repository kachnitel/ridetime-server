<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Kachnitel\RideTimeServer\Database\Connector;
use Kachnitel\RideTimeServer\Database\Users;
use Kachnitel\RideTimeServer\Database\Rides;

require_once(__DIR__ . '/vendor/autoload.php');

$configFile = file_get_contents(__DIR__ . '/config.json');
$config = json_decode($configFile, true);

$slimConfig = $config['slim'];
// $slimConfig['displayErrorDetails'] = true;
// $slimConfig['addContentLengthHeader'] = false;

$secretsFile = file_get_contents(__DIR__ . '/.secrets.json');
$secrets = json_decode($secretsFile, true);
// $config['db']['host']   = 'localhost';
// $config['db']['user']   = 'user';
// $config['db']['pass']   = 'password';
// $config['db']['dbname'] = 'exampleapp';
$slimConfig['db'] = $secrets['db'];

$app = new \Slim\App(['settings' => $slimConfig]);
$container = $app->getContainer();

$container['logger'] = function($c) use ($config) {
    $logger = new \Monolog\Logger($config['appName']);

    $file_handler = new \Monolog\Handler\StreamHandler($config['logPath']);
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    $db = new Connector();
    $db->init($c['settings']['db']);

    return $db;
};

$app->get('/rides', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo('GET rides');

    $rides = new Rides($this->db);

    return $response->withJson($rides->getRides());
});

$app->get('/users/{id}', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo('GET users/{id}', $args);

    $userId = (int) $args['id'];

    $users = new Users($this->db);

    return $response->withJson($users->getUser($userId));
});

$app->run();
