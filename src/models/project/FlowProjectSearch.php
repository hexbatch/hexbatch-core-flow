<?php
namespace app\models\project;

use app\models\base\FlowBase;
use app\models\base\SearchParamBase;
use Exception;
use LogicException;
use PDO;

class FlowProjectSearch extends FlowBase {

    /**
     * Gets multiple projects if given a list of project_guids or titles
     * Optionally limit these to having a user name or guid too
     * Optionally limit to type, special flag and put page and limit
     * @param FlowProjectSearchParams $params
     * @return IFlowProject[]
     *
     * @throws Exception
     */
    public static function find_projects(FlowProjectSearchParams $params): array
    {
        $db = static::get_connection();

        $or_where_conditions = [];
        $and_where_conditions = [];
        $args = [];
        $used_inner_joins = [];
        $inner_joins = [];
        $inner_joins['perm'] = 'INNER JOIN flow_project_users perm on p.id = perm.flow_project_id'
                        . "\n" .
                        'INNER JOIN flow_users perm_user on perm_user.id = perm.flow_user_id';

        $project_owner_clause = '';
        if ($params->getOwnerUserNameOrGuidOrid()) {
            switch ($params->getTypeOwner()) {
                case SearchParamBase::ARG_IS_NAME : {
                    $project_owner_clause = " AND  u.flow_user_name = ? ";
                    break;
                }
                case SearchParamBase::ARG_IS_HEX : {
                    $project_owner_clause = " AND  u.flow_user_guid = UNHEX(?) ";
                    break;
                }
                case SearchParamBase::ARG_IS_EMAIL : {
                    $project_owner_clause = " AND  u.flow_user_email = ? ";
                    break;
                }
                case SearchParamBase::ARG_IS_INT : {
                    $project_owner_clause = " AND  u.id = ? ";
                    break;
                }
                default: {
                    throw new LogicException("Cannot search for owning user in Project Search, invalid type");
                }
            }
        }


        foreach ($params->getProjectTitleGuidOrIdList() as $project_title_guid_or_id) {
            $type_project = FlowProjectSearchParams::find_type_of_arg($project_title_guid_or_id);
            switch ($type_project) {
                case SearchParamBase::ARG_IS_NAME : {
                    $or_where_conditions[] = " ( p.flow_project_title = ?  $project_owner_clause ) ";
                    break;
                }
                case SearchParamBase::ARG_IS_HEX : {
                    $or_where_conditions[] = " ( p.flow_project_guid = UNHEX(?) $project_owner_clause )";
                    break;
                }
                case SearchParamBase::ARG_IS_INT : {
                    $or_where_conditions[] = " ( p.id = ? $project_owner_clause ) ";
                    break;
                }
                default: {
                    throw new LogicException("Cannot search for project in Project Search, invalid type: ". $type_project );
                }
            }
            $args[] = $project_title_guid_or_id;
            if ($project_owner_clause) {
                $args[] = $params->getOwnerUserNameOrGuidOrid();
            }

        }

        $where_condition_or = implode(' OR ',$or_where_conditions);



        if ($params->getFlowProjectType()) {
            $and_where_conditions[] = " (p.flow_project_type = ? $project_owner_clause)";
            $args[] = $params->getFlowProjectType();
            if ($project_owner_clause) {
                $args[] = $params->getOwnerUserNameOrGuidOrid();
            }
        }

        if ($params->getFlowProjectSpecialFlag()) {
            $and_where_conditions[] = " (p.flow_project_special_flag = ? $project_owner_clause )";
            $args[] = $params->getFlowProjectSpecialFlag();
            if ($project_owner_clause) {
                $args[] = $params->getOwnerUserNameOrGuidOrid();
            }
        }



        $permission_sql_generator = function(string $check_column, bool $has_permission)
                                                use ($params,$inner_joins,&$args,&$and_where_conditions,&$used_inner_joins) {

            if (!in_array($check_column,FlowProjectUser::PERMISSION_COLUMNS)) {
                throw new LogicException("Wrong column here ".$check_column);
            }
            $used_inner_joins['perm'] = $inner_joins['perm'];


            if (!$params->getPermissionUserNameOrGuidOrId()) {
                if ($check_column === FlowProjectUser::PERMISSION_COLUMN_READ ) {
                    //special case for is public and no user to check
                    $and_where_conditions[] = " p.is_public = 1 ";
                    return;
                } else {
                    throw new LogicException("Cannot search for write or admin permissions if no permission user set in search") ; //its going to be nothing found
                }
            }


            $check_operation = ' <= ';
            if ($has_permission) {
                $check_operation = ' >  ';
            }

            $public_ok = '';
            if ($check_column === FlowProjectUser::PERMISSION_COLUMN_READ) {
                $public_ok = " OR p.is_public = 1 ";
            }

            if (empty($params->getPermissionUserNameOrGuidOrId())) {
                throw new LogicException("Need a permssion user set when checking read,write,admin");
            }
            switch ($params->getTypePermissionUser()) {
                case SearchParamBase::ARG_IS_NAME : {
                    $and_where_conditions[] = " ( (perm.$check_column $check_operation 0  $public_ok) AND perm_user.flow_user_name = ? )";
                    break;
                }
                case SearchParamBase::ARG_IS_HEX : {
                    $and_where_conditions[] = " ( (perm.$check_column $check_operation 0 $public_ok) AND perm_user.flow_user_guid = UNHEX(?) )";
                    break;
                }
                case SearchParamBase::ARG_IS_EMAIL : {
                    $and_where_conditions[] = " ( ( perm.$check_column $check_operation 0 $public_ok)  AND perm_user.flow_user_email = ? )";
                    break;
                }
                case SearchParamBase::ARG_IS_INT : {
                    $and_where_conditions[] = " ( ( perm.$check_column $check_operation 0 $public_ok) AND perm.flow_user_id = ?) ";
                    break;
                }
                default: {
                    throw new LogicException("Cannot search for permission user $check_column in Project Search, invalid user type");
                }
            }

            $args[] =  $params->getPermissionUserNameOrGuidOrId();
        };


        if (! is_null($params->getCanAdmin())) {
            $permission_sql_generator('can_admin',$params->getCanAdmin());
        }

        if (! is_null($params->getCanWrite())) {
            $permission_sql_generator('can_write',$params->getCanWrite());
        }

        if (! is_null($params->getCanRead())) {
            $permission_sql_generator('can_read',$params->getCanRead());
        }



        $where_condition_and = implode(' AND ',$and_where_conditions);


        if ($where_condition_and && $where_condition_or) {
            $where_condition = " ( $where_condition_or ) AND $where_condition_and ";
        } elseif ($where_condition_and) {
            $where_condition = $where_condition_and;
        } elseif ($where_condition_or) {
            $where_condition = $where_condition_or;
        }
        else {
            $where_condition = "1=1";
        }




        $start_place = ($params->getPage() - 1) * $params->getPageSize();
        $page_size = $params->getPageSize();

        $inner_joins_combined = implode("\n",$used_inner_joins);

        $sql = "SELECT 
                DISTINCT 
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
                p.flow_project_special_flag,
                                 
                u.id as owning_user_id                 

                FROM flow_projects p 
                INNER JOIN  flow_users u ON u.id = p.admin_flow_user_id
                
                $inner_joins_combined
                
                WHERE 1 AND ( $where_condition )
                ORDER BY p.flow_project_special_flag DESC ,owning_user_id,p.id 
                LIMIT $start_place , $page_size
                ";

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