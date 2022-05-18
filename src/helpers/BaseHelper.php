<?php

namespace app\helpers;

use app\common\BaseConnection;
use app\controllers\entry\FlowEntryCallData;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\tag\FlowAppliedTag;
use Exception;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;

class BaseHelper extends BaseConnection {
    /**
     * Used in Ajax Calls
     * validates data, creates new form key too
     * @param AjaxCallData $options
     * @param ServerRequestInterface $request
     * @param string|null $route_name
     * @param string|null $user_name
     * @param string|null $project_name
     * @param ?string $tag_name
     * @param string|null $attribute_name
     * @param string|null $entry_name
     * @param FlowEntrySearchParams|null $entry_search_params
     * @param string|null $entry_arg_param_name
     * @param int|null $page
     * @return AjaxCallData
     * @throws Exception
     */
    public function validate_ajax_call(AjaxCallData           $options, ServerRequestInterface $request,
                                       ?string                $route_name = null,
                                       ?string                $user_name = null,
                                       ?string                $project_name = null ,
                                       ?string                $tag_name = null ,
                                       ?string                $attribute_name = null,
                                       ?string                $entry_name = null,
                                       ?FlowEntrySearchParams $entry_search_params = null,
                                       ?string                $entry_arg_param_name = null,
                                       ?int                   $page = null
    ) : AjaxCallData
    {

        $token = null;
        $args = $request->getParsedBody();
        if (empty($args)) {
            if (!$options->has_option(AjaxCallData::OPTION_ALLOW_EMPTY_BODY)) {
                throw new InvalidArgumentException("No data sent");
            }

        }

        $csrf = null;
        if ($options->has_option(AjaxCallData::OPTION_VALIDATE_TOKEN) ||
            $options->has_option(AjaxCallData::OPTION_MAKE_NEW_TOKEN)
        ) {
            if ($route_name) {
                $csrf = new FlowAntiCSRF($args);
            } else {
                $csrf = new FlowAntiCSRF($args,$_SESSION,FlowAntiCSRF::$fake_server);
            }
        }

        if ($csrf && $options->has_option(AjaxCallData::OPTION_VALIDATE_TOKEN) ){
            if (!$csrf->validateRequest()) {
                throw new HttpForbiddenException($request, "Bad Request. Refresh Page");
            }
        }

        if ($csrf && $options->has_option(AjaxCallData::OPTION_ENFORCE_AJAX) ){
            $x_header = $request->getHeader('X-Requested-With') ?? [];
            if (empty($x_header) || $x_header[0] !== 'XMLHttpRequest') {
                throw new InvalidArgumentException("Need the X-Requested-With header");
            }
        }


        $project = null;
        if ($project_name && $user_name) {
            $project_helper = ProjectHelper::get_project_helper();

            $project = $project_helper->get_project_with_permissions($request,$user_name, $project_name, $options->permission_mode);
            if (!$project) {
                throw new HttpNotFoundException($request,"Project $project_name Not Found");
            }
        }

        if(!$options->has_option(AjaxCallData::OPTION_ALLOW_NO_PROJECT_HASH)) {
            if (empty($args['flow_project_git_hash'])) {
                throw new InvalidArgumentException("Git hash flow_project_git_hash is missing, cannot work without it");
            }
            $old_git_hash = $args['flow_project_git_hash'];
            $new_git_hash = $project->get_head_commit_hash();
            if ( $new_git_hash !== $old_git_hash) {
                throw new InvalidArgumentException(
                    sprintf(
                        "Git hash %s is too old, project was saved since this page loaded with new hash of %s",
                                $old_git_hash,$new_git_hash
                    )
                );
            }
        }



        if ($csrf && $options->has_option(AjaxCallData::OPTION_MAKE_NEW_TOKEN) ) {
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



        $ret = new  AjaxCallData([],$args_as_object,$project,$token);

        if ($project && $tag_name) {
            $tags = $project->get_all_owned_tags_in_project($options->has_option(AjaxCallData::OPTION_GET_APPLIED));

            foreach ($tags as $look_tag) {
                if ($look_tag->getGuid() === $tag_name) { $ret->tag = $look_tag; break;}
                if ($look_tag->getName() === $tag_name) { $ret->tag = $look_tag; break;}
            }

            if (!$ret->tag) {
                throw new HttpNotFoundException($request,"Tag of '$tag_name' cannot be found" );
            }

            if ($attribute_name) {
                foreach ($ret->tag->getAttributes() as $look_at) { //clever name!!
                    if ($look_at->getGuid() === $attribute_name) { $ret->attribute = $look_at; break;}
                    if ($look_at->getName() === $attribute_name) { $ret->attribute = $look_at; break;}
                }

                if (!$ret->attribute) {
                    throw new HttpNotFoundException($request,"Attribute of '$attribute_name' cannot be found" );
                }
            }//end if attribute name
        } //end if tag name

        if ($args_as_object && property_exists($args_as_object,'applied') && $ret->tag) {
            $ret->applied = FlowAppliedTag::reconstitute($args_as_object->applied,$ret->tag);
        }

        $ret->entry_search_params_used = null;

        if ($entry_search_params) {
            $ret->entry_search_params_used = $entry_search_params;

        }  else if ($entry_name && empty($entry_search_params)) {
            $ret->entry_search_params_used = $ret->search_used?? new FlowEntrySearchParams();
            if (WillFunctions::is_valid_guid_format($entry_name)) {
                $ret->entry_search_params_used->entry_guids[] = $entry_name;
            } else {
                $ret->entry_search_params_used->entry_titles[] = $entry_name;
            }

            if ($project) {
                $ret->entry_search_params_used->owning_project_guid = $project->get_project_guid();
            }

        } else if ($entry_arg_param_name && property_exists($ret->args,$entry_arg_param_name)) {
            $ret->entry_search_params_used = new FlowEntrySearchParams($ret->args->$entry_arg_param_name);
        }

        if ($ret->entry_search_params_used) {
            if ($page) {
                $ret->entry_search_params_used->setPage($page) ;
            }

            if ($options->has_option(FlowEntryCallData::OPTION_LIMIT_SEARCH_TO_PROJECT) && $project) {
                $ret->entry_search_params_used->owning_project_guid = $project->get_project_guid();
            }

            $ret->entry_array = FlowEntrySearch::search($ret->entry_search_params_used);
            if ($entry_name && count($ret->entry_array)) {
                $ret->entry = $ret->entry_array[0];
            }
        }

        $ret->note = $options->note;
        return $ret;

    }
}