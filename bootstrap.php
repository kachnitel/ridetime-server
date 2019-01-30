<?php
require_once "vendor/autoload.php";

// Setup Doctrine
$configuration = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration(
    $paths = [__DIR__ . '/entities'],
    $isDevMode = true
);

var_dump($configuration->getMetadataDriverImpl());
$driverImpl = $configuration->newDefaultAnnotationDriver('./RideTimeServer/Entities');
$configuration->setMetadataDriverImpl($driverImpl);

$secretsFile = file_get_contents(__DIR__ . '/.secrets.json');
$secrets = json_decode($secretsFile, true);

// Setup connection parameters
$connectionParameters = [
    // 'dbname' => $secrets['db']['database'],
    'dbname' => 'ridetime-doctrine',
    'user' => $secrets['db']['user'],
    'password' => $secrets['db']['password'],
    'host' => $secrets['db']['host'],
    'driver' => 'pdo_mysql'
];

// Get the entity manager
$entityManager = Doctrine\ORM\EntityManager::create($connectionParameters, $configuration);
