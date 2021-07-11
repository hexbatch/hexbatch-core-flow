<?php
declare(strict_types=1);


use Slim\App;
use Slim\Routing\RouteCollectorProxy;



return function (App $app) {

    $container = $app->getContainer();

    $app->get('/phpinfo', ['homePages', 'php_info'])->setName('debug');


    $app->group('', function (RouteCollectorProxy $group) use($app)  {

        $group->get('/', ['homePages', 'root'])->setName('root');

        $group->group('/project', function (RouteCollectorProxy $group) {

            $group->get('/projects', ['projectPages', 'all_projects'])->setName('all_projects');

            $group->group('', function (RouteCollectorProxy $group) {
                $group->post('/create_new_project', ['projectPages', 'create_new_project'])->setName('create_new_project');
                $group->get('/new_project', ['projectPages', 'new_project_form'])->setName('new_project_form');
            })->add('checkLoggedInMiddleware');

        });



        $group->group('/user', function (RouteCollectorProxy $group) {

            $group->get('/home', ['userPages', 'user_home'])->setName('user_home');

            $group->get('/logout', ['userPages', 'do_logout'])->setName('logout');

            $group->get('/register', ['userPages', 'register_form'])->setName('register');

            $group->post('/submit_registration', ['userPages', 'submit_registration'])->setName('submit_registration');

            $group->get('/login', ['userPages', 'login_form'])->setName('login');

            $group->post('/submit_login', ['userPages', 'submit_login'])->setName('submit_login');
        });




        $group->get('/{user_name:[[:alnum:]\-]+}', ['userPages', 'user_page'])->setName('user_page');

        $group->group('/{user_name:[[:alnum:]\-]+}', function (RouteCollectorProxy $group) {
            $group->get('/{project_name:[[:alnum:]\-]+}', ['projectPages', 'single_project_home'])->setName('single_project_home');

        });

    })->add($container->get('twigMiddleware'));




};