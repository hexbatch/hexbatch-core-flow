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
                $group->post('/create_project', ['projectPages', 'create_project'])->setName('create_project');
                $group->get('/new_project', ['projectPages', 'new_project'])->setName('new_project');
            })->add('checkLoggedInMiddleware');

        });



        $group->group('/user', function (RouteCollectorProxy $group) {

            $group->get('/home', ['userPages', 'user_home'])->setName('user_home');
            $group->post('/update_profile', ['userPages', 'update_profile'])->setName('update_profile');

            $group->get('/logout', ['userPages', 'do_logout'])->setName('logout');

            $group->get('/register', ['userPages', 'register_form'])->setName('register');

            $group->post('/submit_registration', ['userPages', 'submit_registration'])->setName('submit_registration');

            $group->get('/login', ['userPages', 'login_form'])->setName('login');

            $group->post('/submit_login', ['userPages', 'submit_login'])->setName('submit_login');
        });




        $group->get('/search_users', ['userPages', 'find_users_by_project'])->setName('find_users_by_project');

        $group->get('/{user_name:[[:alnum:]\-]+}', ['userPages', 'user_page'])->setName('user_page');

        $group->group('/{user_name:[[:alnum:]\-]+}', function (RouteCollectorProxy $group) {
            $group->get('/{project_name:[[:alnum:]\-]+}', ['projectPages', 'single_project_home'])->setName('single_project_home');
            $group->get('/{project_name:[[:alnum:]\-]+}/edit', ['projectPages', 'edit_project'])->setName('edit_project');
            $group->post('/{project_name:[[:alnum:]\-]+}/edit', ['projectPages', 'update_project'])->setName('update_project');
            $group->post('/{project_name:[[:alnum:]\-]+}/edit_permissions_ajax', ['projectPages', 'change_project_permissions'])->setName('edit_permissions_ajax');
            $group->get('/{project_name:[[:alnum:]\-]+}/permissions', ['projectPages', 'edit_project_permissions'])->setName('project_permissions');
            $group->get('/{project_name:[[:alnum:]\-]+}/tags', ['projectPages', 'edit_project_tags'])->setName('project_tags');
            $group->get('/{project_name:[[:alnum:]\-]+}/history[/page/{page:[1-9]+[0-9]*}]', ['projectPages', 'project_history'])->setName('project_history');
            $group->post('/{project_name:[[:alnum:]\-]+}/file_change_ajax', ['projectPages', 'get_file_change'])->setName('get_file_change_ajax');
            $group->get('/{project_name:[[:alnum:]\-]+}/export', ['projectPages', 'export_view'])->setName('project_export');
            $group->post('/{project_name:[[:alnum:]\-]+}/export', ['projectPages', 'update_export'])->setName('update_project_export');
            $group->get('/{project_name:[[:alnum:]\-]+}/download_export', ['projectPages', 'download_export'])->setName('download_project_export');
            $group->get('/{project_name:[[:alnum:]\-]+}/import', ['projectPages', 'import_view'])->setName('project_import');
            $group->post('/{project_name:[[:alnum:]\-]+}/import', ['projectPages', 'import_from_git'])->setName('project_import_from_git');
            $group->post('/{project_name:[[:alnum:]\-]+}/import_from_file', ['projectPages', 'import_from_file'])->setName('project_import_from_file');

            $group->get('/{project_name:[[:alnum:]\-]+}/resources/{resource}', ['projectPages', 'get_resource_file'])->setName('project_resource');

            //tags in project , no matter how they are used or attached to
            $group->get('/{project_name:[[:alnum:]\-]+}/get_tags_ajax', ['tagPages', 'get_tags'])->setName('get_tags_ajax');
            $group->post('/{project_name:[[:alnum:]\-]+}/set_tags_ajax', ['tagPages', 'set_tags'])->setName('set_tags_ajax');

        });

    })->add('pingUserMiddleware')->add($container->get('twigMiddleware'));




};