<?php
namespace RideTimeServer\API;

use Slim\App;

interface AppLoaderInterface
{
    public function initApp();

    public function runApp();
}
