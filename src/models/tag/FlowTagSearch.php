<?php

namespace app\models\tag;

use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\base\SearchParamBase;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\standard\FlowTagStandardAttribute;
use BlueM\Tree;
use Exception;
use InvalidArgumentException;
use LogicException;
use PDO;

class FlowTagSearch  extends FlowBase{

    /**
     * @var FlowTag[] $tags_found
     */
    protected array $tags_found = [];
    protected ?FlowTagSearchParams $last_search = null;

    /**
     * @return FlowTag[]
     */
    public function get_found_tags() : array { return $this->tags_found;}

    /**
     * get only the tags that have guid or name asked for
     * @return FlowTag[]
     */
    public function get_direct_match_tags() : array {
        $ret = [];
        if (!$this->last_search) {return [];}

        foreach ($this->tags_found as $tag) {
            if (in_array($tag->flow_tag_guid,$this->last_search->get_guids())) {
                $ret[] = $tag;
            }
            elseif (in_array($tag->flow_tag_name,$this->last_search->get_names())) {
                $ret[] = $tag;
            }
            elseif (in_array($tag->flow_tag_id,$this->last_search->get_ids())) {
                $ret[] = $tag;
            }

            elseif ($this->last_search->tag_name_term) {
                if (false !== stripos($tag->flow_tag_name, $this->last_search->tag_name_term) ) {
                    $ret[] = $tag;
                }
            }
        }
        return $ret;
    }
    /**
     * @param FlowTagSearchParams $search
     * @return FlowTagSearch
     * @throws Exception
     */
    public  function get_tags(FlowTagSearchParams $search): FlowTagSearch
    {
        $this->last_search = $search;
        $args = [];
        $where_project = 2;
        $where_name = 4;
        $where_tag_guid = 8;
        $where_tag_id = 16;
        $where_only_applied_to = 32;
        $where_not_applied_to = 64;


        if ($search->getOwningProjectGuid()) {
            $where_project = "driver_project.flow_project_guid = UNHEX(?)";
            $args[] = $search->getOwningProjectGuid();
        }

        if ($search->tag_name_term) {
            $where_name = "driver_tag.flow_tag_name  LIKE ?";
            $args[] = '%'.$search->tag_name_term.'%';
        }

        if (count($search->get_guids())) {
            $in_question_array = [];
            foreach ($search->get_guids() as $a_guid) {
                if ( ctype_xdigit($a_guid) ) {
                    $args[] = $a_guid;
                    $in_question_array[] = "UNHEX(?)";
                }
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_tag_guid = "driver_tag.flow_tag_guid in ($comma_delimited_unhex_question)";
            }
        }

        if (count($search->get_names())) {
            $in_question_array = [];
            foreach ($search->get_names() as $a_name) {
                $args[] = $a_name;
                $in_question_array[] = "?";
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_tag_guid = "driver_tag.flow_tag_name in ($comma_delimited_unhex_question)";
            }
        }



        if (count($search->tag_ids)) {
            $cast_ids = $search->tag_ids;
            array_walk($cast_ids, function(&$x)  use(&$args) {$args[] = intval($x);$x = "?";});
            $comma_delimited_ids = implode(",",$cast_ids);
            $where_tag_id = "driver_tag.id in ($comma_delimited_ids)";
        }

        $maybe_join_inner_driver_applied = '';
        if (count($search->only_applied_to_guids)) {
            $general_search = new GeneralSearchParams();
            $general_search->guids = $search->only_applied_to_guids;
            $search->setPage(1);
            $search->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
            $gmatches = GeneralSearch::general_search($general_search);
            $applied_project_ids=[];
            $applied_user_ids = [];
            $applied_entry_ids = [];
            GeneralSearch::sort_ids_into_arrays($gmatches,$applied_project_ids,$applied_user_ids,$applied_entry_ids);
            $where_part_guid_match = [];
            if (count($applied_project_ids)) {
                $comma_list_applied_projects = implode(',',$applied_project_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_project_id in ($comma_list_applied_projects)";
            }
            if (count($applied_user_ids)) {
                $comma_list_applied_users = implode(',',$applied_user_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_user_id in ($comma_list_applied_users)";
            }
            if (count($applied_entry_ids)) {
                $comma_list_applied_projects = implode(',',$applied_entry_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_entry_id in ($comma_list_applied_projects)";
            }

            if (count($search->only_applied_to_guids) !== (count($applied_project_ids) + count($applied_user_ids) + count($applied_entry_ids))) {
                throw new InvalidArgumentException("One or more invalid guids in the only_applied_to_guids");
            }

            $where_not_applied_to = '(' . implode(' OR ',$where_part_guid_match) . ')';
            $maybe_join_inner_driver_applied = "INNER JOIN flow_applied_tags driver_applied on driver_tag.id = driver_applied.flow_tag_id";

        }


        $maybe_left_join_inner_driver_applied = '';
        if (count($search->not_applied_to_guids)) {
            $general_search = new GeneralSearchParams();
            $general_search->guids = $search->not_applied_to_guids;
            $search->setPage(1);
            $search->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
            $gmatches = GeneralSearch::general_search($general_search);
            $applied_project_ids=[];
            $applied_user_ids = [];
            $applied_entry_ids = [];
            GeneralSearch::sort_ids_into_arrays($gmatches,$applied_project_ids,$applied_user_ids,$applied_entry_ids);
            $where_part_guid_match = [];
            if (count($applied_project_ids)) {
                $comma_list_applied_projects = implode(',',$applied_project_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_project_id NOT IN ($comma_list_applied_projects)";
            }
            if (count($applied_user_ids)) {
                $comma_list_applied_users = implode(',',$applied_user_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_user_id NOT IN ($comma_list_applied_users)";
            }
            if (count($applied_entry_ids)) {
                $comma_list_applied_projects = implode(',',$applied_entry_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_entry_id NOT IN ($comma_list_applied_projects)";
            }

            if (count($search->not_applied_to_guids) !== (count($applied_project_ids) + count($applied_user_ids) + count($applied_entry_ids))) {
                throw new InvalidArgumentException("One or more invalid guids in the not_applied_to_guids");
            }
            $where_only_applied_to = '( driver_applied.tagged_flow_project_id IS NULL OR ' . implode(' OR ',$where_part_guid_match) . ')';
            $maybe_left_join_inner_driver_applied = "LEFT JOIN flow_applied_tags driver_applied on driver_tag.id = driver_applied.flow_tag_id";

        }

        $page_size = $search->getPageSize();
        $start_place = ($search->getPage() - 1) * $page_size;


        $db = static::get_connection();

        $sql = "SELECT 
                    t.id                                    as flow_tag_id,
                    t.flow_project_id,
                    t.parent_tag_id,
                    driver.children_list                    as children_list_as_string,                  
                    t.created_at_ts                         as tag_created_at_ts,
                    UNIX_TIMESTAMP(t.updated_at)            as tag_updated_at_ts,
                    t.flow_tag_name,
                    HEX(t.flow_tag_guid)                    as flow_tag_guid,
                    HEX(parent_t.flow_tag_guid)             as parent_tag_guid,
                    HEX(project.flow_project_guid)          as flow_project_guid,
                    HEX(admin_user.flow_user_guid)          as flow_project_admin_user_guid,
       
                    attribute.id                            as flow_tag_attribute_id,
                    attribute.points_to_entry_id,
                    attribute.points_to_user_id,
                    attribute.points_to_project_id,
                    attribute.points_to_tag_id,
                    attribute.created_at_ts                 as attribute_created_at_ts,
                    UNIX_TIMESTAMP(attribute.updated_at)    as attribute_updated_at_ts,
                    HEX(attribute.flow_tag_attribute_guid)  as flow_tag_attribute_guid,
                    attribute.tag_attribute_name,
                    attribute.tag_attribute_long,
                    attribute.tag_attribute_text,

                    HEX(point_entry.flow_entry_guid)        as points_to_flow_entry_guid,
                    HEX(point_user.flow_user_guid)          as points_to_flow_user_guid,
                    HEX(point_project.flow_project_guid)    as points_to_flow_project_guid,
                    HEX(point_tag.flow_tag_guid)            as points_to_flow_tag_guid,
       
                    IF(
                        point_entry.flow_entry_title IS NOT NULL,
                            point_entry.flow_entry_title,
                            IF(point_user.flow_user_name IS NOT NULL,
                                point_user.flow_user_name,
                                IF (point_project.flow_project_title IS NOT NULL,
                                   point_project.flow_project_title,
                                   point_tag.flow_tag_name 
                                )
                            )
                        ) as points_to_title,
       
       
                    IF(
                        point_entry_project.flow_project_guid IS NOT NULL,
                            HEX(point_entry_project.flow_project_guid),
                            IF(point_user.flow_user_name IS NOT NULL,
                                NULL,
                                IF (point_project.flow_project_title IS NOT NULL,
                                   HEX(point_project.flow_project_guid),
                                   HEX(point_tag_project.flow_project_guid) 
                                )
                            )
                        ) as project_guid_of_pointee,
       
                    IF(
                        point_entry_admin.flow_user_guid IS NOT NULL,
                            HEX(point_entry_admin.flow_user_guid),
                            IF(point_user.flow_user_name IS NOT NULL,
                                NULL,
                                IF (point_project_admin.flow_user_guid IS NOT NULL,
                                   HEX(point_project_admin.flow_user_guid),
                                   HEX(point_tag_admin.flow_user_guid) 
                                )
                            )
                        ) as project_admin_guid_of_pointee,
                    
                    IF(
                        point_entry_admin.flow_user_name IS NOT NULL,
                            point_entry_admin.flow_user_name,
                            IF(point_user.flow_user_name IS NOT NULL,
                                NULL,
                                IF (point_project_admin.flow_user_name IS NOT NULL,
                                   point_project_admin.flow_user_name,
                                   point_tag_admin.flow_user_name
                                )
                            )
                        ) as project_admin_name_of_pointee
       
       
       
                FROM flow_tags t
                INNER JOIN  (
                    
                    
                    WITH RECURSIVE cte AS (
                        (
                            SELECT 0 as depth, driver_tag.id as flow_tag_id , driver_tag.parent_tag_id, cast(null as SIGNED ) as child_tag_id
                            FROM flow_tags driver_tag
                            INNER JOIN flow_projects driver_project ON driver_project.id = driver_tag.flow_project_id
                            $maybe_join_inner_driver_applied
                            $maybe_left_join_inner_driver_applied
                            WHERE 1 
                                AND $where_project  
                                AND $where_name
                                AND $where_tag_guid  
                                AND $where_tag_id
                                AND $where_only_applied_to
                                AND $where_not_applied_to
                                LIMIT $start_place , $page_size
                        )
                        UNION
                        DISTINCT
                        (
                            SELECT  1, parent_tag.id as flow_tag_id, parent_tag.parent_tag_id, c.flow_tag_id as child_tag_id
                            FROM cte c
                            INNER JOIN flow_tags parent_tag ON parent_tag.id = c.parent_tag_id
                        )
                        UNION
                        DISTINCT
                        (
                            SELECT 2 , child_tag.id as flow_tag_id,  c.flow_tag_id as parent_tag_id, null as child_tag_id
                            FROM cte c
                                     INNER JOIN flow_tags child_tag ON child_tag.parent_tag_id = c.flow_tag_id
                        )
                    )
                    SELECT group_concat(depth) as depth ,cte.flow_tag_id, cte.parent_tag_id, group_concat(cte.child_tag_id) as children_list
                    FROM cte
                    GROUP BY cte.flow_tag_id, cte.parent_tag_id
                    
                    
                )  as driver ON driver.flow_tag_id = t.id  
                LEFT JOIN flow_tags parent_t ON parent_t.id = t.parent_tag_id 
                INNER JOIN flow_projects project ON project.id = t.flow_project_id 
                INNER JOIN flow_users admin_user ON admin_user.id = project.admin_flow_user_id 
                LEFT JOIN flow_tag_attributes attribute on attribute.flow_tag_id = t.id
                    
                LEFT JOIN flow_entries point_entry on attribute.points_to_entry_id = point_entry.id
                LEFT JOIN flow_projects point_entry_project on point_entry.flow_project_id = point_entry_project.id 
                LEFT JOIN flow_users point_entry_admin on point_entry_admin.id = point_entry_project.admin_flow_user_id   
                    
                LEFT JOIN flow_users point_user on attribute.points_to_user_id = point_user.id 
            
                LEFT JOIN flow_projects point_project on attribute.points_to_project_id = point_project.id 
                LEFT JOIN flow_users point_project_admin on point_project_admin.id = point_project.admin_flow_user_id
            
                LEFT JOIN flow_tags point_tag on attribute.points_to_tag_id = point_tag.id 
                LEFT JOIN flow_projects point_tag_project on point_tag.flow_project_id = point_tag_project.id 
                LEFT JOIN flow_users point_tag_admin on point_tag_admin.id = point_tag_project.admin_flow_user_id  
                
                WHERE 1 
                ORDER BY flow_tag_id,flow_tag_attribute_id DESC ;
                ";

        try {

            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

            /**
             * @var FlowTag[] $ret
             */
            $ret = [];
            /**
             * @var array<string,FlowTag> $map_all_tags_by_id
             */
            $map_all_tags_by_id = [];
            $map_prefix = "tag-";
            /**
             * @var array<string, FlowTag> $rem_tags
             */
            $rem_tags = [];
            foreach ($res as $row) {
                $tag_node = new FlowTag($row);
                if (array_key_exists($tag_node->flow_tag_guid,$rem_tags)) {
                    $tag_node = $rem_tags[$tag_node->flow_tag_guid];
                } else {
                    $rem_tags[$tag_node->flow_tag_guid] = $tag_node;
                    $ret[] = $tag_node;

                    $map_all_tags_by_id[$map_prefix.$tag_node->flow_tag_id] = $tag_node;
                }

                $attribute_node = new FlowTagAttribute($row);
                if ($attribute_node->getFlowTagAttributeId()) {
                    $tag_node->attributes[] = $attribute_node;
                }


            }

            //put parents in
            foreach ($map_all_tags_by_id as $prefix_id => $dat_tag_you_do ) {
                WillFunctions::will_do_nothing($prefix_id);
                if ($dat_tag_you_do->parent_tag_id) {
                    $what_prefix = $map_prefix. $dat_tag_you_do->parent_tag_id;
                    if (!array_key_exists($what_prefix,$map_all_tags_by_id)) {
                        throw new LogicException(sprintf("Could not find parent %s for %s ",$dat_tag_you_do->parent_tag_id,$dat_tag_you_do->flow_tag_id));
                    }
                    $proud_parent = $map_all_tags_by_id[$what_prefix];
                    if (empty($proud_parent)) {throw new LogicException("Parent is empty at index of $what_prefix");}
                    $dat_tag_you_do->flow_tag_parent = $proud_parent;
                }
            }

            /**
             * @var FlowTag[] $ret
             */
            $filtered = [];
            //filter out the ones that  are not top level searches, the rest will be in the parent list
            foreach ($ret as $item) {
                if ($search->getOwningProjectGuid()) {
                    if ($item->flow_project_guid !== $search->getOwningProjectGuid() ) {continue;}
                }

                if ($search->tag_name_term) {
                    if (false === stripos($item->flow_tag_name, $search->tag_name_term) ) {continue;}
                }

                if (count($search->get_guids())) {
                    $found_guid = false;
                    foreach ($search->get_guids() as $a_search_guid) {
                        if ($item->flow_tag_guid === $a_search_guid) {$found_guid = true; break;}
                    }

                    if (!$found_guid) {continue;}
                }

                if (count($search->tag_ids)) {
                    $found_id = false;
                    for($i = 0; $i < count($search->tag_ids); $i++) {
                        if ($item->flow_tag_id === $search->tag_ids[$i]) {$found_id = true; break;}
                    }
                    if (!$found_id) {continue;}
                }

                $filtered[] = $item;
            }

            if ($search->flag_get_applied) {
                //add attached tags
                $tag_id_array = [];

                /**
                 * @var array<string,FlowTag> $match_map
                 */
                $match_map = [];
                foreach ($filtered as $tag_rehydrated) {
                    $tag_id_array[] = $tag_rehydrated->flow_tag_id;
                    $match_map[$tag_rehydrated->flow_tag_guid] = $tag_rehydrated;
                }

                $attached_map = FlowAppliedTag::get_applied_tags($tag_id_array);

                foreach ($attached_map as $tag_guid => $applied_array) {
                    if (array_key_exists($tag_guid,$match_map)) {
                        $match_map[$tag_guid]->applied = $applied_array;
                    }
                }
            }

            //filter some more if restricting to things that are applied
            if (count($search->only_applied_to_guids)) {
                $applied_filter = [];
                foreach ($filtered as $check_again_tag) {
                    $applied_that_includes_tagged = $check_again_tag->find_applied_by_guid_of_tagged($search->only_applied_to_guids);
                    if (empty($applied_that_includes_tagged)) {
                        continue;
                    }
                    $applied_filter[] = $check_again_tag;
                }
                $filtered = $applied_filter;
            }

            if (count($search->not_applied_to_guids)) {
                $applied_filter = [];
                foreach ($filtered as $check_again_tag) {
                    $applied_that_includes_tagged = $check_again_tag->find_applied_by_guid_of_tagged($search->not_applied_to_guids);
                    if (count($applied_that_includes_tagged)) {
                        continue;
                    }
                    $applied_filter[] = $check_again_tag;
                }
                $filtered = $applied_filter;
            }
            foreach ($filtered as $b_tag) {
                $b_tag->refresh_inherited_fields();
            }
            $resolved_attributes = FlowTagStandardAttribute::read_standard_attributes_of_tags($ret);
            foreach ($resolved_attributes as $tag_guid => $standard_attributes) {
                $tag = $rem_tags[$tag_guid]??null;
                if (!$tag) {continue;} //may not have any standards
                $tag->setStandardAttributes($standard_attributes) ;
            }
            $this->tags_found = $filtered;

            return  $this;
        } catch (Exception $e) {
            static::get_logger()->alert("FlowTag model cannot get_tags ",['exception'=>$e]);
            throw $e;
        }

    }

    /**
     * sort parents before children
     * if there are tags with a parent set, but not in the array, then those are put at the end
     * @param FlowTag[] $tag_array_to_sort
     * @return FlowTag[]
     */
    public static function sort_tag_array_by_parent(array $tag_array_to_sort) : array {

        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','tag'=>null];
        foreach ($tag_array_to_sort as $tag) {
            $data[] = ['id' => $tag->flow_tag_id, 'parent' => $tag->parent_tag_id??0, 'title' => $tag->flow_tag_name,'tag'=>$tag];
        }
        $tree = new Tree(
            $data,
            ['rootId' => -1]
        );

        $sorted_nodes =  $tree->getNodes();
        $ret = [];
        foreach ($sorted_nodes as $node) {
            $what =  $node->tag??null;
            if ($what) {$ret[] = $what;}
        }
        return $ret;
    }
}