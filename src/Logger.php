<?php
namespace RideTimeServer;

use Monolog\Logger as Monolog;

class Logger
{
    /**
     * Initialize monolog
     *
     * @param array $config ['appName', 'logPath']
     * @return callable
     */
    public function getLogger(array $config): Monolog
    {
        $logger = new \Monolog\Logger($config['appName']);

        $fileHandler = new \Monolog\Handler\StreamHandler($config['logPath']);
        $logger->pushHandler($fileHandler);
        return $logger;
    }
}