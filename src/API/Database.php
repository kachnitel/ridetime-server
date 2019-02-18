<?php
namespace RideTimeServer\API;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class Database {
    /**
     * Initialize Doctrine
     *
     * @param array $doctrineConfig ['entitiesPath', 'devMode']
     * @param array $dbSecrets ['database', 'user', 'password', 'host']
     * @return callable
     */
    public function getEntityManager(array $doctrineConfig, array $dbSecrets): EntityManager
    {
        // Setup Doctrine
        $configuration = Setup::createAnnotationMetadataConfiguration(
            $paths = [__DIR__ . $doctrineConfig['entitiesPath']],
            $isDevMode = $doctrineConfig['devMode']
        );

        // Setup connection parameters
        $connectionParameters = [
            'dbname' => $dbSecrets['database'],
            'user' => $dbSecrets['user'],
            'password' => $dbSecrets['password'],
            'host' => $dbSecrets['host'],
            'driver' => 'pdo_mysql'
        ];

        /**
         * Get the entity manager
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = EntityManager::create($connectionParameters, $configuration);

        return $entityManager;
    }
}
