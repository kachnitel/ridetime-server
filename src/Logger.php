<?php
namespace RideTimeServer;

use Monolog\Logger as Monolog;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;

class Logger
{
    /**
     * Initialize monolog
     *
     * @param array $config ['appName', 'log': ['path', 'level']]
     * - 'level' is one of Monolog\Logger constants (ie DEBUG = 100, INFO = 200, ...)
     * @return callable
     */
    public function getLogger(array $config): Monolog
    {
        $logger = new Monolog($config['appName']);

        $fileHandler = new StreamHandler(
            $config['log']['path'],
            $config['log']['level'] ?? Monolog::DEBUG
        );
        $fileHandler->setFormatter(new JsonFormatter());
        $logger->pushHandler($fileHandler);
        return $logger;
    }
}