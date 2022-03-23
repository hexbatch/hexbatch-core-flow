<?php /** @noinspection PhpUnused */

declare(strict_types=1);

use app\controllers\user\CheckAdminMiddleware;
use app\controllers\user\PingUserMiddleware;
use app\models\user\FlowUser;
use app\controllers\user\CheckLoggedInMiddleware;
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
        $user_info = FlowUser::find_one(strval($auth->getUserID()));
        if (!$user_info) {
            $user_info = new FlowUser();
        }
        return $user_info;

    });

    $container->set('checkLoggedInMiddleware', function() use ($container) {

        $auth = $container->get('auth');
        return new CheckLoggedInMiddleware($auth);

    });

    $container->set('checkAdminMiddleware', function() use ($container) {
        return new CheckAdminMiddleware();
    });

    $container->set('pingUserMiddleware', function() use ($container) {

        $user = $container->get('user');
        return new PingUserMiddleware($user);

    });



};
