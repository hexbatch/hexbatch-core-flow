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
        $group->get('/general_search', ['homePages', 'general_search'])->setName('general_search');

        $group->get('/{user_name:[[:alnum:]\-]+}', ['userPages', 'user_page'])->setName('user_page');

        $group->group('/{user_name:[[:alnum:]\-]+}', function (RouteCollectorProxy $group) {

            $group->get('/{project_name:[[:alnum:]\-]+}', ['projectPages', 'single_project_home'])->setName('single_project_home');
            
            $group->group('/{project_name:[[:alnum:]\-]+}', function (RouteCollectorProxy $group) {
                
                $group->get('/', ['projectPages', 'single_project_home'])->setName('single_project_home');
                $group->get('/edit', ['projectPages', 'edit_project'])->setName('edit_project');
                $group->post('/edit', ['projectPages', 'update_project'])->setName('update_project');
                $group->post('/edit_permissions_ajax', ['projectPages', 'change_project_permissions'])->setName('edit_permissions_ajax');
                $group->get('/permissions', ['projectPages', 'edit_project_permissions'])->setName('project_permissions');
                $group->get('/tags', ['projectPages', 'edit_project_tags'])->setName('project_tags');
                $group->get('/history[/page/{page:[1-9]+[0-9]*}]', ['projectPages', 'project_history'])->setName('project_history');
                $group->post('/file_change_ajax', ['projectPages', 'get_file_change'])->setName('get_file_change_ajax');
                $group->get('/export', ['projectPages', 'export_view'])->setName('project_export');
                $group->post('/export', ['projectPages', 'update_export'])->setName('update_project_export');
                $group->get('/download_export', ['projectPages', 'download_export'])->setName('download_project_export');
                $group->get('/import', ['projectPages', 'import_view'])->setName('project_import');
                $group->post('/import', ['projectPages', 'import_from_git'])->setName('project_import_from_git');
                $group->post('/import_from_file', ['projectPages', 'import_from_file'])->setName('project_import_from_file');

                $group->get('/files/{resource}', ['projectPages', 'get_resource_file'])->setName('project_files');
                $group->get('/resources/', ['projectPages', 'resources'])->setName('project_resources');
                $group->post('/resources', ['projectPages', 'upload_resource_file'])->setName('project_upload_resource_file');
                $group->post('/resources_delete', ['projectPages', 'delete_resource_file'])->setName('project_delete_resource_file');



                $group->group('/tag', function (RouteCollectorProxy $group) {

                    //tags in project , no matter how they are used or attached to
                    $group->get('/get', ['tagPages', 'get_tags'])->setName('get_tags_ajax');

                    //tag in project
                    $group->post('/create', ['tagPages', 'create_tag'])->setName('create_tag_ajax');

                    $group->post('{tag_name:[[:alnum:]\-]+}/edit',
                        ['tagPages', 'edit_tag'])->setName('edit_tag_ajax');

                    $group->post('{tag_name:[[:alnum:]\-]+}/delete',
                        ['tagPages', 'delete_tag'])->setName('delete_tag_ajax');

                    //attributes in project
                    $group->post('/{tag_name:[[:alnum:]\-]+}/attribute/create',
                        ['tagPages', 'create_attribute'])->setName('create_tag_attribute_ajax');

                    $group->post('/{tag_name:[[:alnum:]\-]+}/attribute/{attribute_name:[[:alnum:]\-]+}/edit',
                        ['tagPages', 'edit_attribute'])->setName('edit_tag_attribute_ajax');

                    $group->post('/{tag_name:[[:alnum:]\-]+}/attribute/{attribute_name:[[:alnum:]\-]+}/delete',
                        ['tagPages', 'delete_attribute'])->setName('delete_tag_attribute_ajax');

                    //applied in project
                    $group->post('/{tag_name:[[:alnum:]\-]+}/applied/create',
                        ['tagPages', 'create_applied'])->setName('create_applied_ajax');


                    $group->post('{tag_name:[[:alnum:]\-]+}/applied/delete',
                        ['tagPages', 'delete_applied'])->setName('delete_applied_ajax');
                });

                $group->group('/entry', function (RouteCollectorProxy $group) {

                    $group->get('/list[/page/{page:[1-9]+[0-9]*}]', ['entryPages', 'list_entries'])->setName('list_entries');
                    $group->get('/show/{entry_name:[[:alnum:]\-]+}', ['entryPages', 'show_entry'])->setName('show_entry');
                    $group->get('/new', ['entryPages', 'new_entry'])->setName('new_entry');
                    $group->get('/edit/{entry_name:[[:alnum:]\-]+}', ['entryPages', 'edit_entry'])->setName('edit_entry');
                    $group->post('/create', ['entryPages', 'create_entry'])->setName('create_entry');
                    $group->post('/update/{entry_name:[[:alnum:]\-]+}', ['entryPages', 'update_entry'])->setName('update_entry');
                    $group->post('/delete/{entry_name:[[:alnum:]\-]+}', ['entryPages', 'delete_entry'])->setName('delete_entry');
                } );


            });
            


        });

    })->add('pingUserMiddleware')->add($container->get('twigMiddleware'));




};