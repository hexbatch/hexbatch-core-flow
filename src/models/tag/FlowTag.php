<?php

namespace app\models\tag;


use app\models\base\FlowBase;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
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
        return [
            "flow_tag_guid" => $this->flow_tag_guid,
            "parent_tag_guid" => $this->parent_tag_guid,
            "flow_project_guid" => $this->flow_project_guid,
            "flow_user_guid" => $this->flow_user_guid,
            "created_at_ts" => $this->tag_created_at_ts,
            "flow_tag_name" => $this->flow_tag_name,
            "attributes" => $this->attributes,
            "standard_attributes" => $standard
        ];
    }


    public function __construct($object=null){
        $this->attributes = [];
        if (empty($object)) {
            $this->flow_tag_id = null ;
            $this->flow_project_id = null ;
            $this->parent_tag_id = null ;
            $this->tag_created_at_ts = null ;
            $this->flow_tag_guid = null ;
            $this->flow_tag_name = null ;
            $this->flow_project_guid = null ;
            $this->parent_tag_guid = null ;
            $this->flow_user_guid = null ;
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

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
        $where_tag = 4;
        if ($search->project_guid) {
            $where_project = "driver_project.flow_project_guid = UNHEX(?)";
            $args[] = $search->project_guid;
        }

        if ($search->tag_guid) {
            $where_tag = "driver_tag.flow_tag_guid = UNHEX(?)";
            $args[] = $search->tag_guid;
        }

        $start_place = ($page - 1) * $page_size;


        $db = static::get_connection();

        $sql = "SELECT 
                    t.id                                    as flow_tag_id,
                    t.flow_project_id,
                    t.parent_tag_id,
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
                    SELECT driver_tag.id 
                    FROM flow_tags driver_tag
                    INNER JOIN flow_projects driver_project ON driver_project.id = driver_tag.flow_project_id
                    WHERE 1 
                    AND $where_project  
                    AND $where_tag  
                    LIMIT $start_place , $page_size
                )  as driver   
                LEFT JOIN flow_tags parent_t ON parent_t.id = t.id 
                INNER JOIN flow_projects project ON project.id = t.flow_project_id 
                INNER JOIN flow_users admin_user ON admin_user.id = project.admin_flow_user_id 
                LEFT JOIN flow_tag_attributes attribute on attribute.flow_tag_id = t.id
                LEFT JOIN flow_applied_tags applied on applied.id = attribute.flow_applied_tag_id
                LEFT JOIN flow_entries point_entry on attribute.points_to_entry_id = point_entry.id
                LEFT JOIN flow_users point_user on attribute.points_to_user_id = point_user.id 
                LEFT JOIN flow_projects point_project on attribute.points_to_project_id = point_project.id 
                WHERE 1 
                ";

        try {
            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

            $ret = [];

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
                }

                $attribute_node = new FlowTagAttribute($row);
                if ($attribute_node->flow_tag_attribute_id) {
                    $tag_node->attributes[] = $attribute_node;
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