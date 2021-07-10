<?php
declare(strict_types=1);


use Slim\App;
use Slim\Routing\RouteCollectorProxy;



return function (App $app) {

    $container = $app->getContainer();

    $app->group('', function (RouteCollectorProxy $group) use($app)  {

        $group->get('/', ['homePages', 'root'])->setName('root');

        $app->get('/phpinfo', ['homePages', 'php_info'])->setName('debug');
        $group->get('/projects', ['projectPages', 'all_projects'])->setName('all_projects');

        $group->get('/home', ['userPages', 'user_home'])->setName('user_home');



        $group->group('/user', function (RouteCollectorProxy $group) {
            $group->get('/logout', ['userPages', 'do_logout'])->setName('logout');

            $group->get('/register', ['userPages', 'register_form'])->setName('register');

            $group->post('/submit_registration', ['userPages', 'submit_registration'])->setName('submit_registration');

            $group->get('/login', ['userPages', 'login_form'])->setName('login');

            $group->post('/submit_login', ['userPages', 'submit_login'])->setName('submit_login');
        });



        $group->group('', function (RouteCollectorProxy $group) {
            $group->get('/foo', function ($request, $response /*, array $args*/) {
                // Route for /billing
                $response->getBody()->write("protected shit");
                return $response;
            });

            $group->get('/bar/{id:[0-9]+}', function ($request, $response, array $args) {
                // Route for /invoice/{id:[0-9]+}
                $response->getBody()->write("protected poop: " . $args['id']);
                return $response;
            });
        })->add('checkLoggedInMiddleware');


        $group->get('/{user_name:[[:alnum:]\-]+}', ['userPages', 'user_page'])->setName('user_page');

        $group->group('/{user_name:[[:alnum:]\-]+}', function (RouteCollectorProxy $group) {
            $group->get('/{project_name:[[:alnum:]\-]+}', ['projectPages', 'single_project_home'])->setName('single_project_home');

        });

    })->add($container->get('twigMiddleware'));




};