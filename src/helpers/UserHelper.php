<?php

namespace app\helpers;

use app\models\project\FlowProject;
use app\models\project\FlowProjectSearch;
use app\models\project\FlowProjectSearchParams;
use app\models\project\IFlowProject;
use app\models\standard\IFlowTagStandardAttribute;
use app\models\user\FlowUser;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use InvalidArgumentException;
use LogicException;

class UserHelper extends BaseHelper {

    const USER_HOME_TITLE = 'User-Home';

    public static function get_user_helper() : Userhelper {
        try {
            return static::get_container()->get('userHelper');
        } catch (DependencyException|NotFoundException $e) {
            throw new LogicException($e->getMessage());
        }

    }


    /**
     * @param string|null $user_guid
     * @return IFlowProject|null
     * @throws Exception
     */
   public function get_user_home_project(?string $user_guid = null) : ?IFlowProject {

        if (!$user_guid) {
            $user_guid = $this->user->flow_user_guid;
        }

       if (!$user_guid) {throw new InvalidArgumentException("If not logged in, must provide a user guid to get the user home");}

       try {
            $params = new FlowProjectSearchParams();
            $params->setFlowProjectType(IFlowProject::FLOW_PROJECT_TYPE_USER_HOME);
            $params->setOwnerUserNameOrGuidOrId($user_guid);
            $res = FlowProjectSearch::find_projects($params);

            if (count($res)) {
                $user_home_project =  $res[0];
            }
            else {
                $target_user = FlowUser::find_one($user_guid);
                $user_home_project = new FlowProject();
                $user_home_project->set_project_title(static::USER_HOME_TITLE);
                $user_home_project->set_public(false);
                $user_home_project->set_project_type(IFlowProject::FLOW_PROJECT_TYPE_USER_HOME);
                $user_home_project->set_admin_user_id($target_user->flow_user_id);
                $user_home_project->save();
            }

            return $user_home_project;


       } catch (Exception $e) {
           $this->get_logger()->warning("Cannot find or perhaps create user home project");
           throw $e;
       }
   }

    /**
     * @param string $flow_user_guid
     * @return IFlowTagStandardAttribute[]
     * @throws Exception
     */
   public function get_user_meta_tags(string $flow_user_guid) : array  {
       return $this->get_user_tags_of_standard($flow_user_guid,IFlowTagStandardAttribute::STD_ATTR_NAME_META);
   }

    /**
     * @param string $flow_user_guid
     * @param string $standard_name
     * @return IFlowTagStandardAttribute[]
     * @throws Exception
     */
    public function get_user_tags_of_standard(string $flow_user_guid,string $standard_name) : array  {
        $user_home = $this->get_user_helper()->get_user_home_project($flow_user_guid);
        $dapper_tags = [];
        $tags = $user_home->get_all_owned_tags_in_project();
        foreach ($tags as $tag) {
            $sa = $tag->getStandardAttributes();
            foreach ($sa as $standard) {
                if ( $standard->getStandardName() === $standard_name) {
                    $dapper_tags[] = $tag;
                }
            }
        }
        return $dapper_tags;
    }
}