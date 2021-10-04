<?php

namespace app\models\tag;


use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;


class FlowTag extends FlowBase implements JsonSerializable {

    const DEFAULT_TAG_PAGE_SIZE = 25;
    const LENGTH_TAG_NAME = 40;

    public ?int $flow_tag_id;
    public ?int $flow_project_id;
    public ?int $parent_tag_id;
    public ?int $tag_created_at_ts;
    public ?string $flow_tag_guid;
    public ?string $flow_tag_name;


    public ?string $flow_project_guid;
    public ?string $flow_user_guid;
    public ?string $parent_tag_guid;

    /**
     * @var FlowTagAttribute[] $attributes
     */
    public array $attributes;

    /**
     * @var FlowTag|null $flow_tag_parent
     */
    public ?FlowTag $flow_tag_parent;

    public ?int $child_tag_id;


    public function jsonSerialize(): array
    {
        $standard = [];
        foreach ($this->attributes as $attribute) {
            if ($attribute->is_standard_attribute) {
                $standard[$attribute->tag_attribute_name] = $attribute->tag_attribute_text;
            }
        }

        foreach (FlowTagAttribute::STANDARD_ATTRIBUTES as $std) {
            if (!array_key_exists($std,$standard)) {
                $standard[$std] = null;
            }
        }
        $parent_serializied = null;
        if ($this->flow_tag_parent) {
            $parent_serializied = $this->flow_tag_parent->jsonSerialize();
        }
        return [
            "flow_tag_guid" => $this->flow_tag_guid,
            "parent_tag_guid" => $this->parent_tag_guid,
            "flow_project_guid" => $this->flow_project_guid,
            "flow_user_guid" => $this->flow_user_guid,
            "created_at_ts" => $this->tag_created_at_ts,
            "flow_tag_name" => $this->flow_tag_name,
            "attributes" => $this->attributes,
            "standard_attributes" => $standard,
            "flow_tag_parent" => $parent_serializied
        ];
    }


    public function __construct($object=null){
        $this->attributes = [];
        $this->flow_tag_id = null ;
        $this->flow_project_id = null ;
        $this->parent_tag_id = null ;
        $this->tag_created_at_ts = null ;
        $this->flow_tag_guid = null ;
        $this->flow_tag_name = null ;
        $this->flow_project_guid = null ;
        $this->parent_tag_guid = null ;
        $this->flow_user_guid = null ;
        $this->flow_tag_parent = null;

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

        if (is_object($object) && property_exists($object,'attributes') && is_array($object->attributes)) {
            $attributes_to_copy = $object->attributes;
        } elseif (is_array($object) && array_key_exists('attributes',$object) && is_array($object['attributes'])) {
            $attributes_to_copy  = $object['attributes'];
        } else {
            $attributes_to_copy = [];
        }
        $this->attributes = [];
        if (count($attributes_to_copy)) {
            foreach ($attributes_to_copy as $att) {
                $this->attributes[] = new FlowTagAttribute($att);
            }
        }


        if (is_object($object) && property_exists($object,'flow_tag_parent') && !empty($object->flow_tag_parent)) {
            $parent_to_copy = $object->flow_tag_parent;
        } elseif (is_array($object) && array_key_exists('flow_tag_parent',$object) && !empty($object['flow_tag_parent'])) {
            $parent_to_copy  = $object['flow_tag_parent'];
        } else {
            $parent_to_copy = null;
        }
        $this->flow_tag_parent = null;
        if ($parent_to_copy) {
            $this->flow_tag_parent = new FlowTag($parent_to_copy);
        }

    }

    /**
     * @return $this
     * @throws Exception
     */
    public function clone_refresh() : FlowTag {
        if (empty($this->flow_tag_id) && empty($this->flow_tag_guid)) {
            $me = new FlowTag($this); //new to db
            return $me;
        }
        $search = new FlowTagSearchParams();
        if ($this->flow_tag_guid) {
            $search->tag_guids[] = $this->flow_tag_guid;
        } elseif ($this->flow_tag_id) {
            $search->tag_ids[] = $this->flow_tag_id;
        }

        $me_array = static::get_tags($search);
        if (empty($me_array)) {
            throw new InvalidArgumentException("Tag is not found from guid of $this->flow_tag_guid or id of $this->flow_tag_id");
        }
        $me = $me_array[0];
        return $me;
    }

