<?php

namespace app\models\multi;


use app\models\base\FlowBase;
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
        $where_array = [];

        if ($search->guid) {
            $where_array[] = "thing.thing_guid = UNHEX(?)";
            $args[] = $search->guid;
        }
        if ($search->title) {
            $where_array[] = "thing.thing_title like ?";
            $args[] = $search->title . '%';
        }

        if ($search->created_at_ts) {
            $where_array[] = "thing.thing_created_at >= FROM_UNIXTIME(?)";
            $args[] = $search->created_at_ts;
        }

        if ($search->type) {
            $where_array[] = "thing.thing_created_at = ? ";
            $args[] = $search->created_at_ts;
        }

        $where_conditions = 4;
        if (!empty($where_array)) {
            $where_conditions = implode(" AND ",$where_array);
        }

        $start_place = ($page - 1) * $page_size;



        $sql_final = "SELECT 
                            HEX(thing.thing_guid) as guid ,
                            thing.thing_id as id,
                            thing.thing_title as title,
                            thing.thing_type as type,
                            UNIX_TIMESTAMP(thing.thing_created_at) as created_at_ts
                        FROM flow_things thing 
                        WHERE 1 AND $where_conditions
                        ORDER BY title ASC
                        LIMIT $start_place, $page_size

                        ";


        $db = static::get_connection();
        $res = $db->safeQuery($sql_final, $args, PDO::FETCH_OBJ);
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