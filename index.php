<?php

declare(strict_types=1);
error_reporting(E_STRICT);
define('ROOT_DIR', __DIR__);

use RideTimeServer\API\AppLoader;

require_once 'vendor/autoload.php';

$app = new AppLoader();

$app->initApp();
$app->runApp();
