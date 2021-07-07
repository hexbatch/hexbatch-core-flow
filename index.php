<?php

declare(strict_types=1);

use DI\Bridge\Slim\Bridge;
use DI\Container;
use Slim\Factory\AppFactory;

const HEXLET_BASE_PATH = __DIR__ ;
require_once 'config/flow-config-paths.php';
require_once HEXLET_BASE_PATH . '/vendor/autoload.php';


$container = new Container();

$settings = require_once HEXLET_BASE_PATH.'/app/settings.php';
$settings($container);

$logger = require_once HEXLET_BASE_PATH.'/app/logger.php';
$logger($container);



$connection = require_once HEXLET_BASE_PATH.'/app/connection.php';
$connection($container);


$auth = require_once HEXLET_BASE_PATH.'/app/auth.php';
$auth($container);

AppFactory::setContainer($container);
$app = Bridge::create($container);

$views = require_once HEXLET_BASE_PATH.'/app/views.php';
$views($app);

$pages = require_once HEXLET_BASE_PATH.'/app/pages.php';
$pages($app);

$middleware = require_once HEXLET_BASE_PATH.'/app/middleware.php';
$middleware($app);

$routes = require_once HEXLET_BASE_PATH.'/app/routes.php';
$routes($app);

$app->run();
