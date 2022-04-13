<?php

namespace app\helpers;


use app\hexlet\WillFunctions;
use app\models\project\FlowProject;
use app\models\project\FlowProjectSearch;
use app\models\project\FlowProjectSearchParams;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use PDO;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

class AdminHelper extends BaseHelper {

    const ADMIN_USER_ID = 1;


    /**
     * @return bool
     * @throws Exception
     */
    public  function is_current_user_admin() : bool  {

        if (!$this->get_current_user()->flow_user_id) {return false;}

        $params = new FlowProjectSearchParams();
        $params->setFlowProjectSpecialFlag(FlowProject::SPECIAL_FLAG_ADMIN);
        $params->setPermissionUserNameOrGuidOrId($this->get_current_user()->flow_user_id);
        $params->setCanAdmin(true);
        $res = FlowProjectSearch::find_projects($params);

        if (empty($res)) {return false;}
        return true;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function get_admin_project_guid() : ?string {

        $params = new FlowProjectSearchParams();
        $params->setFlowProjectSpecialFlag(FlowProject::SPECIAL_FLAG_ADMIN);
        $res = FlowProjectSearch::find_projects($params);

        if (empty($res)) {return null;}
        return $res[0]->flow_project_guid;
    }


    /**
     * @param Container|null $container
     * @return AdminHelper
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function getInstance(?Container $container = null) : AdminHelper {
        if (!$container) {
            $container = static::get_container();
        }
        return $container->get('adminHelper');
    }

    public  function maybe_add_admin_project() : int {
        try {
            $db = $this->get_connection();
            $sql = "SELECT id FROM flow_projects WHERE flow_project_special_flag = ?";
            $args = [FlowProject::SPECIAL_FLAG_ADMIN];
            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
            if (empty($res)) {
                $admin_project = new FlowProject();
                $admin_project->flow_project_title = 'Admin';
                $admin_project->flow_project_blurb = 'Super Admin Privileges';//
                $admin_project->is_public = false;
                $admin_project->parent_flow_project_id = null;
                $admin_project->flow_project_type = FlowProject::FLOW_PROJECT_TYPE_TOP;
                $admin_project->admin_flow_user_id = static::ADMIN_USER_ID;
                $admin_project->set_read_me('SuperAdmin Stub');

                $admin_project->save();
                $id =  $admin_project->id;
                $sql = "UPDATE flow_projects p SET p.flow_project_special_flag = ? WHERE p.id = ? ";
                $args = [FlowProject::SPECIAL_FLAG_ADMIN,$id];
                $db->safeQuery($sql, $args, PDO::FETCH_OBJ,true);
                return $id;
            }
            return $res->id;
        } catch (Exception $e) {
            $this->get_logger()->warning("Cannot find or perhaps create admin project",['exception'=>$e]);
            return 0;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     * @throws Exception
     */
    public  function admin_test(ServerRequestInterface $request) : array  {
        WillFunctions::will_do_nothing($request);
        $project = ProjectHelper::get_project_helper()->find_one($this->get_admin_project_guid());
        $resource_files = $project->getFlowProjectFiles()->get_resource_file_paths();
        $routeParser = RouteContext::fromRequest($request)->getRouteParser();
        $base_project_url = $routeParser->urlFor('single_project_home',[
            "user_name" => $project->get_admin_user()->flow_user_guid,
            "project_name" => $project->flow_project_guid
        ]);
        $base_resource_file_path = $project->getFlowProjectFiles()->get_project_directory(); //no slash at end

        $resource_urls = [];
        foreach ($resource_files as $full_path_file) {
            $resource_urls[] = str_replace($base_resource_file_path,$base_project_url,$full_path_file);
        }
        return $resource_urls;
    }


}