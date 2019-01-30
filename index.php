<?php

declare(strict_types=1);

// use RideTimeServer\Database\Connector;

// use RideTimeServer\Entities\User;

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/bootstrap.php';

// $classLoader = new \Doctrine\Common\ClassLoader('Entities','./entities');
// $classLoader->register();
require_once './entities/User.php';
require_once './entities/Event.php';

$user = (new User())
        ->setFirstName('Joe')
        ->setLastName('Hoe'); // no fuj!

$entityManager->persist($user);

$entityManager->flush();
/////////////////////////
die();

$configFile = file_get_contents(__DIR__ . '/config.json');
$config = json_decode($configFile, true);

$slimConfig = $config['slim'];

$secretsFile = file_get_contents(__DIR__ . '/.secrets.json');
$secrets = json_decode($secretsFile, true);

$slimConfig['db'] = $secrets['db'];

$app = new \Slim\App([ 'settings' => $slimConfig ]);
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

require_once('app/routes.php');

$app->run();
