<?php

namespace app\models\tag;

use app\models\base\FlowBase;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;

class FlowTagAttribute extends FlowBase implements JsonSerializable {

    const LENGTH_ATTRIBUTE_NAME = 40;
    const STD_ATTR_COLOR = 'color';
    const STD_ATTR_BACKGROUND_COLOR = 'background_color';

    const STANDARD_ATTRIBUTES = [
      self::STD_ATTR_BACKGROUND_COLOR,
      self::STD_ATTR_COLOR,
    ];

    public ?int $flow_tag_attribute_id;
    public ?int $flow_tag_id;
    public ?int $flow_applied_tag_id;
    public ?int $points_to_entry_id;
    public ?int $points_to_user_id;
    public ?int $points_to_project_id;
    public ?int $applied_created_at_ts;
    public ?string $flow_tag_attribute_guid;
    public ?string $tag_attribute_name;
    public ?string$tag_attribute_long;
    public ?string $tag_attribute_text;

    public ?string $flow_tag_guid;
    public ?string $flow_applied_tag_guid;
    public ?string $points_to_flow_entry_guid;
    public ?string $points_to_flow_user_guid;
    public ?string $points_to_flow_project_guid;

    public bool $is_standard_attribute;


    public function __construct($object=null){
        $this->is_standard_attribute = false;
        if (empty($object)) {
            $this->flow_tag_attribute_id = null ;
            $this->flow_tag_id = null ;
            $this->flow_applied_tag_id = null ;
            $this->points_to_entry_id = null ;
            $this->points_to_user_id = null ;
            $this->points_to_project_id = null ;
            $this->applied_created_at_ts = null ;
            $this->flow_tag_attribute_guid = null ;
            $this->tag_attribute_name = null ;
            $this->tag_attribute_long = null ;
            $this->tag_attribute_text = null ;

            $this->flow_tag_guid = null ;
            $this->flow_applied_tag_guid = null;
            $this->points_to_flow_entry_guid = null ;
            $this->points_to_flow_user_guid = null ;
            $this->points_to_flow_project_guid = null ;
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

        if (in_array($this->tag_attribute_name,static::STANDARD_ATTRIBUTES)) {
            $this->is_standard_attribute = true;
        }

    }


    public static function check_valid_name($words) : bool  {
        return static::minimum_check_valid_name($words,static::LENGTH_ATTRIBUTE_NAME);
    }


    /**
     * @throws Exception
     */
    public function save() :void {

        try {
            if (empty($this->tag_attribute_name)) {
                throw new InvalidArgumentException("Attribute Name cannot be empty");
            }


            $b_match = static::check_valid_name($this->tag_attribute_name);
            if (!$b_match) {
                $max_len = static::LENGTH_ATTRIBUTE_NAME;
                throw new InvalidArgumentException(
                    "Attribute name either empty OR invalid! ".
                    "First character cannot be a number. Name Cannot be greater than $max_len. ".
                    " Name cannot be a hex number greater than 25 and cannot be a decimal number");
            }


            $db = static::get_connection();


            if(  !($this->flow_tag_id|| $this->flow_applied_tag_id)) {
                throw new InvalidArgumentException("When saving an attribute, need a tag_id or applied_tag_id");
            }

            if (!$this->points_to_entry_id && $this->points_to_flow_entry_guid) {
                $this->points_to_entry_id = $db->cell(
                    "SELECT id  FROM flow_entries WHERE flow_entry_guid = UNHEX(?)",
                    $this->points_to_flow_entry_guid);
            }

            if (!$this->points_to_project_id && $this->points_to_flow_project_guid) {
                $this->points_to_project_id = $db->cell(
                    "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                    $this->points_to_flow_project_guid);
            }

            if (!$this->points_to_user_id && $this->points_to_flow_user_guid) {
                $this->points_to_user_id = $db->cell(
                    "SELECT id  FROM flow_users WHERE flow_user_guid = UNHEX(?)",
                    $this->points_to_flow_user_guid);
            }

            if (empty($this->tag_attribute_text)) {
                $this->tag_attribute_text = null;
            }


            $saving_info = [
                'flow_tag_id' => $this->flow_tag_id ,
                'flow_applied_tag_id' => $this->flow_applied_tag_id ,
                'points_to_entry_id' => $this->points_to_entry_id ,
                'points_to_user_id' => $this->points_to_user_id ,
                'points_to_project_id' => $this->points_to_project_id ,
                'tag_attribute_name' => $this->tag_attribute_name ,
                'tag_attribute_long' => $this->tag_attribute_long ,
                'tag_attribute_text' => $this->tag_attribute_text
            ];

            if ($this->flow_tag_attribute_id) {

                $db->update('flow_tag_attributes',$saving_info,[
                    'id' => $this->flow_tag_attribute_id
                ]);

            } else {
                $db->insert('flow_tags',$saving_info);
                $this->flow_tag_attribute_id = $db->lastInsertId();
            }

            if (!$this->flow_tag_attribute_guid) {
                $this->flow_tag_attribute_guid = $db->cell(
                    "SELECT HEX(flow_tag_attribute_guid) as flow_tag_attribute_guid FROM flow_tag_attributes WHERE id = ?",
                    $this->flow_tag_attribute_id);

                if (!$this->flow_tag_attribute_guid) {
                    throw new RuntimeException("Could not get attribute guid using id of ". $this->flow_tag_attribute_id);
                }
            }




        } catch (Exception $e) {
            static::get_logger()->alert("Attribute model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }


    
    public function jsonSerialize(): array
    {
        return [
            "flow_tag_attribute_guid" => $this->flow_tag_attribute_guid,
            "flow_tag_guid" => $this->flow_tag_guid,
            "flow_applied_tag_guid" => $this->flow_applied_tag_guid,
            "points_to_flow_entry_guid" => $this->points_to_flow_entry_guid,
            "points_to_flow_user_guid" => $this->points_to_flow_user_guid,
            "points_to_flow_project_guid" => $this->points_to_flow_project_guid,
            "tag_attribute_name" => $this->tag_attribute_name,
            "tag_attribute_long" => $this->tag_attribute_long,
            "tag_attribute_text" => $this->tag_attribute_text,
            "created_at_ts" => $this->applied_created_at_ts,
            "is_standard_attribute" => $this->is_standard_attribute

        ];
    }
}