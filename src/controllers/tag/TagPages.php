<?php
namespace app\controllers\tag;

use app\controllers\base\BasePages;
use app\helpers\ProjectHelper;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\models\multi\GeneralSearch;
use app\models\project\FlowProjectUser;
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
     * @param string $tag_name
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function show_tag( ServerRequestInterface $request,ResponseInterface $response,
                              string $user_name, string $project_name,string $tag_name) :ResponseInterface
    {
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_GET_APPLIED,FlowTagCallData::OPTION_ALLOW_EMPTY_BODY]);
            $option->note = 'show_tag';

            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            $call->tag->refresh_inherited_fields();


            return $this->view->render($response, 'main.twig', [
                'page_template_path' => 'project/show-tag.twig',
                'page_title' => 'Tag ' . $call->tag->flow_tag_name,
                'page_description' => 'Shows tag details',
                'project' => $call->project,
                'tag' => $call->tag,
            ]);
        } catch (Exception $e) {
            $this->logger->error("Could not render show tag page",['exception'=>$e]);
            throw $e;
        }
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
    public function get_tags( ServerRequestInterface $request,ResponseInterface $response,
                              string $user_name, string $project_name) :ResponseInterface {

        try {
            $project_helper = ProjectHelper::get_project_helper();

            $project = $project_helper->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_READ);

            if (!$project) {
                throw new HttpNotFoundException($request, "Project $project_name Not Found");
            }
            $page_size = GeneralSearch::DEFAULT_PAGE_SIZE;
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

                if (isset($args['search']['not_applied_to_guids']) && $args['search']['not_applied_to_guids']) {
                    if (is_array($args['search']['not_applied_to_guids'])) {
                        foreach ($args['search']['not_applied_to_guids'] as $not_for_guid) {
                            $search_params->not_applied_to_guids[] = trim(JsonHelper::to_utf8($not_for_guid));
                        }
                    } else {
                        $search_params->not_applied_to_guids[] = trim(JsonHelper::to_utf8($args['search']['not_applied_to_guid']));
                    }
                }
                if (isset($args['search']['only_applied_to_guids']) && $args['search']['only_applied_to_guids']) {
                    if (is_array($args['search']['only_applied_to_guids'])) {
                        foreach ($args['search']['only_applied_to_guids'] as $not_for_guid) {
                            $search_params->only_applied_to_guids[] = trim(JsonHelper::to_utf8($not_for_guid));
                        }
                    } else {
                        $search_params->only_applied_to_guids[] = trim(JsonHelper::to_utf8($args['search']['only_applied_to_guids']));
                    }
                }

                if (isset($args['search']['b_all_tags_in_project']) && $args['search']['b_all_tags_in_project']) {
                    $search_params->owning_project_guid = $project->flow_project_guid;
                    $page_size = GeneralSearch::UNLIMITED_RESULTS_PER_PAGE;
                }

            }

            $search_params->flag_get_applied = true;
            $page = 1;
            if (isset($args['page'])) {
                $page_number = intval($args['page']);
                if ($page_number > 0) {
                    $page = $page_number;
                }
            }

            $matches = FlowTag::get_tags($search_params, $page,$page_size);
            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            foreach ($matches as $mtag) {
                foreach ($mtag->applied as $mapp) {
                    $mapp->set_link_for_tagged($routeParser);
                }

                foreach ($mtag->attributes as $matt) {
                    $matt->set_link_for_pointee($routeParser);
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

            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->logger->error("Could not get_tag: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),
                'results'=>[],"pagination" => [
                                    "more" => false,
                                    "page" => $page??null
                                ]
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
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
                if ($look_tag->flow_tag_name === $tag->flow_tag_name) {
                    throw new InvalidArgumentException(
                        "Cannot create tag because a tag already has the same name '$tag->flow_tag_name' in this project");
                }
            }

            $tag->save(true);
            $saved_tag = $tag->clone_refresh();
            $saved_tag->update_flow_things_with_standard_attibutes();

            $call->project->do_tag_save();
            $data = ['success'=>true,'message'=>'ok','tag'=>$saved_tag,'token'=> $call->new_token];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $this->logger->error("Could not create_tag: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $call->new_token?? null];
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
        
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'edit_tag';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            if (property_exists($call->args,'flow_tag_parent')) {
                unset($call->args->flow_tag_parent);
            }
            $baby_steps = new FlowTag($call->args);
            $baby_steps->flow_project_id = $call->project->id;
            $tag = $baby_steps->clone_with_missing_data();
            if (!$tag->flow_tag_id || !$tag->flow_tag_guid) {
                throw new InvalidArgumentException("Can only edit tags when found id and guid with this action");
            }
            $tag->save();
            $saved_tag = $tag->clone_refresh();
            $saved_tag->update_flow_things_with_standard_attibutes();

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            foreach ( $saved_tag->applied as $mapp) {
                $mapp->set_link_for_tagged($routeParser);
            }

            foreach ( $saved_tag->attributes as $matt) {
                $matt->set_link_for_pointee($routeParser);
            }

            $call->project->do_tag_save();

            $data = ['success'=>true,'message'=>'ok','tag'=>$saved_tag,'attribute'=>null,'token'=> $call->new_token];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $this->logger->error("Could not edit_tag: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $call->new_token?? null];
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
        
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'delete_tag';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            $call->tag->delete_tag();
            $call->project->do_tag_save();
            $data = ['success'=>true,'message'=>'ok','tag'=>$call->tag,'attribute'=>null,'token'=> $call->new_token];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $this->logger->error("Could not delete_tag: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $call->new_token?? null];
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
     * @param string|null $route_name
     * @param string $user_name
     * @param string $project_name
     * @param ?string $tag_name
     * @param string|null $attribute_name
     * @return FlowTagCallData
     * @throws
     */
    protected function validate_ajax_call(FlowTagCallData $options, ServerRequestInterface $request,
                                          ?string $route_name, string $user_name,
                                          string $project_name, ?string $tag_name = null ,
                                          ?string $attribute_name = null) : FlowTagCallData
    {

        $token = null;
        $args = $request->getParsedBody();
        if (empty($args)) {
            if (!$options->has_option(FlowTagCallData::OPTION_ALLOW_EMPTY_BODY)) {
                throw new InvalidArgumentException("No data sent");
            }

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


        $project_helper = ProjectHelper::get_project_helper();

        $project = $project_helper->get_project_with_permissions($request,$user_name, $project_name, FlowProjectUser::PERMISSION_COLUMN_WRITE);
        if (!$project) {
            throw new HttpNotFoundException($request,"Project $project_name Not Found");
        }

        
        if ($csrf && $options->has_option(FlowTagCallData::OPTION_MAKE_NEW_TOKEN) ) {
            $token_lock_to = '';

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

        $tags = $project->get_all_owned_tags_in_project($options->has_option(FlowTagCallData::OPTION_GET_APPLIED));

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
                    if ($look_at->getFlowTagAttributeGuid() === $attribute_name) { $ret->attribute = $look_at; break;}
                    if ($look_at->getTagAttributeName() === $attribute_name) { $ret->attribute = $look_at; break;}
                }

                if (!$ret->attribute) {
                    throw new HttpNotFoundException($request,"Attribute of '$attribute_name' cannot be found" );
                }
            }//end if attribute name
        } //end if tag name

        if ($args_as_object && property_exists($args_as_object,'applied')) {
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
        
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'create_attribute';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            $attribute_data = $call->args ?? null;
            $attribute_to_add = new FlowTagAttribute($attribute_data);
            $attribute_to_add->setFlowTagId($call->tag->flow_tag_id)  ;
            if (!$attribute_to_add->has_enough_data_set()) {
                throw new InvalidArgumentException("Need mo' data for attribute");
            }
            foreach ($call->tag->attributes as $look_at) {
                if ($look_at->getTagAttributeName() === $attribute_to_add->getTagAttributeName()) {
                    throw new InvalidArgumentException("The attribute name of ".$look_at->getTagAttributeName()." is already used in the tag");
                }
            }
            $call->tag->attributes[] = $attribute_to_add;
            $altered_tag = $call->tag->save_tag_return_clones($attribute_to_add->getTagAttributeName(),$new_attribute);

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            foreach ( $altered_tag->applied as $mapp) {
                $mapp->set_link_for_tagged($routeParser);
            }

            foreach ( $altered_tag->attributes as $matt) {
                $matt->set_link_for_pointee($routeParser);
            }

            $call->project->do_tag_save();
            $data = ['success'=>true,'message'=>'ok','tag'=>$altered_tag,'attribute'=>$new_attribute,'token'=> $call->new_token];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $this->logger->error("Could not create_attribute: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $call->new_token?? null];
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
        
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'edit_attribute';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name,$attribute_name);
            $attribute_data = $call->args ?? null;
            $attribute_to_edit = new FlowTagAttribute($attribute_data);
            $attribute_to_edit->setFlowTagId($call->tag->flow_tag_id);
            if (!$attribute_to_edit->has_enough_data_set()) {
                throw new InvalidArgumentException("Edited attribute does not have enough data set");
            }

            $call->attribute->update_fields_with_public_data($attribute_to_edit);
            $altered_tag = $call->tag->save_tag_return_clones($attribute_to_edit->getTagAttributeName(),$new_attribute);

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            foreach ( $altered_tag->applied as $mapp) {
                $mapp->set_link_for_tagged($routeParser);
            }

            foreach ( $altered_tag->attributes as $matt) {
                $matt->set_link_for_pointee($routeParser);
            }

            $call->project->do_tag_save();

            $data = ['success'=>true,'message'=>'ok attribute','tag'=>$altered_tag,'attribute'=>$new_attribute,'token'=> $call->new_token];


            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $this->logger->error("Could not edit_attribute: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $call->new_token?? null];
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
        
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'delete_attribute';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name,$attribute_name);


            //remove attribute from tag object
            $new_attribute_list = [];
            foreach ($call->tag->attributes as $byby) {
                if ($byby->getFlowTagAttributeGuid() === $call->attribute->getFlowTagAttributeGuid()) { continue;}
                $new_attribute_list[] = $byby;
            }
            $call->tag->attributes = $new_attribute_list;

            $call->attribute->delete_attribute();

            $altered_tag = $call->tag->save_tag_return_clones(null,$new_attribute);

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            foreach ( $altered_tag->applied as $mapp) {
                $mapp->set_link_for_tagged($routeParser);
            }

            foreach ( $altered_tag->attributes as $matt) {
                $matt->set_link_for_pointee($routeParser);
            }

            $call->project->do_tag_save();

            $data = ['success'=>true,'message'=>'ok','tag'=>$altered_tag,'attribute'=>$call->attribute,'token'=> $call->new_token];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $this->logger->error("Could not delete_attribute: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $call->new_token?? null];
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
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'create_applied';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            $new_applied = new FlowAppliedTag($call->args);
            $new_applied->flow_tag_id = $call->tag->flow_tag_id;
            $new_applied->save();
            $applied_to_return = FlowAppliedTag::reconstitute($new_applied->id,$call->tag);

            $tag = $call->tag->clone_refresh();

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            foreach ( $tag->applied as $mapp) {
                $mapp->set_link_for_tagged($routeParser);
            }

            foreach ( $tag->attributes as $matt) {
                $matt->set_link_for_pointee($routeParser);
            }

            $call->project->do_tag_save();
            $data = [
                'success'=>true,'message'=>'ok','tag'=>$tag,'attribute'=>null,'applied'=>$applied_to_return,
                'token'=> $call->new_token
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $this->logger->error("Could not create_applied: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $call->new_token?? null];
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
        try {
            $option = new FlowTagCallData([FlowTagCallData::OPTION_IS_AJAX,FlowTagCallData::OPTION_MAKE_NEW_TOKEN,FlowTagCallData::OPTION_VALIDATE_TOKEN]);
            $option->note = 'delete_applied';
            $call = $this->validate_ajax_call($option,$request,null,$user_name,$project_name,$tag_name);
            $applied_that_was_given = new FlowAppliedTag($call->args);
            $applied_to_be_deleted = FlowAppliedTag::reconstitute($applied_that_was_given,$call->tag);
            $applied_to_be_deleted->delete_applied();

            $call->project->do_tag_save();

            $tag = $call->tag->clone_refresh();

            $routeParser = RouteContext::fromRequest($request)->getRouteParser();
            foreach ( $tag->applied as $mapp) {
                $mapp->set_link_for_tagged($routeParser);
            }

            foreach ( $tag->attributes as $matt) {
                $matt->set_link_for_pointee($routeParser);
            }

            $data = [
                'success'=>true,'message'=>'ok','tag'=>$tag,'attribute'=>null,'applied'=>$call->applied,
                'token'=> $call->new_token
            ];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);


        } catch (Exception $e) {
            $this->logger->error("Could not delete_applied: ".$e->getMessage(),['exception'=>$e]);
            $data = ['success'=>false,'message'=>$e->getMessage(),'data'=>null,'token'=> $call->new_token?? null];
            $payload = JsonHelper::toString($data);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    } //end function




} //end class