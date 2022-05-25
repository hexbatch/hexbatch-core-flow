<?php

namespace app\models\entry;

use app\models\base\FlowBase;
use app\models\entry\public_json\FlowEntryJson;
use app\models\project\FlowProjectSearch;
use app\models\project\FlowProjectSearchParams;
use app\models\project\IFlowProject;
use Exception;
use LogicException;
use PDO;

class FlowEntrySearch extends FlowBase {



    /**
     * @param ?FlowEntrySearchParams $params
     * @return IFlowEntry[]
     * @throws
     */
   public static function search(?FlowEntrySearchParams $params): array {
       if (empty($params)) {return [];}


       $start_place = ($params->getPage() - 1) * $params->getPageSize();
       $page_size = $params->getPageSize();

       $db = static::get_connection();
       $args = [];
       $where_project = 2;
       $where_user = 4;
       $where_entry_guid = 8;
       $where_node_guid = 16;

       $used_inner_joins = [];
       $inner_joins = [];
       $inner_joins['child_nodes'] = "INNER JOIN flow_entry_nodes driver_node on ".
           "driver_node.flow_entry_id = driver_entry.id";

       if ($params->owning_project_guid) {
           $where_project = "driver_project.flow_project_guid = UNHEX(?)";
           $args[] = $params->owning_project_guid;
       }

       if ($params->owning_user_guid) {
           $where_user = "driver_user.flow_user_guid = UNHEX(?)";
           $args[] = $params->owning_project_guid;
       }


       if (count($params->entry_guids)) {
           $in_question_array = [];
           foreach ($params->entry_guids as $a_guid) {
               if ( ctype_xdigit($a_guid) ) {
                   $args[] = $a_guid;
                   $in_question_array[] = "UNHEX(?)";
               }
           }
           if (count($in_question_array)) {
               $comma_delimited_unhex_question = implode(",",$in_question_array);
               $where_entry_guid = "driver_entry.flow_entry_guid in ($comma_delimited_unhex_question)";
           }
       }

       if (count($params->child_node_guids)) {
           $in_question_array = [];
           foreach ($params->child_node_guids as $a_guid) {
               if ( ctype_xdigit($a_guid) ) {
                   $args[] = $a_guid;
                   $in_question_array[] = "UNHEX(?)";
               }
           }
           if (count($in_question_array)) {
               $comma_delimited_unhex_question = implode(",",$in_question_array);
               $where_node_guid = "driver_node.entry_node_guid in ($comma_delimited_unhex_question)";
               $used_inner_joins['child_nodes'] = $inner_joins['child_nodes'];
           }
       }

       if (count($params->entry_titles)) {
           $in_question_array = [];
           foreach ($params->entry_titles as $a_name) {
               $args[] = $a_name;
               $in_question_array[] = "?";
           }
           if (count($in_question_array)) {
               $comma_delimited_unhex_question = implode(",",$in_question_array);
               $where_entry_guid = "driver_entry.flow_entry_title in ($comma_delimited_unhex_question)";
           }
       }

       $inner_joins_combined = implode("\n",$used_inner_joins);

       $sql = /** @lang MySQL */
           "
            SELECT  
                entry.id                                as flow_entry_id, 
                entry.flow_project_id                   as flow_project_id, 
                entry.flow_entry_parent_id              as flow_entry_parent_id, 
                entry.created_at_ts                     as entry_created_at_ts, 
                UNIX_TIMESTAMP(entry.updated_at)        as entry_updated_at_ts,
                HEX(entry.flow_entry_guid)              as flow_entry_guid,
                entry.flow_entry_title                  as flow_entry_title,
                entry.flow_entry_blurb                  as flow_entry_blurb,
                entry.flow_entry_body_bb_code           as flow_entry_body_bb_code,
                HEX(parent_entry.flow_entry_guid)       as flow_entry_parent_guid ,
                HEX(project.flow_project_guid)          as flow_project_guid,
                HEX(admin_user.flow_user_guid)          as flow_user_guid,
                driver.is_primary                       as is_primary,   
                driver.parent_list                      as flow_entry_parent_debug_id_list ,
                null                                    as flow_entry_ancestor_guid_list   
            FROM flow_entries entry
            INNER JOIN  (
                    
                
                WITH RECURSIVE cte AS (
                    (
                        SELECT driver_entry.id                      as entry_id , 
                               driver_entry.flow_entry_parent_id    as parent_id,
                               1                                    as is_primary      
                               
                        FROM flow_entries driver_entry
                        INNER JOIN flow_projects driver_project ON driver_project.id = driver_entry.flow_project_id
                        INNER JOIN flow_users driver_user ON driver_project.admin_flow_user_id = driver_user.id
                        $inner_joins_combined
                        WHERE 1 
                            AND $where_project  
                            AND $where_user  
                            AND $where_entry_guid
                            AND $where_node_guid
                            ORDER BY driver_entry.id 
                            LIMIT $start_place , $page_size
                    )
                    UNION
                    DISTINCT
                    (
                        SELECT 
                                child_entry.id                       as entry_id,
                                child_entry.flow_entry_parent_id     as parent_id,
                                0                                    as is_primary
                               
                        FROM cte c
                        INNER JOIN flow_entries child_entry ON child_entry.flow_entry_parent_id = c.parent_id
                    )
                )
                SELECT cte.entry_id, group_concat(cte.parent_id) as parent_list,SUM(is_primary) as is_primary
                FROM cte
                GROUP BY cte.entry_id
                
                
            )  as driver ON driver.entry_id = entry.id 
            
            INNER JOIN flow_projects project on entry.flow_project_id = project.id   
            INNER JOIN flow_users admin_user ON admin_user.id = project.admin_flow_user_id    
            LEFT JOIN flow_entries parent_entry ON entry.flow_entry_parent_id = parent_entry.id
       ";



       /**
        * @var array<string,IFlowProject|null> $projects
        */
       $projects = [];


       /**
        * @var IFlowEntry $unsorted_ret
        */
       $unsorted_ret = [];



       try {

           $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

           //find all the project guids first
           foreach ($res as $row) {
                $projects[$row->flow_project_guid] = null;
           }

           //get the projects
           $project_guids = array_keys($projects);
           if (count($project_guids)) {
               $params = new FlowProjectSearchParams();
               $params->addProjectTitleGuidOrId($project_guids);
               $projects_found = FlowProjectSearch::find_projects($params);
               foreach ($projects_found as $found_project) {
                   if (!array_key_exists($found_project->get_project_guid(), $projects)) {
                       throw new LogicException("Could not find the project after a select");
                   }
                   $projects[$found_project->get_project_guid()] = $found_project;
               }
           }


           foreach ($res as $row) {
               $using_project = $projects[$row->flow_project_guid]??null;
               if (!$using_project) {throw new LogicException("could not find the project when creating entries");}
               $node = FlowEntry::create_entry($using_project,$row);
               if (intval($row->is_primary)) {$unsorted_ret[] = $node;}
           }



           $ret = FlowEntryJson::sort_array_by_parent($unsorted_ret);
       } catch (Exception $e) {
           static::get_logger()->alert("FlowEntrySearch cannot get entries ",['exception'=>$e]);
           throw $e;
       }

       return $ret;
   }
}