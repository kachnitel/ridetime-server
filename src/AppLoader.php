<?php
namespace RideTimeServer;

use Slim\App;
use Doctrine\ORM\EntityManager;

use RideTimeServer\API\Middleware\AuthMiddleware;
use RideTimeServer\API\Middleware\LoggerMiddleware;
use RideTimeServer\API\Database;
use RideTimeServer\API\Router;

class AppLoader implements AppLoaderInterface
{
    /**
     * Slim app
     *
     * @var App
     */
    protected $app;

    public function initApp()
    {
        $config = $this->loadJsonCfg('/config.json');
        $secrets = $this->loadJsonCfg('/.secrets.json');

        $slimConfig = $config['slim'];
        $slimConfig['db'] = $secrets['db'];
        /**
         * @var App $app
         */
        $this->app = new App([ 'settings' => $slimConfig ]);

        $router = new Router($this->app);
        $router->initRoutes();
        $this->initContainer($config, $secrets);
        $this->initMiddleware($config);
    }

    public function runApp()
    {
        $this->app->run();
    }

    /**
     * @return void
     */
    protected function initContainer(array $config, array $secrets)
    {
        $container = $this->app->getContainer();

        $container['logger'] = $this->initLogger($config);

        $db = new Database();
        $container['entityManager'] = $db->getEntityManager($config['doctrine'], $secrets['db']);
    }

    /**
     * Initialize monolog
     *
     * @param array $config
     * @return callable
     */
    protected function initLogger(array $config): callable
    {
        return function($c) use ($config) {
            $logger = new \Monolog\Logger($config['appName']);

            $file_handler = new \Monolog\Handler\StreamHandler($config['logPath']);
            $logger->pushHandler($file_handler);
            return $logger;
        };
    }

    /**
     * @param array $config
     * @return void
     */
    protected function initMiddleware(array $config)
    {
        $container = $this->app->getContainer();

        $loggerMiddleware = new LoggerMiddleware($container);
        $this->app->add($loggerMiddleware->getMiddleware());

        $authMiddleware = new AuthMiddleware($container, $config);
        $this->app->add($authMiddleware->getMiddleware());
    }

    /**
     * @param string $path
     * @return array
     */
    protected function loadJsonCfg(string $path): array
    {
        $file = file_get_contents(ROOT_DIR . $path);
        if ($file === false) {
            throw new \Exception("Config file '{$path}' not found in app root");
        }
        return json_decode($file, true);
    }
}