<?php
namespace RideTimeServer\API;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use RideTimeServer\CustomEntityManager;
use Slim\Container;

class Database {
    /**
     * Initialize Doctrine
     *
     * @param array $doctrineConfig ['entitiesPath', 'devMode']
     * @param array $dbSecrets ['database', 'user', 'password', 'host']
     * @param Container $container
     * @return EntityManagerInterface
     */
    public function getEntityManager(
        array $doctrineConfig,
        array $dbSecrets,
        Container $container
    ): EntityManagerInterface
    {
        // Setup Doctrine
        $configuration = Setup::createAnnotationMetadataConfiguration(
            [__DIR__ . $doctrineConfig['entitiesPath']],
            $doctrineConfig['devMode']
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

        $customEntityManager = new CustomEntityManager($entityManager, $container);
        return $customEntityManager;
    }
}
