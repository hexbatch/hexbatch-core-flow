<?php
namespace app\project;

use app\exceptions\HexletErrorToUser;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Monolog\Logger;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Views\Twig;


class ProjectPages
{

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
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function all_projects(RequestInterface $request, ResponseInterface $response) :ResponseInterface {
        if ($this->user->id) {
            return $this->all_projects_overview_logged_in($request,$response);
        } else {
            return $this->all_projects_overview_for_anon($request,$response);
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function all_projects_overview_for_anon(RequestInterface $request, ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/all_projects_overview_for_anon.twig',
                'page_title' => 'All Projects',
                'page_description' => 'Shows all projects',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }


    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function all_projects_overview_logged_in(RequestInterface $request, ResponseInterface $response) :ResponseInterface {
        try {
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/all_projects_overview_logged_in.twig',
                'page_title' => 'Your Projects',
                'page_description' => 'Shows projects for user',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render log in form page",['exception'=>$e]);
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $user_name
     * @param string $project_name
     * @return ResponseInterface
     * @throws HttpInternalServerErrorException
     * @noinspection PhpUnused
     */
    public function single_project_home(RequestInterface $request, ResponseInterface $response,
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
            throw new HexletErrorToUser($request,$e->getMessage());
        }
    }


}