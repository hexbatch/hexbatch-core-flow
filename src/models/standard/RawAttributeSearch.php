<?php

namespace app\models\standard;

use app\models\base\FlowBase;
use BlueM\Tree;
use InvalidArgumentException;
use ParagonIE\EasyDB\Exception\QueryError;
use PDO;
use TypeError;


class RawAttributeSearch extends FlowBase {

    /**
     * @param RawAttributeSearchParams $params
     * @return RawAttributeData[]
     */
    public static function search(RawAttributeSearchParams $params) : array {

        $args = [];
        $where_and = [];


        if (count($params->getTagGuids())) {
            $in_question_array = [];
            foreach ($params->getTagGuids() as $a_guid) {
                $args[] = $a_guid;
                $in_question_array[] = "UNHEX(?)";
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_and[] = "t.flow_tag_guid in ($comma_delimited_unhex_question)";
            }
        }

        if (count($params->getTagIds())) {
            $in_question_array = [];
            foreach ($params->getTagIds() as $a_guid) {
                $args[] = $a_guid;
                $in_question_array[] = "?";
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_and[] = "a.flow_tag_id in ($comma_delimited_unhex_question)";
            }
        }


        if (count($params->getAttributeNames())) {
            $in_question_array = [];
            foreach ($params->getAttributeNames() as $a_name) {
                $args[] = $a_name;
                $in_question_array[] = "?";
            }
            if (count($in_question_array)) {
                $comma_delimited_question = implode(",",$in_question_array);
                $where_and[] = "a.tag_attribute_name in ($comma_delimited_question)";
            }
        }


        $where_stuff = implode(' AND ',$where_and);


        $page_size = $params->getPageSize();
        $start_place = ($params->getPage() - 1) * $page_size;


        $db = static::get_connection();


        /*
         * protected ?int $tag_id;
         * protected ?string $tag_guid;
         * protected ?int $attribute_id;
         * protected ?string $attribute_guid;
         * protected ?string $attribute_name;
         * protected ?int $long_val;
         * protected ?string $text_val;
         *





    protected ?int $parent_attribute_id;
    protected ?string $parent_attribute_guid;

         */

        $sql = "SELECT 
                    
                    t.id                                    as tag_id,
                    HEX(t.flow_tag_guid)                    as tag_guid,
                    attribute.id                            as attribute_id,
                    HEX(attribute.flow_tag_attribute_guid)  as attribute_guid,
                    attribute.tag_attribute_name            as attribute_name,
                    attribute.tag_attribute_long            as long_val,
                    attribute.tag_attribute_text            as text_val    ,
                    
                    t.parent_tag_id                         as parent_tag_id ,
                    HEX(parent_t.flow_tag_guid)             as parent_tag_guid
       
                FROM flow_tags t
                INNER JOIN  (
                    
                    
                    WITH RECURSIVE cte AS (
                        (
                            SELECT 0 as depth, driver_tag.id as flow_tag_id , driver_tag.parent_tag_id, cast(null as SIGNED ) as child_tag_id
                            FROM flow_tags driver_tag
                            INNER JOIN flow_projects driver_project ON driver_project.id = driver_tag.flow_project_id
                           
                            WHERE 1 
                                AND $where_stuff  
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
                LEFT JOIN flow_tag_attributes attribute on attribute.flow_tag_id = t.id
                WHERE 1 
                ORDER BY tag_id,attribute_id DESC ;
                ";


        try {

            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

            $nodes = [];
            foreach ($res as $row) {
                $node = new RawAttributeData($row);
                $nodes[] = $node;
            }
            if (empty($nodes)) {return [];}

            $ret = static::sort_raw_array_by_parent_tag($nodes);

            /**
             * @var array<string,RawAttributeData[]> $map_raw_by_tag_guid
             */
            $map_raw_by_tag_guid = [];
            foreach ($ret as $mapper) {
                if (!isset($map_raw_by_tag_guid[$mapper->getTagGuid()])) {
                    $map_raw_by_tag_guid[$mapper->getTagGuid()] = [];
                }
                $map_raw_by_tag_guid[$mapper->getTagGuid()][] = $mapper;
            }

            /**
             * @var array<string,string> $map_tag_parent_guid_by_tag_guid
             */
            $map_tag_parent_guid_by_tag_guid = [];
            foreach ($ret as $mapper) {
                $map_tag_parent_guid_by_tag_guid[$mapper->getTagGuid()] = $mapper->getParentTagGuid();
            }

            //sort by parent tags (parent first)
            // for each raw, loop by parent tag backwards until find its attribute name, then enter that as the parent attribute

            for($i = count($ret) - 1; $i >= 0 ; $i--) {
                $raw = $ret[$i];
                //loop by parent going down
                $parent_tag_guid = $raw->getParentTagGuid();
                while($parent_tag_guid) {
                    $parent_of_mine_attributes = $map_raw_by_tag_guid[$parent_tag_guid];
                    foreach ($parent_of_mine_attributes as $maybe_parent_attribute) {
                        if ($maybe_parent_attribute->getAttributeName() === $raw->getAttributeName()) {
                            $raw->setParentAttributeGuid($maybe_parent_attribute->getAttributeGuid());
                            $raw->setParentAttributeID($maybe_parent_attribute->getAttributeID());
                            break;
                        }
                    }
                    $parent_tag_guid = $map_tag_parent_guid_by_tag_guid[$parent_tag_guid];
                }

            }
        } catch ( InvalidArgumentException|QueryError|TypeError $e) {
            static::get_logger()->alert("RawAttributeSearch cannot get data ",['exception'=>$e]);
            throw $e;
        }


        return $ret;
    }

    /**
     * sort parents before children
     * if there are tags with a parent set, but not in the array, then those are put at the end
     * @param RawAttributeData[] $raw_array_to_sort
     * @return RawAttributeData[]
     */
    protected static function sort_raw_array_by_parent_tag(array $raw_array_to_sort) : array {

        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','raw'=>null];
        foreach ($raw_array_to_sort as $raw) {
            $data[] = [
                'id' => $raw->getTagID(),
                'parent' => $raw->getParentTagID()??0,
                'title' => $raw->getAttributeName(),
                'raw'=>$raw];
        }
        $tree = new Tree(
            $data,
            ['rootId' => -1]
        );

        $sorted_nodes =  $tree->getNodes();
        $ret = [];
        foreach ($sorted_nodes as $node) {
            $what =  $node->raw??null;
            if ($what) {$ret[] = $what;}
        }
        return $ret;
    }
}