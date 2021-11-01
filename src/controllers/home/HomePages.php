<?php
namespace app\controllers\home;

use app\controllers\project\ProjectPages;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\project\FlowProjectSearch;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagSearchParams;
use app\models\user\FlowUser;
use Delight\Auth\Auth;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;


class HomePages
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
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function root( ResponseInterface $response) :ResponseInterface {
        try {
            exec('cd /var/www/flow_projects/ && cat times.txt  2>&1',$output,$result_code);
            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'root.twig',
                'page_title' => 'Root',
                'page_description' => 'No Place Like Home',
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render root page",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @noinspection PhpUnused
     */
    public function php_info( ResponseInterface $response) :ResponseInterface {
        ob_start();
        phpinfo();
        $info = ob_get_clean();
        $response->getBody()->write($info);
        return $response;
    }

    /**
     * @param string $guid
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws DependencyException
     * @throws HttpNotFoundException
     * @throws NotFoundException
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function link_show( string $guid,ServerRequestInterface $request,ResponseInterface $response) :ResponseInterface {
        /*
         * Does a general search for the guid, then gets info for a full link and redirects
         */
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $search = new GeneralSearchParams();
        $search->guids = [$guid];
        $matches = GeneralSearch::general_search($search);
        if (count($matches)) {
            $what = $matches[0];
            switch ($what->type) {


                case GeneralSearch::TYPE_ENTRY: {

                    $entry_search = new FlowEntrySearchParams();
                    $entry_search->entry_guids = [$guid];
                    $entry_res = FlowEntrySearch::search($entry_search);
                    if (empty($entry_res)) {
                        throw new HttpNotFoundException($request,"Cannot find entry ". $guid);
                    }
                    $entry = $entry_res[0];
                    $entry_project = $entry->get_project();

                    $route_to_go_to = $routeParser->urlFor('show_entry',[
                        "user_name" => $entry_project->get_admin_user()->flow_user_guid,
                        "project_name" => $entry_project->flow_project_guid,
                        "entry_name" => $entry->get_guid()
                    ]);

                    break;
                }
                case GeneralSearch::TYPE_PROJECT: {

                    $project = null;
                    $projects_found = FlowProjectSearch::find_projects([$guid]);
                    foreach ($projects_found as $found_project) {
                        $project =  $found_project;
                        break;
                    }
                    if (!$project) {
                        throw new HttpNotFoundException($request,"Cannot find project ". $guid);
                    }


                    $route_to_go_to = $routeParser->urlFor('single_project_home',[
                        "user_name" => $project->get_admin_user()->flow_user_guid,
                        "project_name" => $project->flow_project_guid
                    ]);


                    break;
                }
                case GeneralSearch::TYPE_USER: {

                    $route_to_go_to = $routeParser->urlFor('user_page',[
                        "user_name" => $guid
                    ]);

                    break;
                }
                case GeneralSearch::TYPE_TAG: {
                    $tag_search = new FlowTagSearchParams();
                    $tag_search->tag_guids[] = $guid;
                    $tag_res = FlowTag::get_tags($tag_search);
                    if (empty($tag_res)) {
                        throw new HttpNotFoundException($request,"Cannot find Tag ". $guid);
                    }
                    $tag = $tag_res[0];
                    $route_to_go_to = $routeParser->urlFor('single_project_home',[
                        "user_name" => $tag->flow_project_admin_user_guid,
                        "project_name" => $tag->flow_project_guid
                    ]);

                    break;
                }
                default: {
                    throw new RuntimeException("Cannot recognize this type: ". $what->type);
                }
            }

            $response = $response->withStatus(302);
            return $response->withHeader('Location', $route_to_go_to);
        } else {
            throw new HttpNotFoundException($request,"Resource not found");
        }
    }
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function general_search( ServerRequestInterface $request,ResponseInterface $response) :ResponseInterface {

        $args = $request->getQueryParams();


        $search = new GeneralSearchParams();

        $root = $args;
        if (isset($args['search'])) { $root = $args['search'];}

        if (isset($root['term'])) {
            $search->title = trim($root['term']);
        }

        if (isset($root['guid'])) {
            $search->guids[] = trim($root['guid']);
        }

        if (isset($root['title'])) {
            $search->title = trim($root['title']);
        }

        if (isset($root['created_at_ts']) && intval($root['created_at_ts'])) {
            $search->created_at_ts = (int)($root['created_at_ts']);
        }

        if (isset($root['types'])) {
            if (is_array($root['types']))
            {
                foreach ($root['types'] as $a_type)
                {
                    $search->types[] = $a_type;
                }
            }
            else
            {
                if ($root['types'] === GeneralSearch::ALL_TYPES_KEYWORD )
                {
                    $search->types = GeneralSearch::ALL_TYPES;
                }
                elseif ($root['types'] === GeneralSearch::ALL_TYPES_BUT_TAGS_KEYWORD)
                {
                    $search->types = GeneralSearch::ALL_TYPES_BUT_TAGS;
                }
                else
                {
                    $search->types[] = $root['types'];
                }
            }
        }



        $page = 1;
        if (isset($args['page'])) {
            $page_number = intval($args['page']);
            if ($page_number > 0) {
                $page = $page_number;
            }
        }


        $matches = GeneralSearch::general_search($search,$page);
        $b_more = true;
        if (count($matches) < GeneralSearch::DEFAULT_PAGE_SIZE) {
            $b_more = false;
        }
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        foreach ($matches as &$match) {
            $match->url = $routeParser->urlFor('link_show',[
                "guid" => $match->guid
            ]);
        }


        $data = [
            "results" => $matches,
            "pagination" => [
                "more" => $b_more,
                "page" => $page
            ]
        ];

        $payload = json_encode($data);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
}