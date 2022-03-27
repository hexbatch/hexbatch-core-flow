<?php

declare(strict_types=1);

use app\controllers\user\UserPages;
use Delight\Cookie\Session;
use DI\Bridge\Slim\Bridge;
use DI\Container;
use DI\NotFoundException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Factory\AppFactory;

require_once 'top-constants.php';
require_once HEXLET_BASE_PATH . '/config/flow-config-paths.php';
require_once HEXLET_BASE_PATH . '/vendor/autoload.php';

$container = new Container();

$settings_init_function = require_once HEXLET_BASE_PATH . '/app/settings.php';
$settings_init_function($container);

(function()  use ($container) {
    try {
        $settings = $container->get('settings');
        session_name($settings->session->session_name);
        Session::start('Strict');
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException("Session is not active");
        }
    } catch (NotFoundException|RuntimeException $e) {
        throw new HttpInternalServerErrorException(null,"cannot start the session: " . $e->getMessage());
    }
})();

$model_connection = require_once HEXLET_BASE_PATH . '/app/models.php';
$model_connection($container);

$logger = require_once HEXLET_BASE_PATH . '/app/logger.php';
$logger($container);



$connection = require_once HEXLET_BASE_PATH . '/app/connection.php';
$connection($container);


$auth = require_once HEXLET_BASE_PATH . '/app/auth.php';
$auth($container);


AppFactory::setContainer($container);
$app = Bridge::create($container);


$container->set('app', function() use ($app) { return $app; });



$pages = require_once HEXLET_BASE_PATH . '/app/pages.php';
$pages($app);

$views = require_once HEXLET_BASE_PATH . '/app/twig.php';
$views($app);

$middleware = require_once HEXLET_BASE_PATH . '/app/middleware.php';
$middleware($app);

$routes = require_once HEXLET_BASE_PATH . '/app/routes.php';
$routes($app);


(function()  use ($container) {
    try {
        $user = $container->get('user');
        $container->get('projectHelper'); //call ignoring return, need to initialize early by doing this
        $flash_messages = [];
        $settings = $container->get('settings');
        $flash_key = $settings->session->flash_key;
        UserPages::set_flash_key_in_session($flash_key);
        if (array_key_exists($flash_key,$_SESSION)) {
            $flash_messages = $_SESSION[$flash_key];
            $_SESSION[$flash_key] = [];
        }
        $container->get('view')->getEnvironment()->addGlobal('user', $user);
        $container->get('view')->getEnvironment()->addGlobal('flash_messages', $flash_messages);

    } catch (NotFoundException|RuntimeException $e) {
        throw new HttpInternalServerErrorException(null,"cannot initialize twig global variables: " . $e->getMessage());
    }
})();

$app->run();

