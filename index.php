<?php

declare(strict_types=1);
error_reporting(E_ALL);
define('ROOT_DIR', __DIR__);

use RideTimeServer\AppLoader;

require_once 'vendor/autoload.php';

$app = new AppLoader();

$app->initApp();
$app->runApp();
