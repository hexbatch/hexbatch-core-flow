<?php

namespace app\models\standard;

use app\models\base\FlowBase;
use InvalidArgumentException;
use ParagonIE\EasyDB\Exception\QueryError;
use PDO;
use TypeError;

class StandardAttributeSearch extends FlowBase {

    /**
     * Reads only from the flow_standard_attributes table
     *
     * @param StandardAttributeSearchParams $params
     * @return IFlowTagStandardAttribute[]
     */
    public static function search(StandardAttributeSearchParams $params) : array {

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
                $where_and[] = "tag.flow_tag_guid in ($comma_delimited_unhex_question)";
            }
        }

        if ($params->getOwningProjectGuids()) {
            $in_question_array = [];
            foreach ($params->getOwningProjectGuids() as $a_guid) {
                $args[] = $a_guid;
                $in_question_array[] = "UNHEX(?)";
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_and[] = "project.flow_project_guid in ($comma_delimited_unhex_question)";
            }
        }

        if ($params->getOwningProjectNames()) {
            $in_question_array = [];
            foreach ($params->getOwningProjectNames() as $a_name) {
                $args[] = $a_name;
                $in_question_array[] = "?";
            }
            if (count($in_question_array)) {
                $comma_delimited_question = implode(",",$in_question_array);
                $where_and[] = "project.flow_project_title in ($comma_delimited_question)";
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
                $where_and[] = "a.standard_name in ($comma_delimited_question)";
            }
        }


        if ($params->getOwnerUserGuid()) {
            $args[] = $params->getOwnerUserGuid();
            $where_and[] = "u.flow_user_guid in (UNHEX(?))";
        }

        if ($params->getOwnerUserName()) {
            $args[] = $params->getOwnerUserName();
            $where_and[] = "u.flow_user_name in (?)";
        }

        if ($params->getOwnerUserEmail()) {
            $args[] = $params->getOwnerUserEmail();
            $where_and[] = "u.flow_user_email in (?)";
        }

        $where_stuff = implode(' AND ',$where_and);


        $page_size = $params->getPageSize();
        $start_place = ($params->getPage() - 1) * $page_size;


        $sql = "SELECT 
                    a.id                                    as standard_id,
                    a.standard_name                         as standard_name,
                    a.standard_json                         as standard_value,
                    UNIX_TIMESTAMP(a.standard_updated_at)   as standard_updated_ts,
                    HEX(a.standard_guid)                    as standard_guid,
                    a.flow_tag_id                           as tag_id,
                    HEX(tag.flow_tag_guid)                  as tag_guid,
                    HEX(project.flow_project_guid)          as project_guid
       
                FROM flow_standard_attributes a
                INNER JOIN flow_tags tag on a.flow_tag_id = tag.id
                INNER JOIN flow_projects project on tag.flow_project_id = project.id
                INNER JOIN flow_users u on project.admin_flow_user_id = u.id
                WHERE 1 
                  AND $where_stuff
                LIMIT $start_place , $page_size";

        $ret = [];
        try {
            $db = static::get_connection();
            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

            $nodes = [];
            foreach ($res as $row) {
                $node = new FlowTagStandardAttribute($row);
                $nodes[] = $node;
            }
            if (empty($nodes)) {
                return [];
            }
        }
        catch ( InvalidArgumentException|QueryError|TypeError $e) {
            static::get_logger()->alert("StandardAttributeSearch cannot get data ",['exception'=>$e]);
            throw $e;
        }
        return $ret;
    }
}