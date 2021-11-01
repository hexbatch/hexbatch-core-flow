<?php

namespace app\models\entry;

use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\entry\public_json\FlowEntryJson;
use app\models\project\FlowProject;
use app\models\project\FlowProjectSearch;
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
       /*
        $this->owning_project_guid = null;
        $this->owning_user_guid = null;
        $this->full_text_term = null;
        $this->parent_entry_guid = null;
        $this->host_entry_guid = null;
        $this->entry_guids = [];
        $this->entry_titles = [];
        $this->entry_ids = [];
        $this->flag_full_text_natural_languages = false;
        $this->flag_top_entries_only = false;



        public ?string $flow_entry_body_html;



       public array $child_entries;


       public array $child_guids;


       public array $child_entry_ids;

       protected ?string $child_id_list_as_string;
        */

       $start_place = ($params->get_page() - 1) * $params->get_page_size();
       $page_size = $params->get_page_size();

       $db = static::get_connection();
       $args = [];
       $where_project = 2;
       $where_user = 4;
       $where_entry_guid = 8;

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

       //todo search sql: fill in the values for flow_entry_ancestor_guid_list
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
                entry.flow_entry_body_text              as flow_entry_body_text ,
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
                        
                        WHERE 1 
                            AND $where_project  
                            AND $where_user  
                            AND $where_entry_guid
                        
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
        * @var array<string,FlowProject|null> $projects
        */
       $projects = [];

       /**
        * @var array<string,IFlowEntry> $all
        */
       $all = [];


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
               $projects_found = FlowProjectSearch::find_projects($project_guids);
               foreach ($projects_found as $found_project) {
                   if (!array_key_exists($found_project->flow_project_guid, $projects)) {
                       throw new LogicException("Could not find the project after a select");
                   }
                   $projects[$found_project->flow_project_guid] = $found_project;
               }
           }


           foreach ($res as $row) {
               $using_project = $projects[$row->flow_project_guid]??null;
               if (!$using_project) {throw new LogicException("could not find the project when creating entries");}
               $node = FlowEntry::create_entry($using_project,$row);
               $all[$node->get_guid()] = $node;
               if (intval($row->is_primary)) {$unsorted_ret[] = $node;}
           }



           //build children list
           foreach ($all as $found_guid => $found_entry) {
               WillFunctions::will_do_nothing($found_guid);
               if (!$found_entry->get_parent_guid()) { continue; }

                if (!array_key_exists($found_entry->get_parent_guid(),$all)) {
                    throw new LogicException("FlowEntrySearch: Could not find parent in all array ");
                }
                $parent_entry = $all[$found_entry->get_parent_guid()];
                $parent_entry->add_child($found_entry);
           }

           $ret = FlowEntryJson::sort_array_by_parent($unsorted_ret);
       } catch (Exception $e) {
           static::get_logger()->alert("FlowEntrySearch cannot get entries ",['exception'=>$e]);
           throw $e;
       }

       return $ret;
   }
}