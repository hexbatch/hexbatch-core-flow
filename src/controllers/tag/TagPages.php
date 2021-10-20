<?php
namespace app\controllers\tag;

use app\controllers\base\BasePages;
use app\controllers\project\ProjectPages;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagAttribute;
use app\models\tag\FlowTagCallData;
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
        $search_params->owning_project_guid = $project->flow_project_guid;
        if (isset($args['search'])) {

            if (isset($args['search']['tag_guid'])) {
                $search_params->tag_guids[] = trim($args['search']['tag_guid']);
            }

            if (isset($args['search']['term'])) {
                $search_params->tag_name_term = trim(JsonHelper::to_utf8($args['search']['term']));
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

        //add attached tags
        $tag_id_array = [];

        /**
         * @var array<string,FlowTag> $match_map
         */
        $match_map = [];
        foreach ($matches as $tag_rehydrated) {
            $tag_id_array[] = $tag_rehydrated->flow_tag_id;
            $match_map[$tag_rehydrated->flow_tag_guid] = $tag_rehydrated;
        }

        $attached_map = FlowAppliedTag::get_applied_tags($tag_id_array);

        foreach ($attached_map as $tag_guid => $applied_array) {
            if (array_key_exists($tag_guid,$match_map)) {
                $match_map[$tag_guid]->applied = $applied_array;
            }
        }

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
     * creates or edits tag
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function create_tag( ServerRequestInterface $request,ResponseInterface $response,
                              string $user_name, string $project_name) :ResponseInterface {
        $token = null;
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'create_tag';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name);

            $baby_steps = new FlowTag($call->args);
            $baby_steps->flow_project_id = $call->project->id;
            $tag = $baby_steps->clone_with_missing_data();
            if ($tag->flow_tag_id || $tag->flow_tag_guid) {
                throw new InvalidArgumentException("Can only create new tags with this action. Do not set id or guid");
            }
            $tags_already_in_project = $call->project->get_all_owned_tags_in_project();

            foreach ($tags_already_in_project as $look_tag) {
                if ($look_tag->flow_tag_name === $tag->flow_tag_guid) {
                    throw new InvalidArgumentException("Cannot create tag because a tag already has the same name in this project");
                }
            }

            $tag->save(true);
            $saved_tag = $tag->clone_refresh();


            $data = ['success'=>true,'message'=>'','tag'=>$saved_tag,'token'=> $call->new_token];
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

    }//end set tags


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @param string $tag_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function edit_tag( ServerRequestInterface $request,ResponseInterface $response,
                                string $user_name, string $project_name,string $tag_name) :ResponseInterface
    {
        $token = null;
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'edit_tag';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            $call->tag->save();
            $data = ['success'=>true,'message'=>'ok','tag'=>$call->tag,'attribute'=>null,'token'=> $call->new_token];
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
    } //end function

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @param string $tag_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function delete_tag( ServerRequestInterface $request,ResponseInterface $response,
                                      string $user_name, string $project_name,string $tag_name) :ResponseInterface
    {
        $token = null;
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'delete_tag';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            $call->tag->delete_tag();
            $data = ['success'=>true,'message'=>'ok','tag'=>$call->tag,'attribute'=>null,'token'=> $call->new_token];
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
    } //end function



    /**
     * Used in Ajax Calls
     * validates data, creates new form key too
     * @param FlowTagCallData $options
     * @param ServerRequestInterface $request
     * @param string $route_name
     * @param string $user_name
     * @param string $project_name
     * @param ?string $tag_name
     * @param string|null $attribute_name
     * @return FlowTagCallData
     * @throws
     */
    protected function validate_ajax_call(FlowTagCallData $options, ServerRequestInterface $request, string $route_name, string $user_name,
                                          string $project_name, ?string $tag_name = null ,
                                          ?string $attribute_name = null) : FlowTagCallData
    {

        $args = $request->getParsedBody();
        if (empty($args)) {
            throw new InvalidArgumentException("No data sent");
        }

        $csrf = null;
        if ($options->has_option(FlowTagCallData::OPTION_VALIDATE_TOKEN) ||
            $options->has_option(FlowTagCallData::OPTION_MAKE_NEW_TOKEN)
        ) {
            if ($route_name) {
                $csrf = new FlowAntiCSRF($args);
            } else {
                $csrf = new FlowAntiCSRF($args,$_SESSION,FlowAntiCSRF::$fake_server);
            }
        }

        if ($csrf && $options->has_option(FlowTagCallData::OPTION_VALIDATE_TOKEN) ){
            if (!$csrf->validateRequest()) {
                throw new HttpForbiddenException($request, "Bad Request. Refresh Page");
            }
        }

        if ($csrf && $options->has_option(FlowTagCallData::OPTION_IS_AJAX) ){
            $x_header = $request->getHeader('X-Requested-With') ?? [];
            if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
                throw new InvalidArgumentException("Need the X-Requested-With header");
            }
        }


        /**
         * @var ProjectPages $project_pages
         */
        $project_pages = $this->container->get('projectPages');

        $project = $project_pages->get_project_with_permissions($request,$user_name,$project_name,'write');
        if (!$project) {
            throw new HttpNotFoundException($request,"Project $project_name Not Found");
        }

        $token = null;
        if ($csrf && $options->has_option(FlowTagCallData::OPTION_MAKE_NEW_TOKEN) ) {
            $token_lock_to = null;

            if ($route_name) {
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();

                $lock_to_data = [
                    'user_name' => $user_name,
                    'project_name' => $project_name,
                    'tag_name' => $tag_name,
                    'attribute_name' => $attribute_name
                ];

                $token_lock_to = $routeParser->urlFor($route_name, $lock_to_data);
            }

            $token = $csrf->getTokenArray($token_lock_to);
        }

        $args_as_object = JsonHelper::fromString(JsonHelper::toString($args),true,false);

        $tags = $project->get_all_owned_tags_in_project();

        $ret = new  FlowTagCallData([],$args_as_object,$project,$token);

        if ($tag_name) {

            foreach ($tags as $look_tag) {
                if ($look_tag->flow_tag_guid === $tag_name) { $ret->tag = $look_tag; break;}
                if ($look_tag->flow_tag_name === $tag_name) { $ret->tag = $look_tag; break;}
            }

            if (!$ret->tag) {
                throw new HttpNotFoundException($request,"Tag of '$tag_name' cannot be found" );
            }

            if ($attribute_name) {
                foreach ($ret->tag->attributes as $look_at) { //clever name!!
                    if ($look_at->flow_tag_attribute_guid === $attribute_name) { $ret->attribute = $look_at; break;}
                    if ($look_at->tag_attribute_name === $attribute_name) { $ret->attribute = $look_at; break;}
                }

                if (!$ret->attribute) {
                    throw new HttpNotFoundException($request,"Attribute of '$attribute_name' cannot be found" );
                }
            }//end if attribute name
        } //end if tag name

        if (property_exists($args_as_object,'applied')) {
            $ret->applied = FlowAppliedTag::reconstitute($args_as_object->applied,$ret->tag);
        }

       return $ret;

    }


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @param string $tag_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function create_attribute( ServerRequestInterface $request,ResponseInterface $response,
                              string $user_name, string $project_name,string $tag_name) :ResponseInterface
    {
        $token = null;
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'create_attribute';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            $attribute_data = $call->args->attribute ?? null;
            $attribute_to_add = new FlowTagAttribute($attribute_data);
            $attribute_to_add->flow_tag_id = $call->tag->flow_tag_id;
            if (!$attribute_to_add->has_enough_data_set()) {
                throw new InvalidArgumentException("Need mo' data for attribute");
            }
            foreach ($call->tag->attributes as $look_at) {
                if ($look_at->tag_attribute_name === $attribute_to_add->tag_attribute_name) {
                    throw new InvalidArgumentException("The attribute name of $look_at->tag_attribute_name is already used in the tag");
                }
            }
            $call->tag->attributes[] = $attribute_to_add;
            $altered_tag = $call->tag->save_tag_return_clones($call->attribute->tag_attribute_name,$new_attribute);

            $data = ['success'=>true,'message'=>'ok','tag'=>$altered_tag,'attribute'=>$new_attribute,'token'=> $call->new_token];
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
    } //end function



    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @param string $tag_name
     * @param string $attribute_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function edit_attribute( ServerRequestInterface $request,ResponseInterface $response,
                                      string $user_name, string $project_name,string $tag_name,string $attribute_name) :ResponseInterface
    {
        $token = null;
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'edit_attribute';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name,$attribute_name);
            $attribute_data = $call->args->attribute ?? null;
            $attribute_to_edit = new FlowTagAttribute($attribute_data);
            $attribute_to_edit->flow_tag_id = $call->tag->flow_tag_id;
            if (!$attribute_to_edit->has_enough_data_set()) {
                throw new InvalidArgumentException("Edited attribute does not have enough data set");
            }

            $call->attribute->update_fields_with_public_data($attribute_to_edit);
            $altered_tag = $call->tag->save_tag_return_clones($call->attribute->tag_attribute_name,$new_attribute);

            $data = ['success'=>true,'message'=>'ok','tag'=>$altered_tag,'attribute'=>$new_attribute,'token'=> $call->new_token];


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
    } //end function



    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @param string $tag_name
     * @param string $attribute_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function delete_attribute( ServerRequestInterface $request,ResponseInterface $response,
                                    string $user_name, string $project_name,string $tag_name,string $attribute_name) :ResponseInterface
    {
        $token = null;
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'delete_attribute';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name,$attribute_name);

            $call->attribute->delete_attribute();

            $altered_tag = $call->tag->save_tag_return_clones($call->attribute->tag_attribute_name,$new_attribute);

            $data = ['success'=>true,'message'=>'ok','tag'=>$altered_tag,'attribute'=>$call->attribute,'token'=> $call->new_token];
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
    } //end function


    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @param string $tag_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function create_applied( ServerRequestInterface $request,ResponseInterface $response,
                                string $user_name, string $project_name,string $tag_name) :ResponseInterface
    {
        $token = null;
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'create_applied';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            if(!$call->applied) {
                throw new InvalidArgumentException("Need to set applied when calling this");
            }
            $call->applied->flow_tag_id = $call->tag->flow_tag_id;
            $call->applied->save();
            $applied_to_return = FlowAppliedTag::reconstitute($call->applied->id,$call->tag);

            $data = [
                'success'=>true,'message'=>'ok','tag'=>$call->tag,'attribute'=>null,'applied'=>$applied_to_return,
                'token'=> $call->new_token
            ];
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
    } //end function



    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @param string $user_name
     * @param string $project_name
     * @param string $tag_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function delete_applied( ServerRequestInterface $request,ResponseInterface $response,
                                    string $user_name, string $project_name,string $tag_name) :ResponseInterface
    {
        $token = null;
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'delete_applied';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            if(!$call->applied) {
                throw new InvalidArgumentException("Need to set applied when calling this");
            }
            $call->applied->delete_applied();

            $data = [
                'success'=>true,'message'=>'ok','tag'=>$call->tag,'attribute'=>null,'applied'=>$call->applied,
                'token'=> $call->new_token
            ];
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
    } //end function




} //end class