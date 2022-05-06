<?php
namespace app\controllers\entry;

use app\helpers\AjaxCallData;
use app\models\entry\IFlowEntry;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

enum EntryRoutes: string
{
    case SHOW = 'show_entry';
    case UPDATE = 'update_entry';

    public static function get_entry_url(
        self $route_name,ServerRequestInterface $request,?AjaxCallData $call,?IFlowEntry $over_entry = null ) : ?string
    {
        if (!$call || !$call->project || !($over_entry || $call->entry)) {return null;}
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();

        $entry_to_use = $call->entry;
        if ($over_entry) {$entry_to_use = $over_entry;}


        return match ($route_name) {
            EntryRoutes::SHOW => $routeParser->urlFor('show_entry', [
                'user_name' => $call->project->get_admin_user()->flow_user_name,
                'project_name' => $call->project->get_project_title(),
                'entry_name' => $entry_to_use->get_guid()
            ]),
            EntryRoutes::UPDATE => $routeParser->urlFor('update_entry', [
                'user_name' => $call->project->get_admin_user()->flow_user_name,
                'project_name' => $call->project->get_project_title(),
                'entry_name' => $entry_to_use->get_guid()
            ])
        };


    }
}