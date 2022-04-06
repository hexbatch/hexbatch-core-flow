<?php /** @noinspection PhpUnused */

declare(strict_types=1);

use app\controllers\entry\EntryPages;
use app\controllers\home\AdminPages;
use app\controllers\home\HomePages;
use app\controllers\project\ProjectPages;
use app\controllers\tag\TagPages;
use app\controllers\user\UserPages;
use app\helpers\AdminHelper;
use app\helpers\ProjectHelper;
use app\helpers\StandardHelper;
use app\helpers\UserHelper;
use app\helpers\Utilities;
use Psr\Log\LoggerInterface;
use Slim\App;
use DI\Container;

return function (App $app) {
    /**
     * @var Container $container
     */
    $container = $app->getContainer();

    $container->set('userPages', function() use ($app, $container) {
        return new UserPages($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('homePages', function() use ($app, $container) {
        return new HomePages($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('adminPages', function() use ($app, $container) {
        return new AdminPages($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('projectPages', function() use ($app, $container) {
        return new ProjectPages($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('tagPages', function() use ($app, $container) {
        return new TagPages($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('entryPages', function() use ($app, $container) {
        return new EntryPages($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('projectHelper', function() use ($app, $container) {
        return new ProjectHelper($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('adminHelper', function() use ($app, $container) {
        return new AdminHelper($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('userHelper', function() use ($app, $container) {
        return new UserHelper($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('utilities', function() use ($app, $container) {
        return new Utilities($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

    $container->set('standardHelper', function() use ($app, $container) {
        return new StandardHelper($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

};
