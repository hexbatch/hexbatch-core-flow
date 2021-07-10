<?php

declare(strict_types=1);

use app\user\CheckLoggedInMiddleware;
use Delight\Auth\Auth;
use Delight\Auth\AuthError;
use DI\Container;
use Psr\Log\LoggerInterface;

return function (Container $container) {
    $container->set('auth', function() use ($container) {

        $connection = $container->get('connection');
        $db = $connection->getPdo();


        try {
            $auth = new Auth($db);
            return $auth;
        } catch (AuthError $e) {
            $container->get(LoggerInterface::class)->alert('cannot start user auth to db',['exception'=>$e]);
        }
        return null;

    });

    $container->set('user', function() use ($container) {

        $auth = $container->get('auth');

        $user_info = (object)[
            'id' => (int)$auth->getUserId(),
            'username' =>$auth->getUsername(),
            'email' => $auth->getEmail()
        ];

        return $user_info;

    });

    $container->set('checkLoggedInMiddleware', function() use ($container) {

        $auth = $container->get('auth');
        return new CheckLoggedInMiddleware($auth);

    });



};
