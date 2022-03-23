<?php
namespace app\controllers\home;

use app\controllers\base\BasePages;
use app\controllers\project\ProjectPages;
use app\controllers\user\UserPages;
use app\helpers\AdminHelper;
use app\helpers\SQLHelper;
use app\hexlet\JsonHelper;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\project\FlowProjectSearch;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagSearchParams;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;


class HomePages extends BasePages
{

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function root( ResponseInterface $response) :ResponseInterface {
        try {
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
     * @param string $guid
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws HttpNotFoundException
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


                    $user_class = $project->get_admin_user();
                    $user_name = '';
                    if ($user_class) { $user_name = $user_class->flow_user_guid; }
                    $project_name = $project->flow_project_guid;

                    /**
                     * @var ProjectPages $project_pages
                     */
                    $project_pages = $this->container->get('projectPages');
                    return $project_pages->single_project_home($request,$response,$user_name,$project_name);

                }
                case GeneralSearch::TYPE_USER: {

                    /**
                     * @var UserPages $user_pages
                     */
                    $user_pages = $this->container->get('userPages');
                    return $user_pages->user_page($request,$response,$guid);
                }
                case GeneralSearch::TYPE_TAG: {
                    $tag_search = new FlowTagSearchParams();
                    $tag_search->tag_guids[] = $guid;
                    $tag_res = FlowTag::get_tags($tag_search);
                    if (empty($tag_res)) {
                        throw new HttpNotFoundException($request,"Cannot find Tag ". $guid);
                    }
                    $tag = $tag_res[0];
                    $route_to_go_to = $routeParser->urlFor('show_tag',[
                        "user_name" => $tag->flow_project_admin_user_guid,
                        "project_name" => $tag->flow_project_guid,
                        "tag_name" => $tag->flow_tag_guid,
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
        if ($this->auth->isLoggedIn()) {
            $search->against_user_guid = $this->user->flow_user_guid;
        } else {
            $search->b_only_public = true;
        }
        $root = $args;
        if (isset($args['search'])) { $root = $args['search'];}

        if (isset($root['term'])) {
            $search->words = trim($root['term']);
        }

        if (isset($root['guid'])) {
            $search->guids[] = trim($root['guid']);
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

        $search->b_get_secondary = true;

        if (isset($args['b_no_secondary']) && JsonHelper::var_to_boolean($args['b_no_secondary'])) {
            $search->b_get_secondary = false;
        }

        $matches = GeneralSearch::general_search($search,$page);
        $b_more = true;
        if (count($matches) < GeneralSearch::DEFAULT_PAGE_SIZE) {
            $b_more = false;
        }
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        foreach ($matches as $match) {
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

        $payload = JsonHelper::toString($data);

        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json');
    }

}