    /**
     * @return FlowTag
     * @throws Exception
     */
    public function clone_with_missing_data() : FlowTag {

        $me = $this->clone_refresh();
        if (empty($me->flow_tag_id) && empty($me->flow_tag_guid)) {
            return $me;
        }
        //clear out the settable ids in the $me, if not set in this
        //set new data for this, overwriting the old
        if ($me->parent_tag_id && !$this->parent_tag_guid) {
            $me->parent_tag_id = null;
            $me->parent_tag_guid = null;
        }


        $me->flow_tag_name = $this->flow_tag_name;

        /**
         * @var array<string, FlowTagAttribute> $this_attribute_map
         */
        $this_attribute_map = [];

        foreach ($this->attributes as $attribute) {
            if ($attribute->flow_tag_attribute_guid) {
                $this_attribute_map[$attribute->flow_tag_attribute_guid] = $attribute;
            }
        }

        $me_attributes_filtered = [];
        //for each attribute in the $me, that is not in the $this, delete it
        foreach ($me->attributes as  $me_attribute) {
            if (array_key_exists($me_attribute->flow_tag_attribute_guid,$this_attribute_map)) {
                //clear out the settable ids in the $me::attribute, if not set in this::attribute
                //set new data for $me::attribute, overwriting the old

                /**
                 * @var FlowTagAttribute $this_attribute
                 */
                $this_attribute = $this_attribute_map[$me_attribute->flow_tag_attribute_guid];

                if ($me_attribute->points_to_entry_id && !$this_attribute->points_to_flow_entry_guid) {
                    $me_attribute->points_to_entry_id = null;
                    $me_attribute->points_to_flow_entry_guid = null;
                }

                if ($me_attribute->points_to_user_id && !$this_attribute->points_to_flow_user_guid) {
                    $me_attribute->points_to_user_id = null;
                    $me_attribute->points_to_flow_user_guid = null;
                }

                if ($me_attribute->points_to_project_id && !$this_attribute->points_to_flow_project_guid) {
                    $me_attribute->points_to_project_id = null;
                    $me_attribute->points_to_flow_project_guid = null;
                }

                $me_attribute->points_to_flow_entry_guid = empty($this_attribute->points_to_flow_entry_guid) ? null:  $this_attribute->points_to_flow_entry_guid ;
                $me_attribute->points_to_flow_user_guid = empty($this_attribute->points_to_flow_user_guid)? null : $this_attribute->points_to_flow_user_guid;
                $me_attribute->points_to_flow_project_guid = empty($this_attribute->points_to_flow_project_guid)? null : $this_attribute->points_to_flow_project_guid ;

                $me_attribute->tag_attribute_name = $this_attribute->tag_attribute_name;

                if ($this_attribute->tag_attribute_long !== 0 && $this_attribute->tag_attribute_long !== '0' && empty($this_attribute->tag_attribute_long)) {
                    $this_attribute->tag_attribute_long =  null;
                } else {
                    $me_attribute->tag_attribute_long =  intval($this_attribute->tag_attribute_long);
                }

                $me_attribute->tag_attribute_text = empty($this_attribute->tag_attribute_text)? null : $this_attribute->tag_attribute_text;

                $me_attributes_filtered[] = $me_attribute;
                unset($this_attribute_map[$me_attribute->flow_tag_attribute_guid]);
            }
        }

        //add remaining new attributes that have guids
        foreach ($this_attribute_map as $this_attribute_guid => $this_attribute) {
            WillFunctions::will_do_nothing($this_attribute_guid);
            $me_attributes_filtered[] = $this_attribute;
        }

        //add in new attributes with no guid
        foreach ($this->attributes as $attribute) {
            if (!$attribute->flow_tag_attribute_guid) {
                $me_attributes_filtered[] = $attribute;
            }
        }

        $me->attributes = $me_attributes_filtered;
        return $me;
    }

