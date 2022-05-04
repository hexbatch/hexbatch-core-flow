<?php

namespace app\helpers;


use app\models\project\FlowProject;
use app\models\project\FlowProjectSearch;
use app\models\project\FlowProjectSearchParams;
use app\models\project\IFlowProject;
use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use DirectoryIterator;
use Exception;
use PDO;
use Psr\Http\Message\ServerRequestInterface;
use doesnot\Exist\at\all;

class AdminHelper extends BaseHelper {

    const ADMIN_USER_ID = 1;


    /**
     * @return bool
     * @throws Exception
     */
    public  function is_current_user_admin() : bool  {

        if (!$this->get_current_user()->flow_user_id) {return false;}

        $params = new FlowProjectSearchParams();
        $params->setFlowProjectSpecialFlag(IFlowProject::SPECIAL_FLAG_ADMIN);
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
        $params->setFlowProjectSpecialFlag(IFlowProject::SPECIAL_FLAG_ADMIN);
        $res = FlowProjectSearch::find_projects($params);

        if (empty($res)) {return null;}
        return $res[0]->get_project_guid();
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
            $args = [IFlowProject::SPECIAL_FLAG_ADMIN];
            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
            if (empty($res)) {
                $admin_project = new FlowProject();
                $admin_project->set_project_title('Admin');
                $admin_project->set_project_blurb('Super Admin Privileges');//
                $admin_project->set_public(false);
                $admin_project->set_project_type(IFlowProject::FLOW_PROJECT_TYPE_TOP);
                $admin_project->set_admin_user_id( static::ADMIN_USER_ID);
                $admin_project->set_read_me('SuperAdmin Stub');

                $admin_project->save();
                $id =  $admin_project->get_id();
                $sql = "UPDATE flow_projects p SET p.flow_project_special_flag = ? WHERE p.id = ? ";
                $args = [IFlowProject::SPECIAL_FLAG_ADMIN,$id];
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

        $ret = [static::get_current_user()];
        return $ret;
    }


}