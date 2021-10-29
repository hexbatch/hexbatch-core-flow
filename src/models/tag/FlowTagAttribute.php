<?php

namespace app\models\tag;

use app\hexlet\JsonHelper;
use app\models\base\FlowBase;
use app\models\tag\brief\BriefFlowTagAttribute;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;
use Slim\Interfaces\RouteParserInterface;

class FlowTagAttribute extends FlowBase implements JsonSerializable {

    const LENGTH_ATTRIBUTE_NAME = 40;


    public ?int $flow_tag_attribute_id;
    public ?int $flow_tag_id;
    public ?int $flow_applied_tag_id;
    public ?int $points_to_entry_id;
    public ?int $points_to_user_id;
    public ?int $points_to_project_id;
    public ?int $attribute_created_at_ts;
    public ?int $attribute_updated_at_ts;
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
    public ?bool $is_inherited;

    public ?string $points_to_title;
    public ?string $points_to_admin_name;
    public ?string $points_to_admin_guid;
    public ?string $points_to_url;

    public function has_enough_data_set() :bool {
        if (!$this->flow_tag_id) {return false;}
        if (!$this->tag_attribute_name) {return false;}
        return true;
    }

    public function update_fields_with_public_data(FlowTagAttribute $attribute) {
        $this->tag_attribute_name = $attribute->tag_attribute_name ;

        $this->tag_attribute_long = $attribute->tag_attribute_long ;
        $this->tag_attribute_text = $attribute->tag_attribute_text ;

        $this->points_to_flow_entry_guid = $attribute->points_to_flow_entry_guid ;
        $this->points_to_flow_user_guid = $attribute->points_to_flow_user_guid ;
        $this->points_to_flow_project_guid = $attribute->points_to_flow_project_guid ;

        //instead of doing a lot of edge case testing, just null them out and reform them when saving
        $this->points_to_user_id = null;
        $this->points_to_project_id = null;
        $this->points_to_entry_id = null;

    }


    public function __construct($object=null){
        $this->is_standard_attribute = false;
        $this->flow_tag_attribute_id = null ;
        $this->flow_tag_id = null ;
        $this->flow_applied_tag_id = null ;
        $this->points_to_entry_id = null ;
        $this->points_to_user_id = null ;
        $this->points_to_project_id = null ;
        $this->attribute_created_at_ts = null ;
        $this->attribute_updated_at_ts = null ;
        $this->flow_tag_attribute_guid = null ;
        $this->tag_attribute_name = null ;
        $this->tag_attribute_long = null ;
        $this->tag_attribute_text = null ;

        $this->flow_tag_guid = null ;
        $this->flow_applied_tag_guid = null;
        $this->points_to_flow_entry_guid = null ;
        $this->points_to_flow_user_guid = null ;
        $this->points_to_flow_project_guid = null ;
        $this->is_inherited = null;
        $this->points_to_title = null;
        $this->points_to_admin_name = null;
        $this->points_to_admin_guid = null;
        $this->points_to_url = null;

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

        if (empty($this->flow_tag_attribute_guid)) { $this->flow_tag_attribute_guid = null;}
        if (empty($this->flow_tag_guid)) { $this->flow_tag_guid = null;}
        if (empty($this->flow_applied_tag_guid)) { $this->flow_applied_tag_guid = null;}
        if (empty($this->points_to_flow_user_guid)) { $this->points_to_flow_user_guid = null;}
        if (empty($this->points_to_flow_project_guid)) { $this->points_to_flow_project_guid = null;}
        if (empty($this->points_to_flow_entry_guid)) { $this->points_to_flow_entry_guid = null;}
        if (empty($this->points_to_admin_guid)) { $this->points_to_admin_guid = null;}
        if (empty($this->tag_attribute_long)) { $this->tag_attribute_long = null;}
        if (empty($this->tag_attribute_text)) { $this->tag_attribute_text = null;}

        $this->is_standard_attribute = FlowTagStandardAttribute::is_standard_attribute($this);
    }


    public static function check_valid_name($words) : bool  {

        $b_min_ok =  static::minimum_check_valid_name($words,static::LENGTH_ATTRIBUTE_NAME);
        if (!$b_min_ok) {return false;}
        //no special punctuation
        if (preg_match('/[\'"<>`]/', $words, $output_array)) {
            return false;
        }
        return true;
    }


