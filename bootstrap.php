<?php

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once 'vendor/autoload.php';

// Setup Doctrine
$configuration = Setup::createAnnotationMetadataConfiguration(
    $paths = [__DIR__ . '/src/Entities'],
    $isDevMode = true
);

$secretsFile = file_get_contents(__DIR__ . '/.secrets.json');
$secrets = json_decode($secretsFile, true);

// Setup connection parameters
$connectionParameters = [
    'dbname' => $secrets['db']['database'],
    'user' => $secrets['db']['user'],
    'password' => $secrets['db']['password'],
    'host' => $secrets['db']['host'],
    'driver' => 'pdo_mysql'
];

/** Get the entity manager
 * @var \Doctrine\ORM\EntityManager $entityManager
 */
$entityManager = EntityManager::create($connectionParameters, $configuration);
