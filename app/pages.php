<?php

declare(strict_types=1);

use app\home\HomePages;
use app\project\ProjectPages;
use app\user\UserPages;
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

    $container->set('projectPages', function() use ($app, $container) {
        return new ProjectPages($container->get('auth'),$container->get(LoggerInterface::class),$container);
    });

};