    /**
     * @throws Exception
     */
    public function save() :void {

        try {
            if (empty($this->tag_attribute_name)) {
                throw new InvalidArgumentException("Attribute Name cannot be empty");
            }

            $this->tag_attribute_name = JsonHelper::to_utf8($this->tag_attribute_name);


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
            } else {
                $this->tag_attribute_text = htmlentities(JsonHelper::to_utf8($this->tag_attribute_text));
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

            if ($this->flow_tag_attribute_id && $this->flow_tag_attribute_guid) {

                $db->update('flow_tag_attributes',$saving_info,[
                    'id' => $this->flow_tag_attribute_id
                ]);

            }
            elseif ($this->flow_tag_attribute_guid) {
                $insert_sql = "
                    INSERT INTO flow_tag_attributes(flow_tag_id, flow_applied_tag_id, created_at_ts, points_to_entry_id,
                                                    points_to_user_id, points_to_project_id, flow_tag_attribute_guid,
                                                    tag_attribute_name, tag_attribute_long, tag_attribute_text)  
                    VALUES (?,?,?,?,?,?,UNHEX(?),?,?,?) 
                    ON DUPLICATE KEY UPDATE    flow_tag_id = VALUES(flow_tag_id),   
                                                flow_applied_tag_id = VALUES(flow_applied_tag_id),
                                                points_to_entry_id = VALUES(points_to_entry_id),
                                                points_to_user_id = VALUES(points_to_user_id),
                                                points_to_project_id = VALUES(points_to_project_id),
                                                tag_attribute_name = VALUES(tag_attribute_name),
                                                tag_attribute_long = VALUES(tag_attribute_long),
                                                tag_attribute_text = VALUES(tag_attribute_text)
                                                          
                ";
                $insert_params = [
                    $this->flow_tag_id,
                    $this->flow_applied_tag_id,
                    $this->attribute_created_at_ts,
                    $this->points_to_entry_id,
                    $this->points_to_user_id,
                    $this->points_to_project_id,
                    $this->flow_tag_attribute_guid,
                    $this->tag_attribute_name,
                    $this->tag_attribute_long,
                    $this->tag_attribute_text

                ];
                $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
                $this->flow_tag_attribute_id = $db->lastInsertId();
            }
            else {
                $db->insert('flow_tag_attributes',$saving_info);
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

    public static function merge_attribute(FlowTagAttribute $top, ?FlowTagAttribute $parent ) : FlowTagAttribute {
        if (empty($parent)) {
            return new FlowTagAttribute($top);
        }


        $ret = new FlowTagAttribute();

        foreach ($parent as $key => $val) {
            if (!empty($val)) {
                $ret->$key = $val;
            }
        }

        foreach ($top as $key => $val) {
            if (!empty($val)) {
                $ret->$key = $val;
            }
        }
        return $ret;
    }
    
    public function jsonSerialize(): array
    {
        if ($this->get_brief_json()) {
           $brief = new BriefFlowTagAttribute($this);
           return $brief->to_array();
        } else {
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
                "created_at_ts" => $this->attribute_created_at_ts,
                "updated_at_ts" => $this->attribute_updated_at_ts,
                "is_standard_attribute" => $this->is_standard_attribute,
                "is_inherited" => $this->is_inherited,
                "points_to_title" => $this->points_to_title,
                "points_to_admin_guid" => $this->points_to_admin_guid,
                "points_to_admin_name" => $this->points_to_admin_name,
                "points_to_url" => $this->points_to_url

            ];
        }

    }

    public function delete_attribute() {
        $db = static::get_connection();
        if ($this->flow_tag_attribute_id) {
            $db->delete('flow_tag_attributes',['id'=>$this->flow_tag_attribute_id]);
        } else if($this->flow_tag_attribute_guid) {
            $sql = "DELETE FROM flow_tag_attributes WHERE flow_tag_attribute_guid = UNHEX(?)";
            $params = [$this->flow_tag_attribute_guid];
            $db->safeQuery($sql, $params, PDO::FETCH_BOTH, true);
        } else {
            throw new LogicException("Cannot delete flow_tag_attributes without an id or guid");
        }

    }


    public function set_link_for_pointee(RouteParserInterface $routeParser)
    {

        if ($this->points_to_flow_project_guid)
        {
            $this->points_to_url = $routeParser->urlFor('single_project_home',
                [
                    "user_name" => $this->points_to_admin_name,
                    "project_name" => $this->points_to_title
                ]
            );
        }
        elseif ( $this->points_to_flow_user_guid)
        {
            $this->points_to_url = $routeParser->urlFor('user_page',
                [
                    "user_name" => $this->points_to_title,
                ]
            );
        }
    }

    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids() : array
    {
        $ret = [];
        if (empty($this->flow_tag_id) && $this->flow_tag_guid) { $ret[] = $this->flow_tag_guid;}
        if (empty($this->points_to_entry_id) && $this->points_to_flow_entry_guid) { $ret[] = $this->points_to_flow_entry_guid;}
        if (empty($this->points_to_user_id) && $this->points_to_flow_user_guid) { $ret[] = $this->points_to_flow_user_guid;}
        if (empty($this->points_to_project_id) && $this->points_to_flow_project_guid) { $ret[] = $this->points_to_flow_project_guid;}

        return $ret;
    }

    /**
     * @@param  array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids)
    {
        if (empty($this->flow_tag_id) && $this->flow_tag_guid) {
            $this->flow_tag_id = $guid_map_to_ids[$this->flow_tag_guid] ?? null;}
        if (empty($this->points_to_entry_id) && $this->points_to_flow_entry_guid) {
            $this->points_to_entry_id = $guid_map_to_ids[$this->points_to_flow_entry_guid] ?? null;}
        if (empty($this->points_to_user_id) && $this->points_to_flow_user_guid) {
            $this->points_to_user_id = $guid_map_to_ids[$this->points_to_flow_user_guid] ?? null;}
        if (empty($this->points_to_project_id) && $this->points_to_flow_project_guid) {
            $this->points_to_project_id= $guid_map_to_ids[$this->points_to_flow_project_guid] ?? null;}
    }
}