    /**
     * @return FlowTag[]
     * @throws
     */
    public static function get_tags(FlowTagSearchParams $search,
                                    int     $page = 1,
                                    int     $page_size =  self::DEFAULT_TAG_PAGE_SIZE): array
    {

        $args = [];
        $where_project = 2;
        $where_tag_guid = 4;
        $where_tag_id = 8;
        if ($search->project_guid) {
            $where_project = "driver_project.flow_project_guid = UNHEX(?)";
            $args[] = $search->project_guid;
        }

        if (count($search->tag_guids)) {
            $wrapped_guids = $search->tag_guids;
            array_walk($wrapped_guids, function(&$x)  use(&$args) {$args[] = $x; $x = "UNHEX(?)";});
            $comma_delimited_tag_guids = implode(",",$wrapped_guids);
            $where_tag_guid = "driver_tag.flow_tag_guid in ($comma_delimited_tag_guids)";
        }

        if (count($search->tag_ids)) {
            $cast_ids = $search->tag_ids;
            array_walk($cast_ids, function(&$x)  use(&$args) {$args[] = intval($x);$x = "?";});
            $comma_delimited_ids = implode(",",$cast_ids);
            $where_tag_id = "driver_tag.id in ($comma_delimited_ids)";
        }

        $start_place = ($page - 1) * $page_size;


        $db = static::get_connection();

        $sql = "SELECT 
                    t.id                                    as flow_tag_id,
                    t.flow_project_id,
                    t.parent_tag_id,
                    driver.child_tag_id,                  
                    t.created_at_ts                         as tag_created_at_ts,
                    t.flow_tag_name,
                    HEX(t.flow_tag_guid)                    as flow_tag_guid,
                    HEX(parent_t.flow_tag_guid)             as parent_tag_guid,
                    HEX(project.flow_project_guid)          as flow_project_guid,
                    HEX(admin_user.flow_user_guid)          as flow_user_guid,
       
                    attribute.id                            as flow_tag_attribute_id,
                    attribute.flow_applied_tag_id,
                    attribute.points_to_entry_id,
                    attribute.points_to_user_id,
                    attribute.points_to_project_id,
                    attribute.created_at_ts                 as applied_created_at_ts,
                    HEX(attribute.flow_tag_attribute_guid)  as flow_tag_attribute_guid,
                    attribute.tag_attribute_name,
                    attribute.tag_attribute_long,
                    attribute.tag_attribute_text,

                    HEX(applied.flow_applied_tag_guid)      as flow_applied_tag_guid,
                    HEX(point_entry.flow_entry_guid)        as points_to_flow_entry_guid,
                    HEX(point_user.flow_user_guid)          as points_to_flow_user_guid,
                    HEX(point_project.flow_project_guid)    as points_to_flow_project_guid
    
    
       
                FROM flow_tags t
                INNER JOIN  (
                    
                    
                    WITH RECURSIVE cte AS (
                        (
                            SELECT driver_tag.id as flow_tag_id , driver_tag.parent_tag_id, cast(null as SIGNED ) as child_tag_id
                            FROM flow_tags driver_tag
                            INNER JOIN flow_projects driver_project ON driver_project.id = driver_tag.flow_project_id
                            WHERE 1 
                                AND $where_project  
                                AND $where_tag_guid  
                                AND $where_tag_id  
                                LIMIT $start_place , $page_size
                        )
                        UNION
                        DISTINCT
                        (
                            SELECT parent_tag.id as flow_tag_id, parent_tag.parent_tag_id, c.flow_tag_id as child_tag_id
                            FROM cte c
                            INNER JOIN flow_tags parent_tag ON parent_tag.id = c.parent_tag_id
                        )
                    )
                    SELECT cte.flow_tag_id, cte.parent_tag_id, cte.child_tag_id FROM cte
                    
                    
                )  as driver ON driver.flow_tag_id = t.id  
                LEFT JOIN flow_tags parent_t ON parent_t.id = t.parent_tag_id 
                INNER JOIN flow_projects project ON project.id = t.flow_project_id 
                INNER JOIN flow_users admin_user ON admin_user.id = project.admin_flow_user_id 
                LEFT JOIN flow_tag_attributes attribute on attribute.flow_tag_id = t.id
                LEFT JOIN flow_applied_tags applied on applied.id = attribute.flow_applied_tag_id
                LEFT JOIN flow_entries point_entry on attribute.points_to_entry_id = point_entry.id
                LEFT JOIN flow_users point_user on attribute.points_to_user_id = point_user.id 
                LEFT JOIN flow_projects point_project on attribute.points_to_project_id = point_project.id 
                WHERE 1 
                ORDER BY child_tag_id,flow_tag_id,flow_tag_attribute_id ASC
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
                    if (empty($tag_node->child_tag_id)) {
                        $ret[] = $tag_node; //top level only
                    }

                    $map_all_tags_by_id[$map_prefix.$tag_node->flow_tag_id] = $tag_node;
                }

