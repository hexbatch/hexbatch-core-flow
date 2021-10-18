<?php

namespace app\models\tag;

use app\models\base\FlowBase;
use app\models\multi\GeneralSearchResult;
use JsonSerializable;
use PDO;
use RuntimeException;

class FlowAppliedTag extends FlowBase implements JsonSerializable {

    public ?int $id;
    public ?int $flow_tag_id;

    public ?int $tagged_flow_entry_id;
    public ?int $tagged_flow_user_id;
    public ?int $tagged_flow_project_id;
    public ?int $created_at_ts;
    public ?string $flow_applied_tag_guid;

    public ?string $flow_tag_guid;
    public ?string $tagged_flow_entry_guid;
    public ?string $tagged_flow_user_guid;
    public ?string $tagged_flow_project_guid;

    public ?string $tagged_title;


    public function __construct($object=null){

        $this->id = null;
        $this->flow_tag_id = null;
        $this->flow_tag_guid = null;
        $this->tagged_flow_entry_id = null;
        $this->tagged_flow_user_id = null;
        $this->tagged_flow_project_id = null;
        $this->created_at_ts = null;
        $this->flow_applied_tag_guid = null;
        $this->tagged_flow_entry_guid = null;
        $this->tagged_flow_user_guid = null;
        $this->tagged_flow_project_guid = null;
        $this->tagged_title = null;

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }
    }

    
    public function jsonSerialize(): array
    {
        return [
            "flow_applied_tag_guid" => $this->flow_applied_tag_guid,
            "tagged_flow_entry_guid" => $this->tagged_flow_entry_guid,
            "tagged_flow_user_guid" => $this->tagged_flow_user_guid,
            "tagged_flow_project_guid" => $this->tagged_flow_project_guid,
            "created_at_ts" => $this->created_at_ts,
            "tagged_title" => $this->tagged_title,
        ];
    }

    /**
     * @param int[] $tag_id_array
     * @return array<string,FlowAppliedTag[]>
     */
    public static function get_applied_tags(array $tag_id_array) : array {

        if (empty($tag_id_array)) { return [];}
        $comma_delimited_tag_ids = implode(",",$tag_id_array);
        $db = static::get_connection();

        $I = function($v) { return $v; };

        $sql = "
            SELECT
               GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
               GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,    
               GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
               GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,    
               app.tagged_flow_project_id as taggee_id,
               HEX(fp.flow_project_guid) as taggee_guid,
               fp.flow_project_title as tagged_title,
               '{$I(GeneralSearchResult::TYPE_PROJECT)}' as taggie_type
            FROM flow_applied_tags app
                INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                INNER JOIN flow_projects fp on app.tagged_flow_project_id = fp.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_project_id IS NOT NULL
            GROUP BY app.tagged_flow_project_id
        UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,   
                app.tagged_flow_user_id as taggee_id,
                HEX(fu.flow_user_guid) as taggee_guid,
                fu.flow_user_name as tagged_title,
                '{$I(GeneralSearchResult::TYPE_USER)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_users fu on app.tagged_flow_user_id = fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_user_id IS NOT NULL
            GROUP BY app.tagged_flow_user_id
        UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,   
                app.tagged_flow_entry_id as taggee_id,
                HEX(fe.flow_entry_guid) as taggee_guid,
                fe.flow_entry_title as tagged_title,   
                '{$I(GeneralSearchResult::TYPE_ENTRY)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_entries fe on app.tagged_flow_entry_id = fe.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_entry_id IS NOT NULL
            GROUP BY tagged_flow_entry_id
        ";

        $res = $db->safeQuery($sql, [], PDO::FETCH_OBJ);
        $ret = [];
        foreach ($res as $row) {
            $tag_guid_array = explode(",",$row->tag_guid_list);
            $tag_id_array = explode(",",$row->tag_id_list);
            $created_at_array = explode(",",$row->tagged_at_ts);
            $applied_guid_array = explode(",",$row->applied_guid_list);
            if (count($tag_guid_array) !== count($created_at_array) ||
                count($tag_guid_array) !== count($tag_id_array) ||
                count($tag_guid_array) !== count($applied_guid_array)
            ) {
                throw new RuntimeException(
                    "[get_applied_tags] guid and created_at and id list does not have same numbers, check nulls");
            }
            for($i = 0; $i< count($applied_guid_array); $i++) {
                $node = new FlowAppliedTag();
                $node->created_at_ts = (int)$created_at_array[$i];
                $node->flow_tag_guid = $tag_guid_array[$i];
                $node->flow_tag_id = $tag_id_array[$i];
                $node->flow_applied_tag_guid = $applied_guid_array[$i];
                $node->tagged_title = $row->tagged_title;
                switch ($row->taggie_type) {
                    case GeneralSearchResult::TYPE_ENTRY: {
                        $node->tagged_flow_entry_guid = $row->taggee_guid;
                        $node->tagged_flow_entry_id = $row->taggee_id;
                        break;
                    }
                    case GeneralSearchResult::TYPE_USER: {
                        $node->tagged_flow_user_guid = $row->taggee_guid;
                        $node->tagged_flow_user_id = $row->taggee_id;
                        break;
                    }
                    case GeneralSearchResult::TYPE_PROJECT:  {
                        $node->tagged_flow_project_guid = $row->taggee_guid;
                        $node->tagged_flow_project_id = $row->taggee_id;
                        break;
                    }
                    default: {
                        throw new RuntimeException(
                            "[get_applied_tags] got unknown taggie type of ".$row->taggie_type );
                    }
                }

                if (!array_key_exists($node->flow_tag_guid,$ret)) {
                    $ret[$node->flow_tag_guid] = [] ;
                }
                $ret[$node->flow_tag_guid][] = $node ;
            }
        }

        return $ret;
    }
}