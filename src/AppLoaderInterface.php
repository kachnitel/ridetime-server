<?php
namespace RideTimeServer;

use Slim\App;

interface AppLoaderInterface
{
    public function initApp();

    public function runApp();
}
