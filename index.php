<?php
declare(strict_types=1);

function exception_error_handler($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");

error_reporting(-1);
define('ROOT_DIR', __DIR__);

use RideTimeServer\AppLoader;

require_once 'vendor/autoload.php';

$app = new AppLoader();

$app->initApp();
$app->runApp();
