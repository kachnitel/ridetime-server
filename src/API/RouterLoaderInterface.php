<?php
namespace RideTimeServer\API;

use Slim\App;
use function foo\func;

interface RouterLoaderInterface
{
    public function __construct(App $app);

    public function initRoutes();
}
