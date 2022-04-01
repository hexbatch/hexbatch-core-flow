<?php

namespace app\models\standard;

use app\models\base\FlowBase;
use InvalidArgumentException;
use ParagonIE\EasyDB\Exception\QueryError;
use PDO;
use TypeError;

//todo use with raw attribute search
class RawAttributeSearch extends FlowBase {

    /**
     * @param RawAttributeSearchParams $params
     * @return RawAttributeData[]
     */
    public static function search(RawAttributeSearchParams $params) : array {

        $ret = [];
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
          public ?string $attribute_name;
        public ?string $text_val;
        public ?int $long_val;
        public ?string $attribute_guid;
        public ?string $tag_guid;
        public ?string $parent_attribute_guid;
         */
        $sql = "
            SELECT 
                   a.tag_attribute_name as attribute_name,
                   a.tag_attribute_text as text_val,
                   a.tag_attribute_long as long_val,
                   HEX(a.flow_tag_attribute_guid) as attribute_guid, 
                   HEX(null ) as parent_attribute_guid, 
                   HEX(t.flow_tag_guid) as tag_guid 
            FROM flow_tag_attributes a    
            INNER JOIN flow_tags t on a.flow_tag_id = t.id 
            WHERE 1 AND $where_stuff
            LIMIT $start_place , $page_size

        ";


        try {

            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);


            foreach ($res as $row) {
                $node = new RawAttributeData($row);
                if (!array_key_exists($node->getTagGuid(),$ret)) {
                    $ret[$node->getTagGuid()] = [];
                }
                $ret[$node->getTagGuid()][] = $node;

            }
        } catch ( InvalidArgumentException|QueryError|TypeError $e) {
            static::get_logger()->alert("RawAttributeSearch cannot get data ",['exception'=>$e]);
            throw $e;
        }

        //order parents first
        return $ret;
    }
}