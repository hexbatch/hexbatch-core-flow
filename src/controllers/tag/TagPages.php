<?php
namespace app\controllers\tag;

use app\controllers\base\BasePages;
use app\controllers\project\ProjectPages;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagSearchParams;
use app\models\user\FlowUser;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;


class TagPages extends BasePages
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function get_tags( ServerRequestInterface $request,ResponseInterface $response,
                              string $user_name, string $project_name) :ResponseInterface {

        /**
         * @var ProjectPages $project_pages
         */
        $project_pages = $this->container->get('projectPages');

        $project = $project_pages->get_project_with_permissions($request,$user_name,$project_name,'read');

        if (!$project) {
            throw new HttpNotFoundException($request,"Project $project_name Not Found");
        }
        $args = $request->getQueryParams();
        $search_params = new FlowTagSearchParams();
        $search_params->project_guid = $project->flow_project_guid;
        if (isset($args['search'])) {

            if (isset($args['search']['tag_guid'])) {
                $search_params->tag_guid = trim($args['search']['tag_guid']);
            }
        }

        $page = 1;
        if (isset($args['page'])) {
            $page_number = intval($args['page']);
            if ($page_number > 0) {
                $page = $page_number;
            }
        }

        $matches = FlowTag::get_tags($search_params,$page);
        $b_more = true;
        if (count($matches) < FlowUser::DEFAULT_USER_PAGE_SIZE) {
            $b_more = false;
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

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function set_tags( ServerRequestInterface $request,ResponseInterface $response,
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

            /**
             * @var ProjectPages $project_pages
             */
            $project_pages = $this->container->get('projectPages');

            $project = $project_pages->get_project_with_permissions($request,$user_name,$project_name,'write');
            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            $token_lock_to = $routeParser->urlFor('set_tags_ajax',[
                'user_name' => $user_name,
                'project_name' => $project_name
            ]);

            $token = $csrf->getTokenArray($token_lock_to);

            $args_as_object = JsonHelper::fromString(JsonHelper::toString($args),true,false);

            $baby_steps = new FlowTag($args_as_object);
            $baby_steps->flow_project_id = $project->id;
            $tag = $baby_steps->clone_with_missing_data();
            $tag->save(true);
            $saved_tag = $tag->clone_refresh();


            $data = ['success'=>true,'message'=>'','tag'=>$saved_tag,'token'=> $token];
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




}