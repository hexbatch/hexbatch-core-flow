<?php

namespace app\models\entry\entry_node;

use app\models\entry\IFlowEntry;
use Exception;
use InvalidArgumentException;
use PDO;


class EntryNodeSearch extends EntryNodeContainer {

    /**
     * @param EntryNodeSearchParams $params
     * @return EntryNodeSearch
     * @throws Exception
     */
    public function search(EntryNodeSearchParams $params) : EntryNodeSearch {

        if($params->is_empty()) {throw new InvalidArgumentException("search for nodes: params is empty");}
        $args = [];
        $where_and = [];
        $used_inner_joins = [];
        $inner_joins = [];
        /*
         *

         */
        $inner_joins['applied'] = "INNER JOIN flow_applied_tags driver_applied on ".
                                        "driver_applied.tagged_flow_entry_node_id = driver_node.id";

        $inner_joins['tag'] = "INNER JOIN flow_tags driver_tag  on driver_tag.id = driver_applied.flow_tag_id";

        $inner_joins['parent'] = "INNER JOIN flow_tags driver_parent_tag  on driver_tag.flow_entry_node_parent_id = driver_parent_tag.id";

        if (count($params->getTagGuids())) {
            $in_question_array = [];
            foreach ($params->getTagGuids() as $a_guid) {
                $args[] = $a_guid;
                $in_question_array[] = "UNHEX(?)";
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_and[] = "driver_tag.flow_tag_guid in ($comma_delimited_unhex_question)";
                $used_inner_joins['tag'] = $inner_joins['tag'];
            }
        }

        if (count($params->getAppliedGuids())) {
            $in_question_array = [];
            foreach ($params->getAppliedGuids() as $a_guid) {
                $args[] = $a_guid;
                $in_question_array[] = "UNHEX(?)";
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_and[] = "driver_applied.flow_applied_tag_guid in ($comma_delimited_unhex_question)";
                $used_inner_joins['applied'] = $inner_joins['applied'];
            }
        }

        if (count($params->getEntryGuids())) {
            $in_question_array = [];
            foreach ($params->getEntryGuids() as $a_guid) {
                $args[] = $a_guid;
                $in_question_array[] = "UNHEX(?)";
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_and[] = "driver_entry.flow_entry_guid in ($comma_delimited_unhex_question)";
            }
        }

        if (count($params->getNodeGuids())) {
            $in_question_array = [];
            foreach ($params->getNodeGuids() as $a_guid) {
                $args[] = $a_guid;
                $in_question_array[] = "UNHEX(?)";
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_and[] = "driver_node.entry_node_guid in ($comma_delimited_unhex_question)";
            }
        }

        if ($params->getParentGuid()) {
            $used_inner_joins['parent'] = $inner_joins['parent'];
            $where_and[] = "driver_parent_tag.entry_node_guid = ?";
            $args[] = $params->getParentGuid();
        }

        if (!is_null($params->getIsTopNode())) {
            if ($params->getIsTopNode()) {
                $where_and[] = "driver_node.flow_entry_node_parent_id IS NULL";
            } else {
                $where_and[] = "driver_node.flow_entry_node_parent_id IS NOT NULL";
            }

        }

        if (empty($where_and)) {return $this;}
        


        $where_stuff = implode(' AND ',$where_and);
        $inner_joins_combined = implode("\n",$used_inner_joins);

        $page_size = $params->getPageSize();
        $start_place = ($params->getPage() - 1) * $page_size;


        $db = static::get_connection();


        $sql = "SELECT

                    node.id                                     as node_id,
                    HEX(node.entry_node_guid)                   as node_guid,
                    
                    UNIX_TIMESTAMP(node.entry_node_created_at)  as node_created_at_ts,
                    UNIX_TIMESTAMP(node.entry_node_updated_at)  as node_updated_at_ts,
                    node.bb_tag_name                            as bb_tag_name,
                    node.entry_node_words                       as node_words,
                    HEX(entry.flow_entry_guid)                  as flow_entry_guid,
                    node.flow_entry_id                          as flow_entry_id,
                    HEX(parent.entry_node_guid)                 as parent_guid,
                    node.flow_entry_node_parent_id              as parent_id,
                    node.entry_node_attributes                  as node_attributes,
                    HEX(applied.flow_applied_tag_guid)          as flow_applied_tag_guid,
                    applied.id                                  as flow_applied_tag_id,
                    HEX(tag.flow_tag_guid)                      as flow_tag_guid,
                    tag.id                                      as flow_tag_id
                
                
                FROM flow_entry_nodes node
                INNER JOIN  (
                
                
                    WITH RECURSIVE cte AS (
                        (
                            SELECT driver_node.id as entry_node_id,
                                   driver_node.flow_entry_node_parent_id,
                                   0 as da_count
                            FROM flow_entry_nodes driver_node
                            INNER JOIN flow_entries driver_entry ON driver_entry.id = driver_node.flow_entry_id
                            $inner_joins_combined
                            where  1 
                                    AND $where_stuff  
                                    LIMIT $start_place , $page_size
                        )
                        UNION ALL
                        SELECT child.id, child.flow_entry_node_parent_id, op.da_count + 1
                        FROM flow_entry_nodes child
                                 INNER JOIN cte op ON op.entry_node_id = child.flow_entry_node_parent_id
                        
                    )
                    SELECT group_concat(da_count) as depth ,cte.entry_node_id, cte.flow_entry_node_parent_id
                    FROM cte
                    GROUP BY cte.entry_node_id, cte.flow_entry_node_parent_id
                
                
                )  as driver ON driver.entry_node_id = node.id
                INNER JOIN flow_entries entry ON entry.id = node.flow_entry_id
                LEFT JOIN flow_entry_nodes parent ON parent.id = node.flow_entry_node_parent_id
                LEFT JOIN flow_applied_tags applied on applied.tagged_flow_entry_node_id = node.id
                LEFT JOIN flow_tags tag  on tag.id = applied.flow_tag_id
                WHERE 1
                ORDER BY node.id ASC ;


                ";


        try {

            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

            $this->flat_contents = [];

            /**
             * @var array<string,IFlowEntry> $nodes
             */
            $nodes = [];

            foreach ($res as $row) {
                $node = new FlowEntryNode($row);
                $this->flat_contents[] = $node;
                $nodes[$node->get_node_guid()] = $node;
            }

            foreach ($this->flat_contents as $node) {
                $node->set_parent($nodes[$node->get_parent_guid()]??null);
            }

            $this->flat_contents = $this->sort_nodes_by_parent_id();

            return $this;
        } catch (Exception $e) {
            static::get_logger()->error("EntryNodeSearch::search issue ". $e->getMessage());
            throw $e;
        }

    }//end search


}