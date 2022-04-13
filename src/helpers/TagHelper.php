<?php

namespace app\helpers;

use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\models\project\FlowProjectUser;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTagCallData;
use DI\DependencyException;
use DI\NotFoundException;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;

class TagHelper extends BaseHelper
{
    public static function get_tag_helper(): TagHelper
    {
        try {
            return static::get_container()->get('tagHelper');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }
    }

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
    public function validate_ajax_call(FlowTagCallData $options, ServerRequestInterface $request,
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
        $ret->note = $options->note;
        return $ret;

    }
}