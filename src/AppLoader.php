<?php
namespace RideTimeServer;

use Slim\App;

use RideTimeServer\API\Middleware\AuthMiddleware;
use RideTimeServer\API\Middleware\LoggerMiddleware;
use RideTimeServer\API\Database;
use RideTimeServer\API\Router;
use Aws\S3\S3Client;
use Psr\Container\ContainerInterface;
use RideTimeServer\API\Connectors\TrailforksConnector;

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

        $this->app = new App(['settings' => $config['slim']]);

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

        $container['logger'] = function ($container) use ($config) {
            return (new Logger())->getLogger($config);
        };

        $this->initErrorHandlers($container);

        $container['trailforks'] = function ($container) use ($secrets) {
            return new TrailforksConnector(
                $secrets['trailforks']['app_id'],
                $secrets['trailforks']['app_secret'],
                $container['logger']
            );
        };

        $container['entityManager'] = function ($container) use ($config, $secrets) {
            return (new Database())->getEntityManager($config['doctrine'], $secrets['db'], $container);
        };

        $container['s3'] = function ($container) use ($secrets) {
            $credentials = $secrets['aws'];

            $client = new S3Client([
                'region'  => $credentials['region'],
                'version' => 'latest',
                'credentials' => [
                    'key'    => $credentials['key'],
                    'secret' => $credentials['secret']
                ]
            ]);

            return [
                'client' => $client,
                'bucket' => $credentials['s3bucket']
            ];
        };
    }


    protected function initErrorHandlers(ContainerInterface $container)
    {
        $container['errorHandler'] = function ($container) {
            return new ErrorHandler($container['logger']);
        };

        $container['phpErrorHandler'] = function ($container) {
            return new PHPErrorHandler($container['logger']);
        };

        $container['notFoundHandler'] = function ($container) {
            return function ($request, $response) {
                return $response->withStatus(404)
                    ->withJson([
                        'status' => 'error',
                        'message' => 'Not found'
                    ]);
            };
        };

        $container['notAllowedHandler'] = function ($container) {
            return function ($request, $response, $methods) {
                return $response->withStatus(405)
                    ->withHeader('Allow', implode(', ', $methods))
                    ->withJson([
                        'status' => 'error',
                        'message' => 'Method must be one of: ' . implode(', ', $methods)
                    ]);
            };
        };
    }

    /**
     * @param array $config
     * @return void
     */
    protected function initMiddleware(array $config)
    {
        $container = $this->app->getContainer();

        if (!empty($config['logRequests'])) {
            $loggerMiddleware = new LoggerMiddleware($container);
            $this->app->add($loggerMiddleware->getMiddleware());
        }

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
