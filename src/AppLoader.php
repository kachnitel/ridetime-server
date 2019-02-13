<?php
namespace RideTimeServer;

use Slim\App;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use RideTimeServer\API\Routers;
use RideTimeServer\API\Middleware\AuthMiddleware;

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

        $this->initRoutes();
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
    protected function initRoutes()
    {
        (new Routers\AuthRouter($this->app))->initRoutes();

        // FIXME:
        $appLoader = $this;
        $this->app->group('/api', function () use ($appLoader) {
            $appLoader->initApiRoutes();
        });
    }

    /**
     * Add routes with Auth Middleware
     *
     * @return void
     */
    protected function initApiRoutes()
    {
        $routers = [
            new Routers\UserRouter($this->app),
            new Routers\EventRouter($this->app),
            new Routers\LocationRouter($this->app)
        ];

        foreach ($routers as $router) {
            $router->initRoutes();
        }
    }

    /**
     * @return void
     */
    protected function initContainer(array $config, array $secrets)
    {
        $container = $this->app->getContainer();

        $container['logger'] = $this->initLogger($config);

        $container['entityManager'] = $this->initDB($config['doctrine'], $secrets['db']);
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
     * Initialize Doctrine
     *
     * @param array $doctrineConfig
     * @param array $dbSecrets
     * @return callable
     */
    protected function initDB(array $doctrineConfig, array $dbSecrets): callable
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

        /** Get the entity manager
         * @var \Doctrine\ORM\EntityManager $entityManager
         */
        $entityManager = EntityManager::create($connectionParameters, $configuration);

        return function ($c) use ($entityManager) {
            return $entityManager;
        };
    }

    protected function initMiddleware(array $config)
    {
        $container = $this->app->getContainer();

        /**
         * Request logger middleware
         *
         * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
         * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
         * @param  callable                                 $next     Next middleware
         *
         * @return \Psr\Http\Message\ResponseInterface
         */
        $this->app->add(function (Request $request, Response $response, callable $next) use ($container) {
            $container['logger']->addInfo($request->getMethod() . ' ' . $request->getUri()->getPath());

            $response = $next($request, $response);

            return $response;
        });

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