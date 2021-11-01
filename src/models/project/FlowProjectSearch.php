<?php
namespace app\models\project;

use app\models\base\FlowBase;
use Exception;
use InvalidArgumentException;
use LogicException;
use PDO;

class FlowProjectSearch extends FlowBase {

    /**
     * Gets multiple projects if given a list of project_guids or titles
     * Optionally limit these to having a user name or guid too
     * @param array $project_title_guid_or_id_list
     * @param ?string $user_name_guid_or_id
     * @return FlowProject[]
     * @throws Exception
     */
    public static function find_projects(array $project_title_guid_or_id_list, ?string $user_name_guid_or_id = null): array
    {
        $db = static::get_connection();

        $where_conditions = [];
        $args = [];

        foreach ($project_title_guid_or_id_list as $project_title_guid_or_id) {
            if (empty($project_title_guid_or_id)) { throw new InvalidArgumentException("Project name or guid supplied is empty");}
            if (trim($project_title_guid_or_id) && trim($user_name_guid_or_id)) {
                $where_conditions[] = " ( u.flow_user_name = ? OR u.flow_user_guid = UNHEX(?) ) AND ".
                    " (  p.flow_project_title = ? or p.flow_project_guid = UNHEX(?))";
                $args = [$user_name_guid_or_id,$user_name_guid_or_id,
                    $project_title_guid_or_id,$project_title_guid_or_id];
            } else if (trim($project_title_guid_or_id) ) {
                if (ctype_digit($project_title_guid_or_id) && (intval($project_title_guid_or_id) < (PHP_INT_MAX/2))) {
                    $where_conditions[] = " (p.id = ? )";
                } else {
                    $where_conditions[] = " (p.flow_project_guid = UNHEX(?) )";
                }
                $args = [$project_title_guid_or_id];

            } else{
                throw new LogicException("Need at least one project id/name/string");
            }
        }

        $where_condition = implode(' OR ',$where_conditions);



        $sql = "SELECT 
                p.id,
                p.created_at_ts,
                p.is_public,    
                HEX(p.flow_project_guid) as flow_project_guid,
                p.admin_flow_user_id,
                p.parent_flow_project_id,      
                p.flow_project_type,
                p.flow_project_title,
                p.flow_project_blurb,
                p.flow_project_readme,
                p.flow_project_readme_bb_code,
       
                p.export_repo_do_auto_push,
                p.export_repo_url,
                p.export_repo_branch,
                p.export_repo_key,
       
                p.import_repo_url,
                p.import_repo_branch,
                p.import_repo_key

                FROM flow_projects p 
                INNER JOIN  flow_users u ON u.id = p.admin_flow_user_id
                WHERE 1 AND ( $where_condition )";

        try {
            $rows = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
            if (empty($rows)) {
                return [];
            }
            $ret = [];
            foreach ($rows as $row) {
                $ret[] = new FlowProject($row);
            }
            return $ret;
        } catch (Exception $e) {
            static::get_logger()->alert("FlowProjectSearch::find_projects error ",['exception'=>$e]);
            throw $e;
        }
    }

}