<?php

declare(strict_types=1);
error_reporting(E_ALL);
define('ROOT_DIR', __DIR__);
// TODO: Error handler to catch warnings etc
ini_set('display_errors', '1');

use RideTimeServer\AppLoader;

require_once 'vendor/autoload.php';

$app = new AppLoader();

$app->initApp();
$app->runApp();
