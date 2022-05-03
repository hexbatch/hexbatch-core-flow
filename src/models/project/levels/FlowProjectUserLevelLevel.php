<?php
namespace app\models\project\levels;

use app\models\project\FlowProjectUser;
use app\models\user\FlowUser;
use Exception;
use InvalidArgumentException;

abstract class FlowProjectUserLevelLevel extends FlowProjectDataLevel {

    protected ?FlowUser $admin_user ;
    protected ?FlowProjectUser $current_user_permissions;

    /**
     * @param null|array|object $object
     * @throws Exception
     */
    public function __construct($object=null)
    {
        parent::__construct($object);
        $this->admin_user = null;
        $this->current_user_permissions = null;
    }

    public function set_admin_user_id(?int $user_id) : void {
        if ($this->admin_user) {
             unset($this->admin_user); $this->admin_user = null;
        }
        parent::set_admin_user_id($user_id);
    }


    /**
     * @return array
     * @throws Exception
     */
    public function jsonSerialize() : array
    {
        $ret = parent::jsonSerialize();
        $ret['admin_user'] = $this->get_admin_user();
        return $ret;
    }

    /**
     * @return FlowUser|null
     * @throws Exception
     */
    public function get_admin_user(): ?FlowUser
    {
        if ($this->admin_user) {return $this->admin_user;}
        if ($this->admin_flow_user_id) {
            $this->admin_user =  FlowUser::find_one($this->admin_flow_user_id);
        }
        return $this->admin_user;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function get_owner_user_guid() : ?string {
        $admin_user = $this->get_admin_user();
        if (!$admin_user) {return null;}
        return $this->get_admin_user()->flow_user_guid;
    }

    /**
     * @return FlowUser[]
     * @throws Exception
     */
    public function get_flow_project_users() : array {

        $page = 1;
        $ret = [];
        do {
            $info = FlowUser::find_users_by_project(true,$this->flow_project_guid,null,null,null ,$page);
            $page++;
            $ret = array_merge($ret,$info);
        } while(count($info));
        return $ret;
    }

    public function set_current_user_permissions(?FlowProjectUser $v) {
        $this->current_user_permissions = $v;
    }

    /**
     * @return FlowProjectUser|null
     * @throws Exception
     */
    public function get_current_user_permissions(): ?FlowProjectUser
    {
        if (!isset($this->current_user_permissions)) {
            $user_permissions = FlowUser::find_users_by_project(true,
                $this->flow_project_guid, null, true, $this->get_admin_user()->flow_user_guid);

            if (empty($user_permissions)) {
                throw new InvalidArgumentException("No permissions set for this");
            }
            $permissions_array = $user_permissions[0]->get_permissions();
            if (empty($permissions_array)) {
                throw new InvalidArgumentException("No permissions found, although in project");
            }
            $project_user = $permissions_array[0];

            $this->set_current_user_permissions($project_user);
        } //return no permissions
        return $this->current_user_permissions;
    }

    public function destroy_project(bool $b_do_transaction = true) : void {
        parent::destroy_project($b_do_transaction);
        $this->admin_user = null;
    }


}