<?php
namespace app\controllers\project;

use app\controllers\user\UserPages;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\GoodZipArchive;
use app\models\project\FlowGitFile;
use app\models\project\FlowProject;
use app\models\project\FlowProjectUser;
use app\models\user\FlowUser;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use InvalidArgumentException;
use LogicException;
use Monolog\Logger;
use ParagonIE\AntiCSRF\AntiCSRF;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Stream;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;


class ProjectPages
{

    const REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY = 'project_new_form_in_progress_has_error';
    const REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY = 'project_edit_form_in_progress_has_error';
    const REM_EXPORT_PROJECT_WITH_ERROR_SESSION_KEY = 'project_export_form_in_progress_has_error';
    const REM_IMPORT_PROJECT_GIT_WITH_ERROR_SESSION_KEY = 'project_import_git_form_in_progress_has_error';

    protected Auth $auth;
    protected Logger $logger;
    /**
     * @var Container $container
     */
    protected Container $container;

    protected Twig $view;

    /**
     * @var FlowUser $user
     */
    protected FlowUser $user;

    /**
     * UserLogInPages constructor.
     * @param Auth $auth
     * @param Logger $logger
     * @param Container $container
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function __construct(Auth $auth, Logger $logger, Container $container)
    {
        $this->auth = $auth;
        $this->logger = $logger;
        $this->container = $container;
        $this->view = $this->container->get('view');
        $this->user = $this->container->get('user');
    }



    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function all_projects( ResponseInterface $response) :ResponseInterface {
        if ($this->user->flow_user_id) {
            return $this->all_projects_overview_logged_in($response);
        } else {
            return $this->all_projects_overview_for_anon($response);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function all_projects_overview_for_anon( ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/all_projects_overview_for_anon.twig',
                'page_title' => 'All Projects',
                'page_description' => 'Shows all projects',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not all pages for anon page",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function all_projects_overview_logged_in( ResponseInterface $response) :ResponseInterface {
        try {
            $my_projects= FlowProject::get_all_top_projects( FlowUser::get_logged_in_user()->flow_user_id);
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/all_projects_overview_logged_in.twig',
                'page_title' => 'Your Projects',
                'page_description' => 'Shows projects for user',
                'my_projects' => $my_projects
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not all projects for logged in page",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function single_project_home( ServerRequestInterface $request,ResponseInterface $response,
                                        string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'read');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/single_project_home.twig',
                'page_title' => 'Project ' . $project->flow_project_title,
                'page_description' => 'Shows projects for user',
                'project' => $project
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render single project home page",['exception'=>$e]);
            throw $e;
        }
    }



    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function new_project( ResponseInterface $response) :ResponseInterface {
        try {
            if (array_key_exists(static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?FlowProject $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY];
                $_SESSION[static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $form_in_progress = new FlowProject();
                }
            } else {
                $form_in_progress = new FlowProject();
            }
            $form_in_progress->admin_flow_user_id = $this->user->flow_user_id;
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/new_project.twig',
                'page_title' => 'Make A New Project',
                'page_description' => 'New Project Form',
                'project' => $form_in_progress,
                'project_form_action' => 'create_project'


            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render new project page",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function create_project(ServerRequestInterface $request, ResponseInterface $response) :ResponseInterface
    {
        $project = null;
        try {
            $csrf = new AntiCSRF;
            if (!empty($_POST)) {
                if (!$csrf->validateRequest()) {
                    throw new HttpForbiddenException($request,"Bad Request") ;
                }
            }
            $project = new FlowProject();
            $project->flow_project_type = FlowProject::FLOW_PROJECT_TYPE_TOP;
            $project->parent_flow_project_id = null;
            $project->admin_flow_user_id = FlowUser::get_logged_in_user()->flow_user_id;

            $args = $request->getParsedBody();
            $project->flow_project_title = $args['flow_project_title'];
            $project->flow_project_blurb = $args['flow_project_blurb'];
            $project->is_public = isset($args['is_public']) && intval($args['is_public']);
            $project->set_read_me($args['flow_project_readme_bb_code']);

            $project->save();
            $_SESSION[static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY] = null;
            try {
                UserPages::add_flash_message('success', "Created Project " . $project->flow_project_title);
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('all_projects');
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to all projects after successful creation", ['exception' => $e]);
                throw $e;
            }
        } catch (Exception $e) {
            try {
                UserPages::add_flash_message('warning', "Cannot create project " . $e->getMessage());
                $_SESSION[static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY] = $project;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('new_project');
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to create project after error", ['exception' => $e]);
                throw $e;
            }
        }

    }


    /**
     * @param ServerRequestInterface $request
     * @param string $user_name
     * @param string $project_name
     * @param string $permission read|write|admin
     * @return FlowProject|null
     * @throws Exception
     */
    protected  function get_project_with_permissions(
        ServerRequestInterface $request,string $user_name, string $project_name,string $permission) : ?FlowProject
    {
        if ($permission !== 'read' && $permission !== 'write' && $permission !== 'admin') {
            throw new InvalidArgumentException("permission has to be read or write or admin");
        }

        $project = FlowProject::find_one($project_name,$user_name);

        //return if public and nobody logged in
        if (empty($this->user->flow_user_id)) {
            if ($permission === 'read') {
                if ($project->is_public) {
                    return $project;
                }
            } else {
                throw new HttpForbiddenException($request,"Project is not public");
            }
        }

        $user_permissions = FlowUser::find_users_by_project(true,
            $project->flow_project_guid,null,true,$this->user->flow_user_guid);

        if (empty($user_permissions)) {
            throw new InvalidArgumentException("No permissions set for this");
        }
        $permissions_array  = $user_permissions[0]->get_permissions();
        if (empty($permissions_array)) {
            throw new InvalidArgumentException("No permissions found, although in project");
        }
        $project_user = $permissions_array[0];

        $project->set_current_user_permissions($project_user);

        if ($permission === 'read') {
            if ($project->is_public) {
                return $project;
            } elseif (!$project_user->can_read) {
                throw new HttpForbiddenException($request,"Project cannot be viewed");
            }
        }
        else if ($permission === 'admin') {
            if ( ! $project_user->can_admin) {
                throw new HttpForbiddenException($request,"Only the admin can edit this part of the project $project_name");
            }
        } else if ($permission === 'write') {
            if ( ! $project_user->can_write) {
                throw new HttpForbiddenException($request,"You can view but not edit this project");
            }
        }

        return $project;
    }
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function edit_project( ServerRequestInterface $request,ResponseInterface $response,
                                         string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            if (array_key_exists(static::REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?FlowProject $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY];
                $_SESSION[static::REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $form_in_progress = $project;
                }
            } else {
                $form_in_progress = $project;
            }


            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/edit_project.twig',
                'page_title' => "Edit Project $project_name",
                'page_description' => 'Edits this project',
                'project' => $form_in_progress,
                'project_form_action' => 'update_project'
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render edit project page",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function update_project(ServerRequestInterface $request,ResponseInterface $response,
                                   string $user_name, string $project_name) :ResponseInterface {

        $project = null;
        try {
            $csrf = new AntiCSRF;

            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            $args = $request->getParsedBody();


            $project->flow_project_title = $args['flow_project_title'];
            $project->flow_project_blurb = $args['flow_project_blurb'];
            $project->is_public = isset($args['is_public']) && intval($args['is_public']);
            $project->set_read_me($args['flow_project_readme_bb_code']);

            if (!empty($_POST)) {
                if (!$csrf->validateRequest()) {
                    throw new HttpForbiddenException($request,"Bad Request") ;
                }
            }

            if (empty($args['flow_project_git_hash'])) {
                if ($project->get_head_commit_hash()) {
                    throw new InvalidArgumentException("Missing flow_project_git_hash");
                }
            }

            $old_git_hash = $args['flow_project_git_hash'];
            if ($project->get_head_commit_hash() !== $old_git_hash) {
                throw new InvalidArgumentException("Git hash is too old, project was saved since this page loaded");
            }

            $save_words = $project->save();
            $_SESSION[static::REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY] = null;

            try {
                UserPages::add_flash_message('success', "Updated Project  " . $project->flow_project_title. " <br> $save_words");
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('single_project_home',[
                    "user_name" => $project->get_admin_user()->flow_user_name,
                    "project_name" => $project->flow_project_title
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to all projects after successful update", ['exception' => $e]);
                throw $e;
            }
        } catch (Exception $e) {
            try {
                UserPages::add_flash_message('warning', "Cannot update project " . $e->getMessage());
                $_SESSION[static::REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY] = $project;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('edit_project',[
                    "user_name" => $user_name ,
                    "project_name" => $project_name
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to create project after error", ['exception' => $e]);
                throw $e;
            }
        }

    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function edit_project_permissions( ServerRequestInterface $request,ResponseInterface $response,
                                  string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'admin');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            $admin_users = [];
            $write_users = [];
            $read_users = [];
            $users_in_project = $project->get_flow_project_users();
            foreach ($users_in_project as $user_to_scan) {
                foreach ( $user_to_scan->get_permissions() as $up ) {
                    if ($up->can_write) {
                        $write_users[] = $user_to_scan;
                    }
                    if ($up->can_read) {
                        $read_users[] = $user_to_scan;
                    }
                    if ($up->can_admin) {
                        $admin_users[] = $user_to_scan;
                    }
                }
            }
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/project_permission.twig',
                'page_title' => "Edit Permissions for Project $project_name",
                'page_description' => 'Sets who can update and see this',
                'project' => $project,
                'write_users' => $write_users,
                'read_users' => $read_users,
                'admin_users' => $admin_users
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render permissions page",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function change_project_permissions(ServerRequestInterface $request,ResponseInterface $response,
                                   string $user_name, string $project_name) :ResponseInterface {

        $token = null;
        try {
            $args = $request->getParsedBody();
            if (empty($args)) {
                throw new InvalidArgumentException("No data sent");
            }
            $csrf = new FlowAntiCSRF;
            if (!$csrf->validateRequest()) {
                throw new HttpForbiddenException($request,"Bad Request") ;
            }

            $x_header = $request->getHeader('X-Requested-With') ?? [];
            if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
                throw new InvalidArgumentException("Need the X-Requested-With header");
            }

            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'admin');

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $token_lock_to = $routeParser->urlFor('edit_permissions_ajax',[
                'user_name' => $user_name,
                'project_name' => $project_name
            ]);

            $token = $csrf->getTokenArray($token_lock_to);


            $action = $args['action'] ?? '';
            if (!$action) {throw new InvalidArgumentException("Action needs to be set");}

            switch ($action) {
                case 'permission_read_add':
                case 'permission_read_remove':
                case 'permission_write_add':
                case 'permission_write_remove':
                case 'permission_admin_add':
                case 'permission_admin_remove': {

                    $flow_user_guid = $args['user_guid'] ?? null;
                    $flow_project_guid = $args['project_guid'] ?? null;
                    if (!$flow_user_guid || !$flow_project_guid) {
                        throw new InvalidArgumentException("Need both project and user guids to complete this");
                    }
                    $target_user_array = FlowUser::find_users_by_project(true,$flow_project_guid,null,true,$flow_user_guid);
                    if (empty($target_user_array) || empty($target_user_array[0]->get_permissions())) {
                        $target_user = FlowUser::find_one($flow_user_guid);
                        if (empty($target_user)) {
                            throw new InvalidArgumentException("Cannot find user by guid of $flow_user_guid");
                        }
                        $perm = new FlowProjectUser();
                        $perm->can_write = false;
                        $perm->can_read = false;
                        $perm->can_admin = false;
                        $perm->flow_user_id = $target_user->flow_user_id;
                        $perm->flow_project_id = $project->id;
                    } else {
                        $perm = $target_user_array[0]->get_permissions()[0];
                    }
                    $inner_data = $perm;


                    switch ($action) {
                        case 'permission_read_add': {
                            $perm->can_read = true;
                            break;
                        }
                        case 'permission_read_remove': {
                            if ($perm->flow_user_guid === $project->get_admin_user()->flow_user_guid) {
                                throw new InvalidArgumentException("Cannot remove read from the project owner");
                            }
                            $perm->can_read = false;
                            break;
                        }
                        case 'permission_write_add': {
                            $perm->can_write = true;
                            $perm->can_read = true;
                            break;
                        }
                        case 'permission_write_remove': {
                            if ($perm->flow_user_guid === $project->get_admin_user()->flow_user_guid) {
                                throw new InvalidArgumentException("Cannot remove write from the project owner");
                            }
                            $perm->can_write = false;
                            break;
                        }
                        case 'permission_admin_add': {
                            $perm->can_admin = true;
                            $perm->can_write = true;
                            $perm->can_read = true;
                            break;
                        }
                        case 'permission_admin_remove': {
                            if ($perm->flow_user_guid === $project->get_admin_user()->flow_user_guid) {
                                throw new InvalidArgumentException("Cannot remove admin from the project owner");
                            }
                            $perm->can_admin = false;
                            break;
                        }
                        default: {throw new LogicException("Ooops mismatched switch");}
                    }

                    $perm->save();
                    break;


                }
                case 'permission_public_set': {
                    $project->is_public = isset($args['is_public']) && intval($args['is_public']);
                    $project->save();
                    $inner_data = $project;
                    break;
                }
                default: {
                    throw new InvalidArgumentException("Unknown Action Verb: $action");
                }
            }
            $data = ['success'=>true,'message'=>'','data'=>$inner_data,'token'=> $token];
            $payload = json_encode($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $token];
            $payload = json_encode($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }




    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function edit_project_tags( ServerRequestInterface $request,ResponseInterface $response,
                                              string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'read');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/project_tags.twig',
                'page_title' => "Edit Tags for Project $project_name",
                'page_description' => 'Manage the tags set in this project',
                'project' => $project,
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render tags page",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @param ?int  $page
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function project_history( ServerRequestInterface $request,ResponseInterface $response,
                                       string $user_name, string $project_name,?int $page) :ResponseInterface {
        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            if (intval($page) < 1) {$page = 1;}

            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/project_history.twig',
                'page_title' => "History for Project $project_name",
                'page_description' => 'History',
                'history_page_number' => $page,
                'history_page_size' => 10,
                'project' => $project,
            ]);

        } catch (Exception $e) {
            $this->logger->error("Could not render history page",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function get_file_change(ServerRequestInterface $request,ResponseInterface $response,
                                    string $user_name, string $project_name) :ResponseInterface {


        try {
            $args = $request->getParsedBody();
            if (empty($args)) {
                throw new InvalidArgumentException("No data sent");
            }


            $x_header = $request->getHeader('X-Requested-With') ?? [];
            if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
                throw new InvalidArgumentException("Need the X-Requested-With header");
            }

            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');


            $file_path = $args['file_path'] ?? '';
            //if (!$file_path) {throw new InvalidArgumentException("File Path needs to be set");}
            $commit = $args['commit'] ?? '';
            if (!$commit) {throw new InvalidArgumentException("Commit needs to be set");}

            $b_show_all = (bool)intval($args['show_all'] ?? '1');

            $project_directory = $project->get_project_directory();
            $flow_file = new FlowGitFile($project_directory,$commit,$file_path);
            $diff = $flow_file->show_diff($b_show_all);

            $data = ['success'=>true,'message'=>'','diff'=>$diff,'token'=> null];
            $payload = json_encode($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);


        } catch (Exception $e) {
            $data = ['success'=>false,'message'=>$e->getMessage(),'diff'=>null,'token'=> null];
            $payload = json_encode($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }



    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function export_view( ServerRequestInterface $request,ResponseInterface $response,
                                     string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found for export settings");
            }


            if (array_key_exists(static::REM_EXPORT_PROJECT_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?FlowProject $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_EXPORT_PROJECT_WITH_ERROR_SESSION_KEY];
                $_SESSION[static::REM_EXPORT_PROJECT_WITH_ERROR_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $form_in_progress = $project;
                }
            } else {
                $form_in_progress = $project;
            }


            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/project_export.twig',
                'page_title' => "Export Project $project_name",
                'page_description' => 'Export',
                'project' => $form_in_progress,
            ]);

        } catch (Exception $e) {
            $this->logger->error("Could not render history page",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function update_export(ServerRequestInterface $request,ResponseInterface $response,
                                   string $user_name, string $project_name) :ResponseInterface {

        $project = null;
        try {
            $csrf = new AntiCSRF;

            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            $args = $request->getParsedBody();



            $project->export_repo_url = $args['export_repo_url'];
            $project->export_repo_key = $args['export_repo_key'];
            $project->export_repo_branch = $args['export_repo_branch'];
            $project->export_repo_do_auto_push = isset($args['export_repo_do_auto_push']) && intval($args['export_repo_do_auto_push']);

            if (!empty($_POST)) {
                if (!$csrf->validateRequest()) {
                    throw new HttpForbiddenException($request,"Bad Request") ;
                }
            }


            $project->save_export_settings();
            $success_message = "Updated Project Export Settings "  . $project->flow_project_title;
            if (array_key_exists('export-push-now',$args)) {
                //do push now
                $push_status = $project->push_repo();
                $success_message = "Pushing to $project->export_repo_url "  . $project->flow_project_title . "<br>$push_status";
            }
            $_SESSION[static::REM_EXPORT_PROJECT_WITH_ERROR_SESSION_KEY] = null;

            try {
                UserPages::add_flash_message('success',$success_message );
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('project_history',[
                    "user_name" => $project->get_admin_user()->flow_user_name,
                    "project_name" => $project->flow_project_title
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to all projects after successful update", ['exception' => $e]);
                throw $e;
            }
        } catch (Exception $e) {
            try {
                UserPages::add_flash_message('warning', "Cannot update project export settings: <b>" . $e->getMessage().'</b>');
                $_SESSION[static::REM_EXPORT_PROJECT_WITH_ERROR_SESSION_KEY] = $project;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('project_export',[
                    "user_name" => $user_name ,
                    "project_name" => $project_name
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to project export settings after error", ['exception' => $e]);
                throw $e;
            }
        }

    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function download_export(ServerRequestInterface $request,ResponseInterface $response,
                                   string $user_name, string $project_name) :ResponseInterface {

        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }
            $temp_file_path = tempnam(sys_get_temp_dir(), 'git-zip-');
            $b_rename_ok = rename($temp_file_path, $temp_file_path .= '.zip');
            if (!$b_rename_ok) {
                throw new HttpInternalServerErrorException($request,"Cannot rename temp folder");
            }

            $project_directory_path = $project->get_project_directory();
            new GoodZipArchive($project_directory_path,    $temp_file_path,$project->flow_project_title) ;
            $file_size = filesize($temp_file_path);
            if (!$file_size) {
                throw new HttpInternalServerErrorException($request,"Cannot create zip folder for download");
            }

            $response = $response
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Length', 'application/zip')
                ->withHeader('Content-Disposition', "attachment; filename=$project->flow_project_title.zip")
                ->withAddedHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->withHeader('Cache-Control', 'post-check=0, pre-check=0')
                ->withHeader('Pragma', 'no-cache')
                ->withBody((new Stream(fopen($temp_file_path, 'rb'))));

            return $response;

        } catch (Exception $e) {
            $this->logger->error("Could not download project zip",['exception'=>$e]);
            throw $e;
        }


    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function import_view( ServerRequestInterface $request,ResponseInterface $response,
                                 string $user_name, string $project_name) :ResponseInterface {


        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'admin');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found for export settings");
            }


            if (array_key_exists(static::REM_IMPORT_PROJECT_GIT_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?FlowProject $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_IMPORT_PROJECT_GIT_WITH_ERROR_SESSION_KEY];
                $_SESSION[static::REM_IMPORT_PROJECT_GIT_WITH_ERROR_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $form_in_progress = $project;
                }
            } else {
                $form_in_progress = $project;
            }


            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/project_import.twig',
                'page_title' => "Import for Project $project_name",
                'page_description' => 'Import for Project',
                'project' => $form_in_progress,
            ]);

        } catch (Exception $e) {
            $this->logger->error("Could not render history page",['exception'=>$e]);
            throw $e;
        }

    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function import_from_git(ServerRequestInterface $request,ResponseInterface $response,
                                  string $user_name, string $project_name) :ResponseInterface {

        $project = null;
        try {
            $csrf = new AntiCSRF;

            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            $args = $request->getParsedBody();



            $project->import_repo_url = $args['import_repo_url'];
            $project->import_repo_key = $args['import_repo_key'];
            $project->import_repo_branch = $args['import_repo_branch'];

            if (!empty($_POST)) {
                if (!$csrf->validateRequest()) {
                    throw new HttpForbiddenException($request,"Bad Request") ;
                }
            }


            $project->save_import_settings();
            $success_message = "Updated Project Import Settings "  . $project->flow_project_title;
            if (array_key_exists('import-now',$args)) {
                //do push now
                $push_status = $project->import_pull_repo_from_git();
                $success_message = "Pulling from $project->import_repo_url "  . $project->flow_project_title . "<br>$push_status";
            }
            $_SESSION[static::REM_IMPORT_PROJECT_GIT_WITH_ERROR_SESSION_KEY] = null;

            try {
                UserPages::add_flash_message('success',$success_message );
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('project_history',[
                    "user_name" => $project->get_admin_user()->flow_user_name,
                    "project_name" => $project->flow_project_title
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to all projects after successful import from git", ['exception' => $e]);
                throw $e;
            }
        } catch (Exception $e) {
            try {
                UserPages::add_flash_message('warning', "Cannot update project import settings: <b>" . $e->getMessage().'</b>');
                $_SESSION[static::REM_IMPORT_PROJECT_GIT_WITH_ERROR_SESSION_KEY] = $project;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('project_import',[
                    "user_name" => $user_name ,
                    "project_name" => $project_name
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to project import settings after error", ['exception' => $e]);
                throw $e;
            }
        }

    }




    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function import_from_file(ServerRequestInterface $request,ResponseInterface $response,
                                    string $user_name, string $project_name) :ResponseInterface {

        $project = null;
        try {
            $csrf = new AntiCSRF;

            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            if (!empty($_POST)) {
                if (!$csrf->validateRequest()) {
                    throw new HttpForbiddenException($request,"Bad Request") ;
                }
            }


            $uploadedFiles = $request->getUploadedFiles();
            if (!array_key_exists('repo_or_patch_file',$uploadedFiles)) {
                throw new HttpBadRequestException($request,"Need the file named repo_or_patch_file");
            }
            // handle single input with single file upload
            /**
             * @var UploadedFileInterface $uploadedFile
             */
            $uploadedFile = $uploadedFiles['repo_or_patch_file'];
            $file_name = $uploadedFile->getClientFilename();
            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $phpFileUploadErrors = array(
                    0 => 'There is no error, the file uploaded with success',
                    1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                    2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                    3 => 'The uploaded file was only partially uploaded',
                    4 => 'No file was uploaded',
                    6 => 'Missing a temporary folder',
                    7 => 'Failed to write file to disk.',
                    8 => 'A PHP extension stopped the file upload.',
                );
                throw new HttpBadRequestException($request,"File Error $file_name : ". $phpFileUploadErrors[$uploadedFile->getError()] );
            }


            $success_message = "Nothing Done "  . $project->flow_project_title;
            $args = $request->getParsedBody();
            if (array_key_exists('import-now',$args)) {
                //do push now
                $push_status = $project->update_repo_from_file();
                $success_message = "Merging from file $file_name to "  . $project->flow_project_title . "<br>$push_status";
            }
            $_SESSION[static::REM_IMPORT_PROJECT_GIT_WITH_ERROR_SESSION_KEY] = null;

            try {
                UserPages::add_flash_message('success',$success_message );
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('project_history',[
                    "user_name" => $project->get_admin_user()->flow_user_name,
                    "project_name" => $project->flow_project_title
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to all projects after successful merge from file", ['exception' => $e]);
                throw $e;
            }
        } catch (Exception $e) {
            try {
                UserPages::add_flash_message('warning', "Cannot merge project from file: <b>" . $e->getMessage().'</b>');
                $_SESSION[static::REM_IMPORT_PROJECT_GIT_WITH_ERROR_SESSION_KEY] = $project;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('project_import',[
                    "user_name" => $user_name ,
                    "project_name" => $project_name
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to project import settings after error", ['exception' => $e]);
                throw $e;
            }
        }

    }





}