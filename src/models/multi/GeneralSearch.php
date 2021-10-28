<?php

namespace app\models\multi;


use app\models\base\FlowBase;
use PDO;

class GeneralSearch extends FlowBase{

    const DEFAULT_PAGE_SIZE = 20;
    const UNLIMITED_RESULTS_PER_PAGE = 100000;

    /**
     * @param GeneralSearchResult[] $matches
     * @param int[] $project_ids
     * @param int[] $user_ids
     * @param int[] $entry_ids
     */
    public static function sort_ids_into_arrays(array $matches,array &$project_ids, array &$user_ids, array &$entry_ids) {
        $project_ids=[];
        $user_ids = [];
        $entry_ids = [];
        foreach ($matches as $match) {
            switch ($match->type) {
                case GeneralSearchResult::TYPE_USER: {
                    $user_ids[] = $match->id;
                    break;
                }
                case GeneralSearchResult::TYPE_ENTRY: {
                    $entry_ids[] = $match->id;
                    break;
                }
                case GeneralSearchResult::TYPE_PROJECT: {
                    $project_ids[] = $match->id;
                    break;
                }
                case null: {
                    break;
                }
                default: {
                    static::get_logger()->warning("GeneralSearch::sort_ids_into_arrays does not recognize type",['match'=>$match]);
                }

            }
        }
    }

    /**
     * @param GeneralSearchParams $search
     * @param int $page
     * @param int $page_size
     * @return GeneralSearchResult[]
     */
    public static function general_search(GeneralSearchParams $search,
                                          int     $page = 1,
                                          int     $page_size =  self::DEFAULT_PAGE_SIZE): array {

        $args = [];
        $where_array = [];

        if ($page_size === self::UNLIMITED_RESULTS_PER_PAGE) {
            $page = 1;
        }

        if (count($search->guids)) {
            $in_question_array = [];
            foreach ($search->guids as $a_guid) {
                if ( ctype_xdigit($a_guid) ) {
                    $args[] = $a_guid;
                    $in_question_array[] = "UNHEX(?)";
                }
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_array[] = "thing.thing_guid in ($comma_delimited_unhex_question)";
            }
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
         * @var GeneralSearchResult[] $ret
         */
        $ret = [];
        foreach ($res as $row) {
            $node = new GeneralSearchResult($row);
            $ret[] = $node;
        }

        return $ret;



    }
}