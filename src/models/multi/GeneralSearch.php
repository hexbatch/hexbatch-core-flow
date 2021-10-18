<?php

namespace app\models\multi;


use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use InvalidArgumentException;
use PDO;

class GeneralSearch extends FlowBase{

    const DEFAULT_PAGE_SIZE = 20;

    /**
     * @param \app\models\multi\GeneralSearchParams $search
     * @param int $page
     * @param int $page_size
     * @return \app\models\multi\GeneralSearchResult[]
     */
    public static function general_search(GeneralSearchParams $search,
                                          int     $page = 1,
                                          int     $page_size =  self::DEFAULT_PAGE_SIZE): array {

        $args = [];
        $where_projects = [];
        $where_users = [];
        $where_entries = [];

        $build = function(string $alias, array &$where_array) use ($search,&$args) {
            switch ($alias) {
                case GeneralSearchResult::TYPE_PROJECT:
                {
                    if ($search->guid) {
                        $where_array[] = "project.flow_project_guid = UNHEX(?)";
                        $args[] = $search->guid;
                    }
                    if ($search->title) {
                        $where_array[] = "project.flow_project_title like ?";
                        $args[] = $search->title . '%';
                    }

                    if ($search->created_at_ts) {
                        $where_array[] = "project.created_at_ts >= ?";
                        $args[] = $search->created_at_ts;
                    }
                    break;

                }
                case GeneralSearchResult::TYPE_USER:
                {
                    if ($search->guid) {
                        $where_array[] = "user.flow_project_guid = UNHEX(?)";
                        $args[] = $search->guid;
                    }
                    if ($search->title) {
                        $where_array[] = "user.flow_user_name like ?";
                        $args[] = $search->title . '%';
                    }

                    if ($search->created_at_ts) {
                        $where_array[] = "user.created_at_ts >= ?";
                        $args[] = $search->created_at_ts;
                    }
                    break;

                }
                case GeneralSearchResult::TYPE_ENTRY:
                {
                    if ($search->guid) {
                        $where_array[] = "entry.flow_entry_guid = UNHEX(?)";
                        $args[] = $search->guid;
                    }
                    if ($search->title) {
                        $where_array[] = "entry.flow_user_name like ?";
                        $args[] = $search->title . '%';
                    }

                    if ($search->created_at_ts) {
                        $where_array[] = "entry.created_at_ts >= ?";
                        $args[] = $search->created_at_ts;
                    }
                    break;
                }
                default:
                {
                    throw new InvalidArgumentException("Unknown alias search type $search->type");
                }
            }
            WillFunctions::will_do_nothing($args);
        };

        if ($search->type) {
            switch ($search->type) {
                case GeneralSearchResult::TYPE_PROJECT: {
                    $build($search->type,$where_projects);
                    break;
                }
                case GeneralSearchResult::TYPE_USER: {
                    $build($search->type,$where_users);
                    break;
                }
                case GeneralSearchResult::TYPE_ENTRY:  {
                    $build($search->type,$where_entries);
                    break;
                }
            }
        } else {
            $build(GeneralSearchResult::TYPE_PROJECT,$where_projects);
            $build(GeneralSearchResult::TYPE_USER,$where_users);
            $build(GeneralSearchResult::TYPE_ENTRY,$where_entries);

        }


        $I = function($v) { return $v; };
        $start_place = ($page - 1) * $page_size;


        $where_project_conditions = 2;
        if (!empty($where_projects)) {
            $where_project_conditions = implode(" AND ",$where_projects);
        }
        $sql_projects = "SELECT 
                            HEX(project.flow_project_guid) as guid ,
                            project.id as id,
                            project.flow_project_title as title,
                            '{$I(GeneralSearchResult::TYPE_PROJECT)}' as type
                        FROM flow_projects project 
                        WHERE 1 AND $where_project_conditions
                        ";


        $where_user_conditions = 4;
        if (!empty($where_users)) {
            $where_user_conditions = implode(" AND ",$where_users);
        }
        $sql_users = "SELECT 
                            HEX(user.flow_user_guid) as guid ,
                            user.id as id,
                            user.flow_user_name as title,
                            '{$I(GeneralSearchResult::TYPE_PROJECT)}' as type
                        FROM flow_users user 
                        WHERE 1 AND $where_user_conditions
                        ";


        $where_entry_conditions = 8;
        if (!empty($where_entries)) {
            $where_entry_conditions = implode(" AND ",$where_entries);
        }
        $sql_entries = "SELECT 
                            HEX(entry.flow_entry_guid) as guid ,
                            entry.id as id,
                            entry.flow_entry_title as title,
                            '{$I(GeneralSearchResult::TYPE_PROJECT)}' as type
                        FROM flow_entries entry 
                        WHERE 1 AND $where_entry_conditions
                        ";

        //todo make lookup table to avoid these unions
        if ($search->type) {
            switch ($search->type) {
                case GeneralSearchResult::TYPE_PROJECT: {
                    $sql_final = $sql_projects;
                    break;
                }
                case GeneralSearchResult::TYPE_USER: {
                    $sql_final = $sql_users;
                    break;
                }
                case GeneralSearchResult::TYPE_ENTRY:  {
                    $sql_final = $sql_entries;
                    break;
                }
                default: {
                    throw new InvalidArgumentException("Uknown general search type '$search->type' deep in the code");
                }
            }
        } else {
            $sql_final = "
                ($sql_projects)
                UNION
                ($sql_users)
                UNION
                ($sql_entries)
                LIMIT $start_place, $page_size
            ";

        }

        $db = static::get_connection();
        $res = $db->safeQuery($sql_final, [], PDO::FETCH_OBJ);
        /**
         * @var \app\models\multi\GeneralSearchResult[] $ret
         */
        $ret = [];
        foreach ($res as $row) {
            $node = new GeneralSearchResult($row);
            $ret[] = $node;
        }

        return $ret;



    }
}