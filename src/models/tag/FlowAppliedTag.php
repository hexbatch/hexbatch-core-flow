<?php

namespace app\models\tag;

use app\models\base\FlowBase;
use app\models\multi\GeneralSearch;
use app\models\tag\brief\BriefFlowAppliedTag;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;
use Slim\Interfaces\RouteParserInterface;

class FlowAppliedTag extends FlowBase implements JsonSerializable {

    public ?int $id;
    public ?int $flow_tag_id;

    public ?int $tagged_flow_entry_node_id;
    public ?int $tagged_flow_entry_id;
    public ?int $tagged_flow_user_id;
    public ?int $tagged_flow_project_id;
    public ?string $flow_project_guid;

    public ?int $created_at_ts;
    public ?string $flow_applied_tag_guid;

    public ?string $flow_tag_guid;
    public ?string $tagged_flow_entry_guid;
    public ?string $tagged_flow_entry_node_guid;
    public ?string $tagged_flow_user_guid;
    public ?string $tagged_flow_project_guid;

    public ?string $tagged_title;
    public ?string $flow_applied_tag_owner_user_guid;
    public ?string $flow_applied_tag_owner_user_name;

    public ?string $tagged_url;

    //public ?string $flow_project_guid;




    public function __construct($object=null){

        $this->id = null;
        $this->flow_tag_id = null;
        $this->flow_tag_guid = null;
        $this->tagged_flow_entry_id = null;
        $this->tagged_flow_entry_node_id = null;
        $this->tagged_flow_user_id = null;
        $this->tagged_flow_project_id = null;
        $this->flow_project_guid = null;
        $this->created_at_ts = null;
        $this->flow_applied_tag_guid = null;
        $this->tagged_flow_entry_node_guid = null;
        $this->tagged_flow_entry_guid = null;
        $this->tagged_flow_user_guid = null;
        $this->tagged_flow_project_guid = null;
        $this->tagged_title = null;
        $this->flow_applied_tag_owner_user_guid = null;
        $this->flow_applied_tag_owner_user_name = null;
        $this->tagged_url = null;

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $careful_value = $val;
                if ($val === '') {$careful_value = null;}
                $this->$key = $careful_value;
            }
        }
    }



    
    public function jsonSerialize(): array
    {
        if ($this->get_brief_json_flag()) {
            $brief = new BriefFlowAppliedTag($this);
            return $brief->to_array();
        } else {
            return [
                "flow_applied_tag_guid" => $this->flow_applied_tag_guid,
                "flow_tag_guid" => $this->flow_tag_guid,
                "tagged_flow_entry_guid" => $this->tagged_flow_entry_guid,
                "tagged_flow_entry_node_guid" => $this->tagged_flow_entry_node_guid,
                "tagged_flow_user_guid" => $this->tagged_flow_user_guid,
                "tagged_flow_project_guid" => $this->tagged_flow_project_guid,
                "created_at_ts" => $this->created_at_ts,
                "tagged_title" => $this->tagged_title,
                "flow_applied_tag_owner_user_guid" => $this->flow_applied_tag_owner_user_guid,
                "flow_applied_tag_owner_user_name" => $this->flow_applied_tag_owner_user_name,
                "tagged_url" => $this->tagged_url,
                "flow_project_guid" => $this->flow_project_guid,
            ];
        }

    }

    public function set_link_for_tagged(RouteParserInterface $routeParser) {

        if ($this->tagged_flow_project_guid) {
            $this->tagged_url = $routeParser->urlFor('single_project_home',[
                "user_name" => $this->flow_applied_tag_owner_user_name,
                "project_name" => $this->tagged_title
            ]);
        } elseif ( $this->tagged_flow_user_guid) {
            $this->tagged_url = $routeParser->urlFor('user_page',[
                "user_name" => $this->tagged_title,
            ]);
        }
        elseif ( $this->tagged_flow_entry_guid) {
            $this->tagged_url = $routeParser->urlFor('show_entry',[
                "user_name" => $this->flow_applied_tag_owner_user_guid,
                "project_name" => $this->flow_project_guid,
                "entry_name" => $this->tagged_flow_entry_guid,
            ]);
        }
        elseif ( $this->tagged_flow_entry_node_guid) {
            $this->tagged_url = $routeParser->urlFor('show_entry',[
                "user_name" => $this->flow_applied_tag_owner_user_guid,
                "project_name" => $this->flow_project_guid,
                "entry_name" => $this->tagged_flow_entry_guid,
            ]);
        }
        else {
            static::get_logger()->warning("Not able to genenerate a link for applied");
        }

    }

    /**
     * @param int[] $tag_id_array
     * @param string|null $match_only_applied_guid , if given, will only return the applied that fits the tag_id and matches guid
     * @param int|null $match_only_applied_id , if given, will only return the applied that fits the tag_id and matches the id
     * @return array<string,FlowAppliedTag[]>
     */
    public static function get_applied_tags(array $tag_id_array,?string $match_only_applied_guid=null,
                                            ?int $match_only_applied_id=null) : array {

        if (empty($tag_id_array)) { return [];}
        $tag_id_ints = [];
        foreach ($tag_id_array as $raw_int) {
            $maybe_int = intval($raw_int);
            if ($maybe_int) {$tag_id_ints[] = $maybe_int;}
        }

        if (empty($tag_id_ints)) {
            throw new InvalidArgumentException("Need at last one integer in the tag id array to get applied tags");
        }
        $comma_delimited_tag_ids = implode(",",$tag_id_ints);
        $db = static::get_connection();

        $I = function($v) { return $v; };

        $args = [];
        $where_match_guid = 16;
        if ($match_only_applied_guid) {
            $where_match_guid = " app.flow_applied_tag_guid = UNHEX(?) ";
            $args[] = $match_only_applied_guid;
        }

        $where_match_id = 32;
        if (intval($match_only_applied_id)) {
            $where_match_id = " app.id = ? ";
            $args[] = $match_only_applied_id;
        }

        $sql = "
            SELECT
               GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
               GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,    
               GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
               GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list, 
               GROUP_CONCAT( HEX(fp_owner.flow_project_guid) order by app.id) as flow_project_guid_list,   
               GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,  
               NULL as owning_entry_guid_list,
               GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,     
               GROUP_CONCAT( app.id order by app.id) as applied_id_list,
               app.tagged_flow_project_id as taggee_id,
               HEX(fp.flow_project_guid) as taggee_guid,
               fp.flow_project_title as tagged_title,
               '{$I(GeneralSearch::TYPE_PROJECT)}' as taggie_type
            FROM flow_applied_tags app
                INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                INNER JOIN flow_projects fp on app.tagged_flow_project_id = fp.id
                INNER JOIN flow_projects fp_owner on retag.flow_project_id = fp_owner.id
                INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND 
                  app.tagged_flow_project_id IS NOT NULL AND
                  $where_match_guid AND $where_match_id
            
            GROUP BY app.tagged_flow_project_id
        UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,
                GROUP_CONCAT( HEX(fp.flow_project_guid) order by app.id) as flow_project_guid_list,
                GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,  
                NULL as owning_entry_guid_list,
                GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,    
                GROUP_CONCAT( app.id order by app.id) as applied_id_list,   
                app.tagged_flow_user_id as taggee_id,
                HEX(fu.flow_user_guid) as taggee_guid,
                fu.flow_user_name as tagged_title,
                '{$I(GeneralSearch::TYPE_USER)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_users fu on app.tagged_flow_user_id = fu.id
                     INNER JOIN flow_projects fp on retag.flow_project_id = fp.id
                     INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_user_id IS NOT NULL
            GROUP BY app.tagged_flow_user_id
        UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,   
                GROUP_CONCAT( HEX(fp.flow_project_guid) order by app.id) as flow_project_guid_list,   
                GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,  
                NULL as owning_entry_guid_list,
                GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,   
                GROUP_CONCAT( app.id order by app.id) as applied_id_list, 
                app.tagged_flow_entry_id as taggee_id,
                HEX(fe.flow_entry_guid) as taggee_guid,      
                fe.flow_entry_title as tagged_title,   
                '{$I(GeneralSearch::TYPE_ENTRY)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_entries fe on app.tagged_flow_entry_id = fe.id
                     INNER JOIN flow_projects fp on fe.flow_project_id = fp.id
                     INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_entry_id IS NOT NULL
            GROUP BY tagged_flow_entry_id
        UNION
            SELECT
                GROUP_CONCAT( HEX(retag.flow_tag_guid) order by app.id) as tag_guid_list,
                GROUP_CONCAT( app.flow_tag_id order by app.id) as tag_id_list,   
                GROUP_CONCAT( app.created_at_ts order by app.id) as tagged_at_ts,
                GROUP_CONCAT( HEX(app.flow_applied_tag_guid) order by app.id) as applied_guid_list,   
                GROUP_CONCAT( HEX(fp.flow_project_guid) order by app.id) as flow_project_guid_list,   
                GROUP_CONCAT( HEX(admin_fu.flow_user_guid) order by app.id) as owning_user_guid_list,
                GROUP_CONCAT( HEX(fe.flow_entry_guid) order by app.id) as owning_entry_guid_list,
                GROUP_CONCAT( admin_fu.flow_user_name order by app.id) as owning_user_name_list,   
                GROUP_CONCAT( app.id order by app.id) as applied_id_list, 
                app.tagged_flow_entry_node_id as taggee_id,
                HEX(ne.entry_node_guid) as taggee_guid,      
                ne.bb_tag_name as tagged_title,   
                '{$I(GeneralSearch::TYPE_ENTRY_NODE)}' as taggie_type
            FROM flow_applied_tags app
                     INNER JOIN flow_tags retag ON retag.id = app.flow_tag_id
                     INNER JOIN flow_entry_nodes ne on app.tagged_flow_entry_node_id = ne.id
                     INNER JOIN flow_entries fe on ne.flow_entry_id = fe.id
                     INNER JOIN flow_projects fp on fe.flow_project_id = fp.id
                     INNER JOIN flow_users admin_fu on fp.admin_flow_user_id = admin_fu.id
            WHERE app.flow_tag_id in ($comma_delimited_tag_ids) AND app.tagged_flow_entry_node_id IS NOT NULL
            GROUP BY tagged_flow_entry_node_id
        ";

        $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
        $ret = [];
        foreach ($res as $row) {
            $tag_guid_array = explode(",",$row->tag_guid_list);
            $tag_id_array = explode(",",$row->tag_id_list);
            $created_at_array = explode(",",$row->tagged_at_ts);
            $applied_guid_array = explode(",",$row->applied_guid_list);
            $applied_id_array = explode(",",$row->applied_id_list);
            $flow_project_guid_array = explode(",",$row->flow_project_guid_list);
            $owning_user_name_list_array = explode(",",$row->owning_user_name_list);
            $owning_user_guid_list_array = explode(",",$row->owning_user_guid_list);
            $owning_entry_guid_list_array = $row->owning_entry_guid_list? explode(",",$row->owning_entry_guid_list) : [];
            if (count($tag_guid_array) !== count($created_at_array) ||
                count($tag_guid_array) !== count($tag_id_array) ||
                count($applied_id_array) !== count($tag_id_array) ||
                count($tag_guid_array) !== count($applied_guid_array) ||
                count($tag_guid_array) !== count($owning_user_guid_list_array) ||
                count($tag_guid_array) !== count($owning_user_name_list_array) ||
                count($tag_guid_array) !== count($flow_project_guid_array)
            ) {
                throw new RuntimeException(
                    "[get_applied_tags] guid, created_at , id, applied_id list does not have same numbers, check nulls");
            }
            for($i = 0; $i< count($applied_guid_array); $i++) {
                $node = new FlowAppliedTag();
                $node->created_at_ts = (int)$created_at_array[$i];
                $node->flow_tag_guid = $tag_guid_array[$i];
                $node->flow_tag_id = $tag_id_array[$i];
                $node->flow_applied_tag_guid = $applied_guid_array[$i];
                $node->id = $applied_id_array[$i];
                $node->tagged_title = $row->tagged_title;
                $node->flow_applied_tag_owner_user_guid = $owning_user_guid_list_array[$i];
                $node->flow_project_guid = $flow_project_guid_array[$i];
                $node->flow_applied_tag_owner_user_name = $owning_user_name_list_array[$i];

                switch ($row->taggie_type) {
                    case GeneralSearch::TYPE_ENTRY: {
                        $node->tagged_flow_entry_guid = $row->taggee_guid;
                        $node->tagged_flow_entry_id = $row->taggee_id;
                        break;
                    }

                    case GeneralSearch::TYPE_ENTRY_NODE: {
                        $node->tagged_flow_entry_node_guid = $row->taggee_guid;
                        $node->tagged_flow_entry_node_id = $row->taggee_id;
                        $node->tagged_flow_entry_guid = $owning_entry_guid_list_array[0]?? null;
                        if (!$node->tagged_flow_entry_guid) {
                            throw new RuntimeException(
                                "[get_applied_tags] Could not get owning entry guid for node ".$node->tagged_flow_entry_node_id);
                        }
                        break;
                    }
                    case GeneralSearch::TYPE_USER: {
                        $node->tagged_flow_user_guid = $row->taggee_guid;
                        $node->tagged_flow_user_id = $row->taggee_id;
                        break;
                    }
                    case GeneralSearch::TYPE_PROJECT:  {
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

    /**
     * @throws Exception
     */
    public function save() :void {
        $db = static::get_connection();

        if(  !$this->flow_tag_id) {
            throw new InvalidArgumentException("When saving an applied, need a tag_id");
        }


        if (!$this->tagged_flow_entry_id && $this->tagged_flow_entry_guid) {
            $this->tagged_flow_entry_id = $db->cell(
                "SELECT id  FROM flow_entries WHERE flow_entry_guid = UNHEX(?)",
                $this->tagged_flow_entry_guid);
        }

        if (!$this->tagged_flow_entry_node_id && $this->tagged_flow_entry_node_guid) {
            $this->tagged_flow_entry_node_id = $db->cell(
                "SELECT id  FROM flow_entry_nodes WHERE entry_node_guid = UNHEX(?)",
                $this->tagged_flow_entry_node_guid);
        }

        if (!$this->tagged_flow_project_id && $this->tagged_flow_project_guid) {
            $this->tagged_flow_project_id = $db->cell(
                "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                $this->tagged_flow_project_guid);
        }

        if (!$this->tagged_flow_user_id && $this->tagged_flow_user_guid) {
            $this->tagged_flow_user_id = $db->cell(
                "SELECT id  FROM flow_users WHERE flow_user_guid = UNHEX(?)",
                $this->tagged_flow_user_guid);
        }

        if (!($this->tagged_flow_user_id || $this->tagged_flow_project_id ||
                $this->tagged_flow_entry_id || $this->tagged_flow_entry_node_id)
        ) {
            throw new InvalidArgumentException("When saving an applied, it needs to be tagging something" );
        }

        $saving_info = [
            'flow_tag_id' => $this->flow_tag_id ,
            'tagged_flow_entry_id' => $this->tagged_flow_entry_id ,
            'tagged_flow_entry_node_id' => $this->tagged_flow_entry_node_id ,
            'tagged_flow_project_id' => $this->tagged_flow_project_id ,
            'tagged_flow_user_id' => $this->tagged_flow_user_id
        ];

        if ($this->id) {

            $db->update('flow_applied_tags',$saving_info,[
                'id' => $this->id
            ]);

        }
        elseif ($this->flow_applied_tag_guid) {
            $insert_sql = "
                    INSERT INTO flow_applied_tags(flow_tag_id, tagged_flow_entry_id,tagged_flow_entry_node_id, tagged_flow_user_id,
                                                  tagged_flow_project_id, created_at_ts, flow_applied_tag_guid)  
                    VALUES (?,?,?,?,?,?,UNHEX(?))  
                    ON DUPLICATE KEY UPDATE 
                                            flow_tag_id             = VALUES(flow_tag_id) ,       
                                            tagged_flow_entry_id    = VALUES(tagged_flow_entry_id) ,       
                                            tagged_flow_entry_node_id    = VALUES(tagged_flow_entry_node_id) ,       
                                            tagged_flow_user_id     = VALUES(tagged_flow_user_id) ,       
                                            tagged_flow_project_id  = VALUES(tagged_flow_project_id)        
                ";
            $insert_params = [
                $this->flow_tag_id,
                $this->tagged_flow_entry_id,
                $this->tagged_flow_entry_node_id,
                $this->tagged_flow_user_id,
                $this->tagged_flow_project_id,
                $this->created_at_ts,
                $this->flow_applied_tag_guid
            ];
            $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
            $this->flow_tag_id = $db->lastInsertId();
        }
        else {
            $db->insert('flow_applied_tags',$saving_info);
            $this->id = $db->lastInsertId();
        }
    }//end function save


    public function delete_applied() {
        $db = static::get_connection();
        if ($this->id) {
            $db->delete('flow_applied_tags',['id'=>$this->id]);
        } else if($this->flow_applied_tag_guid) {
            $sql = "DELETE FROM flow_applied_tags WHERE flow_applied_tag_guid = UNHEX(?)";
            $params = [$this->flow_applied_tag_guid];
            $db->safeQuery($sql, $params, PDO::FETCH_BOTH, true);
        } else {
            throw new LogicException("Cannot delete flow_applied_tags without an id or guid");
        }


    } //end function delete


    public static function reconstitute($args,FlowTag $parent_tag) : FlowAppliedTag {

        if (is_numeric($args)) { //expected to be applied id
            $ret_array = static::get_applied_tags([$parent_tag->flow_tag_id],null,$args);
            if (empty($ret_array) || empty($ret_array[$parent_tag->flow_tag_guid])) {
                throw new InvalidArgumentException("cannot find applied using the id of $args");
            }
            $ret = $ret_array[$parent_tag->flow_tag_guid][0]; //only one
        } elseif (is_string($args)) { //args expected to be applied guid
            $ret_array = static::get_applied_tags([$parent_tag->flow_tag_id],$args);
            if (empty($ret_array) || empty($ret_array[$parent_tag->flow_tag_guid])) {
                throw new InvalidArgumentException("cannot find applied using the guid of $args");
            }
            $ret = $ret_array[$parent_tag->flow_tag_guid][0]; //only one
        } elseif (is_array($args) || is_object($args)) {
            $node = new FlowAppliedTag($args);
            if ($node->flow_applied_tag_guid) {
                $ret_array = static::get_applied_tags([$parent_tag->flow_tag_id],$node->flow_applied_tag_guid);
                if (empty($ret_array) || empty($ret_array[$parent_tag->flow_tag_guid])) {
                    throw new InvalidArgumentException("cannot find applied using the guid of $node->flow_applied_tag_guid");
                }
                $ret = $ret_array[$parent_tag->flow_tag_guid][0];
                $ret->tagged_flow_entry_id = $node->tagged_flow_entry_id;
                $ret->tagged_flow_entry_guid = $node->tagged_flow_entry_guid;

                $ret->tagged_flow_entry_node_id = $node->tagged_flow_entry_node_id;
                $ret->tagged_flow_entry_node_guid = $node->tagged_flow_entry_node_guid;

                $ret->tagged_flow_project_id = $node->tagged_flow_project_id;
                $ret->tagged_flow_project_guid = $node->tagged_flow_project_guid;

                $ret->tagged_flow_user_id = $node->tagged_flow_user_id;
                $ret->tagged_flow_user_guid = $node->tagged_flow_user_guid;
            } else {
                $ret = $node;
            }

        } else {
            throw new InvalidArgumentException("Could not figure out Applied from the data of $args");
        }

        return $ret;
    }

    /**
     * @param string[] $guid_list
     * @return bool
     */
    public function has_at_least_one_of_these_tagged_guid(array $guid_list) :bool{
        foreach ($guid_list as $guid) {
            if ($this->tagged_flow_user_guid === $guid) {return true;}
            if ($this->tagged_flow_project_guid === $guid) {return true;}
            if ($this->tagged_flow_entry_guid === $guid) {return true;}
            if ($this->tagged_flow_entry_node_guid === $guid) {return true;}
        }
        return false;
    }


    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids() : array
    {
        $ret = [];
        if (empty($this->flow_tag_id) && $this->flow_tag_guid) { $ret[] = $this->flow_tag_guid;}
        if (empty($this->tagged_flow_entry_id) && $this->tagged_flow_entry_guid) { $ret[] = $this->tagged_flow_entry_id;}
        if (empty($this->tagged_flow_entry_node_id) && $this->tagged_flow_entry_node_guid) { $ret[] = $this->tagged_flow_entry_node_id;}
        if (empty($this->tagged_flow_user_id) && $this->tagged_flow_user_guid) { $ret[] = $this->tagged_flow_user_guid;}
        if (empty($this->tagged_flow_project_id) && $this->tagged_flow_project_guid) { $ret[] = $this->tagged_flow_project_guid;}

        return $ret;
    }

    /**
     * @@param  array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids)
    {
        if (empty($this->flow_tag_id) && $this->flow_tag_guid) {
            $this->flow_tag_id = $guid_map_to_ids[$this->flow_tag_guid] ?? null;}
        if (empty($this->tagged_flow_entry_id) && $this->tagged_flow_entry_guid) {
            $this->tagged_flow_entry_id = $guid_map_to_ids[$this->tagged_flow_entry_guid] ?? null;}
        if (empty($this->tagged_flow_entry_node_id) && $this->tagged_flow_entry_node_guid) {
            $this->tagged_flow_entry_node_id = $guid_map_to_ids[$this->tagged_flow_entry_node_guid] ?? null;}
        if (empty($this->tagged_flow_user_id) && $this->tagged_flow_user_guid) {
            $this->tagged_flow_user_id = $guid_map_to_ids[$this->tagged_flow_user_guid] ?? null;}
        if (empty($this->tagged_flow_project_id) && $this->tagged_flow_project_guid) {
            $this->tagged_flow_project_id= $guid_map_to_ids[$this->tagged_flow_project_guid] ?? null;}
    }


}