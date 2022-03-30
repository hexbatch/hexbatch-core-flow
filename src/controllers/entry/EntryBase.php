<?php
namespace app\controllers\entry;

use app\controllers\base\BasePages;
use app\helpers\ProjectHelper;
use app\hexlet\FlowAntiCSRF;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\project\FlowProjectUser;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;


class EntryBase extends BasePages
{


    /**
     * Used in Ajax Calls
     * validates data, creates new form key too
     * @param FlowEntryCallData $options
     * @param ServerRequestInterface $request
     * @param string|null $route_name
     * @param string $user_name
     * @param string $project_name
     * @param ?string $entry_name
     * @param string $project_permissions_needed
     * @param ?FlowEntrySearchParams $search
     * @param ?int $page
     * @return FlowEntryCallData
     * @throws
     */
    protected function validate_call(FlowEntryCallData $options, ServerRequestInterface $request,
                                          ?string $route_name, string $user_name,
                                          string $project_name, ?string $entry_name = null,
                                          string $project_permissions_needed  = FlowProjectUser::PERMISSION_COLUMN_WRITE,
                                          ?FlowEntrySearchParams $search = null,
                                          ?int $page = null
                                        ) : FlowEntryCallData
    {

        $token = null;
        $args = $request->getParsedBody();
        if (empty($args)) {
            $args = [];
        }

        $csrf = null;
        if ($options->has_option(FlowEntryCallData::OPTION_VALIDATE_TOKEN) ||
            $options->has_option(FlowEntryCallData::OPTION_MAKE_NEW_TOKEN)
        ) {
            if ($route_name) {
                $csrf = new FlowAntiCSRF($args);
            } else {
                $csrf = new FlowAntiCSRF($args,$_SESSION,FlowAntiCSRF::$fake_server);
            }
        }

        if ($csrf && $options->has_option(FlowEntryCallData::OPTION_VALIDATE_TOKEN) ){
            if (!$csrf->validateRequest()) {
                throw new HttpForbiddenException($request, "Bad Request for entry. Refresh Page");
            }
        }

        $is_ajax_call = $this->is_ajax_call($request);
        if ($csrf && $options->has_option(FlowEntryCallData::OPTION_ENFORCE_AJAX) ){
            if (!$is_ajax_call) {
                throw new InvalidArgumentException("Need the X-Requested-With header");
            }
        }



        $project_helper = ProjectHelper::get_project_helper();

        $project = $project_helper->get_project_with_permissions($request,$user_name, $project_name, $project_permissions_needed);
        if (!$project) {
            throw new HttpNotFoundException($request,"Project $project_name Not Found");
        }

        
        if ($csrf && $options->has_option(FlowEntryCallData::OPTION_MAKE_NEW_TOKEN) ) {
            $token_lock_to = '';

            if ($route_name) {
                $routeParser = RouteContext::fromRequest($request)->getRouteParser();

                $lock_to_data = [
                    'user_name' => $user_name,
                    'project_name' => $project_name,
                    'entry_name' => $entry_name
                ];

                $token_lock_to = $routeParser->urlFor($route_name, $lock_to_data);
            }

            $token = $csrf->getTokenArray($token_lock_to);
        }

        $args_as_object = JsonHelper::fromString(JsonHelper::toString($args),true,false);


        $ret = new  FlowEntryCallData([],$args_as_object,$project,$token);

        $ret->is_ajax_call = $is_ajax_call;

       
        $ret->search_used = null;

        if ($search) {
            $ret->search_used = $search;
            if ($options->has_option(FlowEntryCallData::OPTION_LIMIT_SEARCH_TO_PROJECT)) {
                $ret->search_used->owning_project_guid = $project->flow_project_guid;
            }
        } else if (property_exists($ret->args,'search')) {
            $ret->search_used = new FlowEntrySearchParams($ret->args->search);
        } else if ($entry_name && empty($search)) {
            $ret->search_used = $ret->search_used?? new FlowEntrySearchParams();
            if (WillFunctions::is_valid_guid_format($entry_name)) {
                $ret->search_used->entry_guids[] = $entry_name;
            } else {
                $ret->search_used->entry_titles[] = $entry_name;
            }

            $ret->search_used->owning_project_guid = $project->flow_project_guid;
        }

        if ($ret->search_used) {
            if ($page) {
                $ret->search_used->setPage($page) ;
            }
            $ret->entry_array = FlowEntrySearch::search($ret->search_used);
        }



       return $ret;

    }





} //end class