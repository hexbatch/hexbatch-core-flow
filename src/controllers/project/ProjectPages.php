<?php
namespace app\controllers\project;

use app\controllers\user\UserPages;
use app\models\project\FlowProject;
use app\models\project\FlowProjectUser;
use app\models\user\FlowUser;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use InvalidArgumentException;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;


class ProjectPages
{

    const REM_NEW_PROJECT_WITH_ERROR_SESSION_KEY = 'project_form_in_progress_has_error';
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
        if ($this->user->id) {
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
            $my_projects= FlowProject::get_all_top_projects( FlowUser::get_logged_in_user()->id);
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
                'page_title' => 'Your Projects',
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
            $form_in_progress->admin_flow_user_id = $this->user->id;
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
            $project = new FlowProject();
            $project->flow_project_type = FlowProject::FLOW_PROJECT_TYPE_TOP;
            $project->parent_flow_project_id = null;
            $project->admin_flow_user_id = FlowUser::get_logged_in_user()->id;

            $args = $request->getParsedBody();
            $project->flow_project_title = $args['flow_project_title'];
            $project->flow_project_blurb = $args['flow_project_blurb'];
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
     * @param string $permission read|write
     * @return FlowProject|null
     * @throws Exception
     */
    protected  function get_project_with_permissions(
        ServerRequestInterface $request,string $user_name, string $project_name,string $permission) : ?FlowProject {
        if ($permission !== 'read' && $permission !== 'write' && $permission !== 'admin') {
            throw new InvalidArgumentException("permission has to be read or write or admin");
        }
        if (($this->user->flow_user_name !== $user_name)) {

            if ($permission === 'admin') {
                throw new HttpForbiddenException($request,"Only the admin can edit this part of the project $project_name");
            }
            //if we are not checking for the project admin user, then check for other users that can edit
            $maybe_edit_permissions = FlowProjectUser::find_users_in_project($project_name,$user_name);
            if (empty($maybe_edit_permissions)) {
                throw new HttpForbiddenException($request,"Cannot edit this project $project_name");
            }
            if ($permission === 'write') {
                if ( ! $maybe_edit_permissions[0]->can_write) {
                    throw new HttpForbiddenException($request,"You can view but not edit this project");
                }
            } elseif ($permission === 'read') {
                if ( ! $maybe_edit_permissions[0]->can_read) {
                    throw new HttpForbiddenException($request,"You cannot view this project");
                }
            }
        } //end if the project user is not the logged in user

        $project = FlowProject::find_one($project_name,$user_name);
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
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/edit_project.twig',
                'page_title' => "Edit Project $project_name",
                'page_description' => 'Edits this project',
                'project' => $project,
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
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'write');

            $args = $request->getParsedBody();
            $project->flow_project_title = $args['flow_project_title'];
            $project->flow_project_blurb = $args['flow_project_blurb'];
            $project->set_read_me($args['flow_project_readme_bb_code']);

            $project->save();

            try {
                UserPages::add_flash_message('success', "Updated Project " . $project->flow_project_title);
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
     * @noinspection PhpUnused
     */
    public function edit_project_permissions( ServerRequestInterface $request,ResponseInterface $response,
                                  string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'admin');

            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            $write_users = [];
            $read_users = [];
            $users_in_project = $project->get_flow_project_users();
            foreach ($users_in_project as $up) {
                if ($up->can_write) {
                    $write_users[] = $up;
                }
                if ($up->can_read) {
                    $read_users[] = $up;
                }
            }
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/project_permission.twig',
                'page_title' => "Edit Permissions for Project $project_name",
                'page_description' => 'Sets who can update and see this',
                'project' => $project,
                'write_users' => $write_users,
                'read_users' => $read_users
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
    public function edit_project_tags( ServerRequestInterface $request,ResponseInterface $response,
                                              string $user_name, string $project_name) :ResponseInterface {
        try {
            $project = $this->get_project_with_permissions($request,$user_name,$project_name,'admin');

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


}