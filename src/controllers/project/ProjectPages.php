<?php
namespace app\controllers\project;

use app\controllers\user\UserPages;
use app\models\project\FlowProject;
use app\models\user\FlowUser;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;


class ProjectPages
{

    const REM_FORM_SESSION_KEY = 'project_form_in_progress';
    protected Auth $auth;
    protected Logger $logger;
    /**
     * @var Container $container
     */
    protected Container $container;

    protected Twig $view;

    protected object $user;

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
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
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
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function single_project_home( ResponseInterface $response,
                                        string $user_name, string $project_name) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/single_project_home.twig',
                'page_title' => 'Your Projects',
                'page_description' => 'Shows projects for user',
                'project' => ['admin_flow_user_name' =>$user_name,'project_name' => $project_name]
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw $e;
        }
    }



    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function new_project_form( ResponseInterface $response) :ResponseInterface {
        try {
            if (array_key_exists(static::REM_FORM_SESSION_KEY,$_SESSION)) {
                /**
                 * @var ?FlowProject $form_in_progress
                 */
                $form_in_progress = $_SESSION[static::REM_FORM_SESSION_KEY];
                $_SESSION[static::REM_FORM_SESSION_KEY] = null;
                if (empty($form_in_progress)) {
                    $form_in_progress = new FlowProject();
                }
            } else {
                $form_in_progress = new FlowProject();
            }
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/new_project_form.twig',
                'page_title' => 'Make A New Project',
                'page_description' => 'New Project Form',
                'form_in_progress' => $form_in_progress


            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function create_new_project(RequestInterface $request, ResponseInterface $response) :ResponseInterface
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
            $project->flow_project_readme = $args['flow_project_readme'];

            $project->save();

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
                $_SESSION[static::REM_FORM_SESSION_KEY] = $project;
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();
                $url = $routeParser->urlFor('new_project_form');
                $response = $response->withStatus(302);
                return $response->withHeader('Location', $url);
            } catch (Exception $e) {
                $this->logger->error("Could not redirect to create project after error", ['exception' => $e]);
                throw $e;
            }
        }

    }


}