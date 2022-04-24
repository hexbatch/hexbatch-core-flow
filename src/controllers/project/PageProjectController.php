<?php
namespace app\controllers\project;

use app\controllers\user\UserPages;
use app\helpers\AjaxCallData;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\project\FlowProject;
use app\models\project\FlowProjectUser;
use app\models\project\IFlowProject;
use app\models\tag\FlowTagSearch;
use app\models\tag\FlowTagSearchParams;
use app\models\user\FlowUser;

use Exception;
use InvalidArgumentException;
use LogicException;

use ParagonIE\AntiCSRF\AntiCSRF;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;



class PageProjectController extends BaseProjectController
{

    const REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY = 'project_new_form_in_progress_has_error';
    const REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY = 'project_edit_form_in_progress_has_error';




    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws
     * 
     */
    public function all_projects(  ResponseInterface $response) :ResponseInterface {
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
     * 
     */
    public function all_projects_overview_for_anon( ResponseInterface $response) :ResponseInterface {
        try {
            $my_projects= $this->get_project_helper()->get_all_top_projects( FlowUser::get_logged_in_user()->flow_user_id);
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/all_projects_overview_for_anon.twig',
                'page_title' => 'All Projects',
                'page_description' => 'Shows all public projects',
                'my_projects' => $my_projects
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not all pages for anon page",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws
     */
    public function all_projects_overview_logged_in( ResponseInterface $response) :ResponseInterface {
        try {
            $my_projects= $this->get_project_helper()->get_all_top_projects( FlowUser::get_logged_in_user()->flow_user_id);
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/all_projects_overview_logged_in.twig',
                'page_title' => 'Your Projects',
                'page_description' => 'Shows projects for user',
                'my_projects' => $my_projects
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not get projects for logged in page",['exception'=>$e]);
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
     * 
     */
    public function single_project_home( ServerRequestInterface $request,ResponseInterface $response,
                                        string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_READ);

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/single_project_home.twig',
                'page_title' => 'Project ' . $project->get_project_title(),
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
     * 
     */
    public function new_project( ResponseInterface $response) :ResponseInterface {
        try {
            if (array_key_exists(static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?IFlowProject $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY];
                $_SESSION[static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $form_in_progress = new FlowProject();
                }
            } else {
                $form_in_progress = new FlowProject();
            }
            $form_in_progress->set_admin_user_id( $this->user->flow_user_id);
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
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * 
     */
    public function clone_project( ResponseInterface $response) :ResponseInterface {
        return $this->view->render($response, 'main.twig', [
            'page_template_path' => 'project/clone_project.twig',
            'page_title' => 'Clone Project',
            'page_description' => 'Clone a project from a repo'

        ]);
    }

    /**
     * makes new project and then redirects to new project page
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param string $guid
     * @return ResponseInterface
     * @throws Exception
     * 
     */
    public function clone_project_from_local(ServerRequestInterface $request, ResponseInterface $response, string $guid) :ResponseInterface {
        $project = null;
        try {
            $args = $request->getParsedBody();
            $csrf = new FlowAntiCSRF($args,$_SESSION,FlowAntiCSRF::$fake_server);
            if (!$csrf->validateRequest()) {
                throw new HttpForbiddenException($request,"Bad Request. Refresh Page") ;
            }
            $project = $this->get_project_helper()->copy_project_from_guid($request,$guid);

            try {
                UserPages::add_flash_message('success', "Created Project " . $project->get_project_title());
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('single_project_home',[
                    "user_name" => $project->get_admin_user()->flow_user_name,
                    "project_name" => $project->get_project_title()
                ]);
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to project pageafter successful creation", ['exception' => $e]);
                throw $e;
            }
        } catch (Exception $e) {
            try {
                UserPages::add_flash_message('warning', "Cannot clone project " . $e->getMessage());
                $_SESSION[static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY] = $project;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('clone_project');
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
     * @return ResponseInterface
     * @throws Exception
     * 
     */
    public function create_project(ServerRequestInterface $request, ResponseInterface $response) :ResponseInterface
    {
        $project = null;
        try {
            $csrf = new AntiCSRF;
            if (!empty($_POST)) {
                if (!$csrf->validateRequest()) {
                    throw new HttpForbiddenException($request,"Bad Request. Refresh Page") ;
                }
            }
            $project = new FlowProject();
            $project->set_project_type( IFlowProject::FLOW_PROJECT_TYPE_TOP);
            $project->set_admin_user_id( FlowUser::get_logged_in_user()->flow_user_id);

            $args = $request->getParsedBody();
            $project->set_project_title($args['flow_project_title']);
            $project->set_project_blurb($args['flow_project_blurb']);
            $project->set_public(isset($args['is_public']) && intval($args['is_public']));
            $project->set_read_me($args['flow_project_readme_bb_code']);

            $project->save();
            $_SESSION[static::REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY] = null;
            try {
                UserPages::add_flash_message('success', "Created Project " . $project->get_project_title());
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
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * 
     */
    public function edit_project( ServerRequestInterface $request,ResponseInterface $response,
                                         string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            if (array_key_exists(static::REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?IFlowProject $form_in_progress
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
     * 
     */
    public function update_project(ServerRequestInterface $request,ResponseInterface $response,
                                   string $user_name, string $project_name) :ResponseInterface {

        $project = null;
        try {
            $csrf = new AntiCSRF;

            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);

            $args = $request->getParsedBody();


            $project->set_project_title($args['flow_project_title']);
            $project->set_project_blurb($args['flow_project_blurb']);
            $project->set_public(isset($args['is_public']) && intval($args['is_public']));
            $project->set_read_me($args['flow_project_readme_bb_code']);

            if (!empty($_POST)) {
                if (!$csrf->validateRequest()) {
                    throw new HttpForbiddenException($request,"Bad Request. Refresh Page") ;
                }
            }

            if (empty($args['flow_project_git_hash'])) {
                if ($project->getFlowProjectFiles()->get_head_commit_hash()) {
                    throw new InvalidArgumentException("Missing flow_project_git_hash");
                }
            }

            $old_git_hash = $args['flow_project_git_hash'];
            if ($project->getFlowProjectFiles()->get_head_commit_hash() !== $old_git_hash) {
                throw new InvalidArgumentException("Git hash is too old, project was saved since this page loaded");
            }

            $project->save();
            $_SESSION[static::REM_EDIT_PROJECT_WITH_ERROR_SESSION_KEY] = null;

            try {
                UserPages::add_flash_message('success', "Updated Project  " . $project->get_project_title(). " <br> Saved");
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('single_project_home',[
                    "user_name" => $project->get_admin_user()->flow_user_name,
                    "project_name" => $project->get_project_title()
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
     * 
     */
    public function edit_project_permissions( ServerRequestInterface $request,ResponseInterface $response,
                                  string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_ADMIN);

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
     * 
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
                throw new HttpForbiddenException($request,"Bad Request. Refresh Page") ;
            }

            $x_header = $request->getHeader('X-Requested-With') ?? [];
            if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
                throw new InvalidArgumentException("Need the X-Requested-With header");
            }

            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_ADMIN);

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
                        $perm->flow_project_id = $project->get_id();
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
                    $project->set_public(isset($args['is_public']) && intval($args['is_public']));
                    $project->save();
                    $inner_data = $project;
                    break;
                }
                default: {
                    throw new InvalidArgumentException("Unknown Action Verb: $action");
                }
            }
            $data = ['success'=>true,'message'=>'','data'=>$inner_data,'token'=> $token];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $token];
            $payload = JsonHelper::toString($data);

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
     * 
     */
    public function edit_project_tags( ServerRequestInterface $request,ResponseInterface $response,
                                              string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_READ);

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            $tag_guid = null;
            $args = $request->getQueryParams();
            if (isset($args['tag_guid']) && WillFunctions::is_valid_guid_format($args['tag_guid'])) {
                $tag_guid = $args['tag_guid'];
            }
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/project_tags.twig',
                'page_title' => "Edit Tags for Project $project_name",
                'page_description' => 'Manage the tags set in this project',
                'selected_tag_guid' => $tag_guid,
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
     * @param string $setting_name
     * @return ResponseInterface
     */
    public function set_project_setting(ServerRequestInterface $request,ResponseInterface $response,
                                   string $user_name, string $project_name, string $setting_name): ResponseInterface
    {

        $ret_tag = $ret_attribute = $setting_tag = $setting_standard_value = $standard_attribute_name=  null;

        try {
            $option = new AjaxCallData([
                AjaxCallData::OPTION_IS_AJAX,
                AjaxCallData::OPTION_MAKE_NEW_TOKEN,
                AjaxCallData::OPTION_VALIDATE_TOKEN
            ]);

            $option->note = 'set_project_setting';
            $option->permission_mode = FlowProjectUser::PERMISSION_COLUMN_ADMIN;
            $call = $this->get_project_helper()->validate_ajax_call($option, $request, null, $user_name, $project_name);

            $project = $call->project;

            if (!$project) {
                throw new HttpNotFoundException($request, "[set_project_setting] Project $project_name Not Found");
            }


            if (!isset(IFlowProject::STANDARD_SETTINGS[$setting_name])) {
                throw new InvalidArgumentException("[set_standard_name] $setting_name is not a valid setting for projects");
            }
            $setting_details = IFlowProject::STANDARD_SETTINGS[$setting_name];
            $standard_attribute_name = $setting_details['standard_attribute_name'];

            if (isset($call->args->tag_guid) && $call->args->tag_guid) {
                //set setting

                $setting_tag_guid = $call->args->tag_guid;
                $tag_params = new FlowTagSearchParams();
                $tag_params->tag_guids[] = $setting_tag_guid;
                $setting_tag_array  = FlowTagSearch::get_tags($tag_params);
                if (empty($setting_tag_array)) {
                    throw new InvalidArgumentException("[set_project_setting] could not find tag by tag_guid ". $setting_tag_guid);
                }

                $setting_tag = $setting_tag_array[0];
                //make sure can read the tag
                if ($setting_tag->flow_project_guid !== $project->get_project_guid()) {
                    $setting_project = $this->get_project_helper()->get_project_with_permissions(
                        $request,$setting_tag->flow_project_admin_user_guid,$setting_tag->flow_project_guid,
                        FlowProjectUser::PERMISSION_COLUMN_READ);
                    if (!$setting_project) {
                        throw new InvalidArgumentException(
                            "[set_project_setting] You do not have permission to read the project from the tag");
                    }
                }

                //make sure setting tag has the proper data
                $setting_standard_value = $setting_tag->hasStandardAttribute($standard_attribute_name);
                if (!$setting_standard_value) {
                    throw new LogicException(
                        "[set_project_setting] Could not find standard attribute of type $setting_name for tag ".
                        $setting_tag->flow_tag_guid);
                }
            } else {
                //remove setting
                $setting_tag_guid = null;
            }


            $holding_tag = $project->get_setting_holder_tag($setting_name);

            $holding_attribute = $holding_tag->get_or_create_attribute($setting_name);
            $holding_attribute->setPointsToFlowTagGuid($setting_tag_guid);
            $holding_attribute->setPointsToTagId(null); //need this to save

            $holding_tag->save(true, true);

            $ret_tag = $holding_tag->clone_refresh();
            $ret_attribute = $ret_tag->get_or_create_attribute($setting_name);

            $project->save();

            $data = [
                'success'=>true,
                'message'=>'ok',
                'holding_tag'=>$ret_tag,
                'holding_attribute'=>$ret_attribute,
                'setting_tag'=>$setting_tag,
                'setting_name'=>$setting_name,
                'standard_name'=>$standard_attribute_name,
                'standard_value'=>$setting_standard_value,
                'token'=> $call->new_token
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (Exception $e) {
            $this->logger->error("Could not set_standard_setting: ".$e->getMessage(),['exception'=>$e]);
            $data = [
                'success'=>false,
                'message'=>$e->getMessage(),
                'holding_tag'=>$ret_tag,
                'holding_attribute'=>$ret_attribute,
                'setting_tag'=>$setting_tag,
                'setting_name'=>$setting_name,
                'standard_name'=>$standard_attribute_name,
                'standard_value'=>$setting_standard_value,
                'token'=> $call->new_token?? null
            ];
            $payload = JsonHelper::toString($data);

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
     * 
     */
    public function destroy_project(ServerRequestInterface $request,ResponseInterface $response,
                                    string $user_name, string $project_name) :ResponseInterface {


        $token = null;
        try {
            $args = $request->getParsedBody();
            $csrf = new FlowAntiCSRF($args,$_SESSION,FlowAntiCSRF::$fake_server);
            if (!$csrf->validateRequest()) {
                throw new HttpForbiddenException($request,"Bad Request. Refresh Page") ;
            }
            $token = $csrf->getTokenArray();

            $project = $this->get_project_helper()->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_ADMIN);
            $project->destroy_project();


            $data = ['success'=>true,'message'=>'','flow_project_guid'=>$project->get_project_guid(),'token'=> $token];
            $payload = JsonHelper::toString($data);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(202);


        } catch (Exception $e) {
            $data = ['success'=>false,'message'=>$e->getMessage(),'flow_project_guid'=>$project_name,'token'=> $token];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }

    }

}