                $attribute_node = new FlowTagAttribute($row);
                if ($attribute_node->flow_tag_attribute_id) {
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

            return  $ret;
        } catch (Exception $e) {
            static::get_logger()->alert("FlowTag model cannot get_tags ",['exception'=>$e]);
            throw $e;
        }

    }

    public static function check_valid_name($words) : bool  {
        return static::minimum_check_valid_name($words,static::LENGTH_TAG_NAME);
    }

    /**
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false) :void {
        $db = null;

        try {
            if (empty($this->flow_tag_name)) {
                throw new InvalidArgumentException("Project Title cannot be empty");
            }


            $b_match = static::check_valid_name($this->flow_tag_name);
            if (!$b_match) {
                $max_len = static::LENGTH_TAG_NAME;
                throw new InvalidArgumentException(
                    "Tag name either empty OR invalid! ".
                    "First character cannot be a number. Name Cannot be greater than $max_len. ".
                    " Title cannot be a hex number greater than 25 and cannot be a decimal number");
            }


            $db = static::get_connection();



            if (!$this->flow_project_id && $this->flow_project_guid) {
                $this->flow_project_id = $db->cell(
                    "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                    $this->flow_project_guid);
            }

            if(  !$this->flow_project_id) {
                throw new InvalidArgumentException("When saving a tag for the first time, need its project id or guid");
            }

            if (!$this->parent_tag_id && $this->parent_tag_guid) {
                $this->parent_tag_id = $db->cell(
                    "SELECT id  FROM flow_tags WHERE flow_tag_guid = UNHEX(?)",
                    $this->parent_tag_guid);
            }

            if (empty($this->parent_tag_id)) {$this->parent_tag_id = null;}


            $save_info = [
                'flow_project_id' => $this->flow_project_id,
                'parent_tag_id' => $this->parent_tag_id,
                'flow_tag_name' => $this->flow_tag_name
            ];

            if ($b_do_transaction) {$db->beginTransaction();}
            if ($this->flow_project_guid) {

                $db->update('flow_tags',$save_info,[
                    'id' => $this->flow_tag_id
                ]);

            } else {
                $db->insert('flow_tags',$save_info);
                $this->flow_tag_id = $db->lastInsertId();
            }

            if (!$this->flow_tag_guid) {
                $this->flow_tag_guid = $db->cell(
                    "SELECT HEX(flow_tag_guid) as flow_tag_guid FROM flow_tags WHERE id = ?",
                    $this->flow_tag_id);

                if (!$this->flow_tag_guid) {
                    throw new RuntimeException("Could not get tag guid using id of ". $this->flow_tag_id);
                }
            }

            foreach ($this->attributes as $attribute) {
                $attribute->flow_tag_id = $this->flow_tag_id;
                $attribute->flow_applied_tag_id = null;
                $attribute->save();
            }

            if ($b_do_transaction) {$db->commit(); }


        } catch (Exception $e) {
            if ($b_do_transaction && $db) { $db->rollBack(); }
            static::get_logger()->alert("Tag model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }

}