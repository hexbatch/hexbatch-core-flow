<?php

namespace app\models\tag;

use app\helpers\Utilities;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\tag\brief\BriefFlowTagAttribute;
use Exception;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;
use Slim\Interfaces\RouteParserInterface;

class FlowTagAttribute extends FlowBase implements JsonSerializable,IFlowTagAttribute {




    protected ?int $flow_tag_attribute_id;
    protected ?int $flow_tag_id;
    protected ?int $points_to_entry_id;
    protected ?int $points_to_user_id;
    protected ?int $points_to_project_id;
    protected ?int $points_to_tag_id;
    protected ?int $attribute_created_at_ts;
    protected ?int $attribute_updated_at_ts;

    protected ?string $flow_tag_attribute_guid;
    protected ?string $tag_attribute_name;
    protected ?int $tag_attribute_long;
    protected ?string $tag_attribute_text;

    protected ?string $flow_tag_guid;
    protected ?string $points_to_flow_entry_guid;
    protected ?string $points_to_flow_user_guid;
    protected ?string $points_to_flow_project_guid;
    protected ?string $points_to_flow_tag_guid;

    protected ?bool $is_inherited;

    protected ?string $points_to_title;
    protected ?string $points_to_url;


    protected ?string $project_guid_of_pointee;
    protected ?string $project_admin_guid_of_pointee;
    protected ?string $project_admin_name_of_pointee;

    /**
     * @param bool|null $is_inherited
     */
    public function setIsInherited(?bool $is_inherited): void
    {
        $this->is_inherited = $is_inherited;
    }

    /**
     * @param string|null $points_to_flow_entry_guid
     */
    public function setPointsToFlowEntryGuid(?string $points_to_flow_entry_guid): void
    {
        Utilities::valid_guid_format_or_null_or_throw($points_to_flow_entry_guid);
        $this->points_to_flow_entry_guid = $points_to_flow_entry_guid;
    }

    /**
     * @param string|null $points_to_flow_user_guid
     */
    public function setPointsToFlowUserGuid(?string $points_to_flow_user_guid): void
    {
        Utilities::valid_guid_format_or_null_or_throw($points_to_flow_user_guid);
        $this->points_to_flow_user_guid = $points_to_flow_user_guid;
    }

    /**
     * @param string|null $points_to_flow_project_guid
     */
    public function setPointsToFlowProjectGuid(?string $points_to_flow_project_guid): void
    {
        Utilities::valid_guid_format_or_null_or_throw($points_to_flow_project_guid);
        $this->points_to_flow_project_guid = $points_to_flow_project_guid;
    }

    /**
     * @param string|null $points_to_flow_tag_guid
     */
    public function setPointsToFlowTagGuid(?string $points_to_flow_tag_guid): void
    {
        Utilities::valid_guid_format_or_null_or_throw($points_to_flow_tag_guid);
        $this->points_to_flow_tag_guid = $points_to_flow_tag_guid;
    }


    /**
     * @param int|null $tag_attribute_long
     */
    public function setLong(?int $tag_attribute_long): void
    {
        $this->tag_attribute_long = $tag_attribute_long;
    }

    /**
     * @param string|null $tag_attribute_text
     */
    public function setText(?string $tag_attribute_text): void
    {
        $this->tag_attribute_text = $tag_attribute_text;
    }

    /**
     * @param string|null $tag_attribute_name
     */
    public function setName(?string $tag_attribute_name): void
    {
        $this->tag_attribute_name = $tag_attribute_name;
    }

    /**
     * @param string|null $flow_tag_attribute_guid
     */
    public function setGuid(?string $flow_tag_attribute_guid): void
    {
        $this->flow_tag_attribute_guid = $flow_tag_attribute_guid;
    }

    /**
     * @param int|null $points_to_entry_id
     */
    public function setPointsToEntryId(?int $points_to_entry_id): void
    {
        $this->points_to_entry_id = $points_to_entry_id;
    }

    /**
     * @param int|null $points_to_user_id
     */
    public function setPointsToUserId(?int $points_to_user_id): void
    {
        $this->points_to_user_id = $points_to_user_id;
    }

    /**
     * @param int|null $points_to_project_id
     */
    public function setPointsToProjectId(?int $points_to_project_id): void
    {
        $this->points_to_project_id = $points_to_project_id;
    }

    /**
     * @param int|null $points_to_tag_id
     */
    public function setPointsToTagId(?int $points_to_tag_id): void
    {
        $this->points_to_tag_id = $points_to_tag_id;
    }



    /**
     * @param int|null $flow_tag_id
     */
    public function setTagId(?int $flow_tag_id): void
    {
        $this->flow_tag_id = $flow_tag_id;
    }


    /**
     * @param int|null $flow_tag_attribute_id
     */
    public function setId(?int $flow_tag_attribute_id): void
    {
        $this->flow_tag_attribute_id = $flow_tag_attribute_id;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->flow_tag_attribute_id;
    }


    /**
     * @return int|null
     */
    public function getCreatedAtTs(): ?int
    {
        return $this->attribute_created_at_ts;
    }

    /**
     * @return int|null
     */
    public function getUpdatedAtTs(): ?int
    {
        return $this->attribute_updated_at_ts;
    }



    /**
     * @return int|null
     */
    public function getPointsToEntryId(): ?int
    {
        return $this->points_to_entry_id;
    }

    /**
     * @return int|null
     */
    public function getPointsToUserId(): ?int
    {
        return $this->points_to_user_id;
    }

    /**
     * @return int|null
     */
    public function getPointsToProjectId(): ?int
    {
        return $this->points_to_project_id;
    }

    /**
     * @return int|null
     */
    public function getPointsToTagId(): ?int
    {
        return $this->points_to_tag_id;
    }




    /**
     * @return string|null
     */
    public function getGuid(): ?string
    {
        return $this->flow_tag_attribute_guid;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->tag_attribute_name;
    }

    /**
     * @return int|null
     */
    public function getLong(): ?int
    {
        return $this->tag_attribute_long;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->tag_attribute_text;
    }

    /**
     * @return string|null
     */
    public function getTagGuid(): ?string
    {
        return $this->flow_tag_guid;
    }

    /**
     * @return string|null
     */
    public function getPointsToFlowEntryGuid(): ?string
    {
        return $this->points_to_flow_entry_guid;
    }

    /**
     * @return string|null
     */
    public function getPointsToFlowUserGuid(): ?string
    {
        return $this->points_to_flow_user_guid;
    }

    /**
     * @return string|null
     */
    public function getPointsToFlowProjectGuid(): ?string
    {
        return $this->points_to_flow_project_guid;
    }

    /**
     * @return string|null
     */
    public function getPointsToFlowTagGuid(): ?string
    {
        return $this->points_to_flow_tag_guid;
    }



    public function has_enough_data_set() :bool {
        if (!$this->flow_tag_id) {return false;}
        if (!$this->tag_attribute_name) {return false;}
        return true;
    }

    public function update_fields_with_public_data(IFlowTagAttribute $attribute) {
        $this->tag_attribute_name = $attribute->tag_attribute_name ;

        $this->setLong($attribute->getLong()) ;
        $this->setText($attribute->getText()) ;

        $this->points_to_flow_entry_guid = $attribute->points_to_flow_entry_guid ;
        $this->points_to_flow_user_guid = $attribute->points_to_flow_user_guid ;
        $this->points_to_flow_project_guid = $attribute->points_to_flow_project_guid ;
        $this->points_to_flow_tag_guid = $attribute->points_to_flow_tag_guid ;

        //instead of doing a lot of edge case testing, just null them out and reform them when saving
        $this->points_to_user_id = null;
        $this->points_to_project_id = null;
        $this->points_to_entry_id = null;
        $this->points_to_tag_id = null;

    }


    public function __construct($object=null){
        $this->flow_tag_attribute_id = null ;
        $this->flow_tag_id = null ;
        $this->points_to_entry_id = null ;
        $this->points_to_user_id = null ;
        $this->points_to_project_id = null ;
        $this->points_to_tag_id = null ;
        $this->attribute_created_at_ts = null ;
        $this->attribute_updated_at_ts = null ;
        $this->flow_tag_attribute_guid = null ;
        $this->tag_attribute_name = null ;
        $this->tag_attribute_long = null ;
        $this->tag_attribute_text = null ;

        $this->flow_tag_guid = null ;
        $this->points_to_flow_entry_guid = null ;
        $this->points_to_flow_user_guid = null ;
        $this->points_to_flow_project_guid = null ;
        $this->points_to_flow_tag_guid = null ;
        $this->is_inherited = null;
        $this->points_to_title = null;
        $this->points_to_url = null;

        $this->project_admin_guid_of_pointee = null;
        $this->project_admin_name_of_pointee = null;
        $this->project_guid_of_pointee = null;

        if (empty($object)) {
            return;
        }

        if ($object instanceof BriefFlowTagAttribute) {
            $this->attribute_created_at_ts = $object->getCreatedAtTs();
            $this->attribute_updated_at_ts = $object->getUpdatedAtTs();
            $this->flow_tag_attribute_guid = $object->getGuid();
            $this->flow_tag_guid = $object->getTagGuid();
            $this->tag_attribute_name = $object->getName();
            $this->tag_attribute_long = $object->getLong();
            $this->tag_attribute_text = $object->getText();
            $this->points_to_flow_entry_guid = $object->getPointsToFlowEntryGuid();
            $this->points_to_flow_user_guid = $object->getPointsToFlowUserGuid();
            $this->points_to_flow_project_guid = $object->getPointsToFlowProjectGuid();
            $this->points_to_flow_tag_guid = $object->getPointsToFlowTagGuid();
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                if ($key === 'tag_attribute_long') {
                    if ($val==='' || is_null($val)) { $val = null;} else {$val = (int)$val;}
                }
                $this->$key = $val;
            }
        }

        if (empty($this->flow_tag_attribute_guid)) { $this->flow_tag_attribute_guid = null;}
        if (empty($this->flow_tag_guid)) { $this->flow_tag_guid = null;}
        if (empty($this->points_to_flow_user_guid)) { $this->points_to_flow_user_guid = null;}
        if (empty($this->points_to_flow_project_guid)) { $this->points_to_flow_project_guid = null;}
        if (empty($this->points_to_flow_tag_guid)) { $this->points_to_flow_tag_guid = null;}
        if (empty($this->points_to_flow_entry_guid)) { $this->points_to_flow_entry_guid = null;}
        if (empty($this->project_guid_of_pointee)) { $this->project_guid_of_pointee = null;}
        if (empty($this->project_admin_guid_of_pointee)) { $this->project_admin_guid_of_pointee = null;}

        $this->setLong($this->getLong());
        $this->setText($this->getText());



    }


    public static function check_valid_name($words) : bool  {

        $b_min_ok =  static::minimum_check_valid_name($words,IFlowTagAttribute::LENGTH_ATTRIBUTE_NAME);
        if (!$b_min_ok) {return false;}
        //no special punctuation
        if (preg_match('/[\'"<>`]/', $words, $output_array)) {
            WillFunctions::will_do_nothing($output_array);
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

            $this->tag_attribute_name = Utilities::to_utf8($this->tag_attribute_name);
            $this->tag_attribute_text = Utilities::to_utf8($this->tag_attribute_text);


            $b_match = static::check_valid_name($this->tag_attribute_name);
            if (!$b_match) {
                $max_len = IFlowTagAttribute::LENGTH_ATTRIBUTE_NAME;
                throw new InvalidArgumentException(
                    "Attribute name either empty OR invalid! ".
                    "First character cannot be a number. Name Cannot be greater than $max_len. ".
                    " Name cannot be a hex number greater than 25 and cannot be a decimal number");
            }


            $db = static::get_connection();


            if(  !($this->flow_tag_id)) {
                throw new InvalidArgumentException("When saving an attribute, need a tag_id or applied_tag_id");
            }

            if (!$this->points_to_entry_id && $this->points_to_flow_entry_guid) {
                $this->points_to_entry_id = $db->cell(
                    "SELECT id  FROM flow_entries WHERE flow_entry_guid = UNHEX(?)",
                    $this->points_to_flow_entry_guid);
                $this->points_to_entry_id = Utilities::if_empty_null($this->points_to_entry_id);
            }

            if (!$this->points_to_project_id && $this->points_to_flow_project_guid) {
                $this->points_to_project_id = $db->cell(
                    "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                    $this->points_to_flow_project_guid);
                $this->points_to_project_id = Utilities::if_empty_null($this->points_to_project_id);
            }

            if (!$this->points_to_tag_id && $this->points_to_flow_tag_guid) {
                $this->points_to_tag_id = $db->cell(
                    "SELECT id  FROM flow_tags WHERE flow_tag_guid = UNHEX(?)",
                    $this->points_to_flow_tag_guid);
                $this->points_to_tag_id = Utilities::if_empty_null($this->points_to_tag_id);
            }

            if (!$this->points_to_user_id && $this->points_to_flow_user_guid) {
                $this->points_to_user_id = $db->cell(
                    "SELECT id  FROM flow_users WHERE flow_user_guid = UNHEX(?)",
                    $this->points_to_flow_user_guid);
                $this->points_to_user_id = Utilities::if_empty_null($this->points_to_user_id);
            }

            if (empty($this->getText())) {
                $this->setText(null);
            } else {
                $this->setText($this->getText());
            }


            $saving_info = [
                'flow_tag_id' => $this->flow_tag_id ,
                'points_to_entry_id' => $this->points_to_entry_id ,
                'points_to_user_id' => $this->points_to_user_id ,
                'points_to_project_id' => $this->points_to_project_id ,
                'points_to_tag_id' => $this->points_to_tag_id ,
                'tag_attribute_name' => $this->tag_attribute_name ,
                'tag_attribute_long' => $this->getLong() ,
                'tag_attribute_text' => $this->getText()
            ];

            if ($this->flow_tag_attribute_id && $this->flow_tag_attribute_guid) {

                $db->update('flow_tag_attributes',$saving_info,[
                    'id' => $this->flow_tag_attribute_id
                ]);

            }
            elseif ($this->flow_tag_attribute_guid) {
                $insert_sql = "
                    INSERT INTO flow_tag_attributes(flow_tag_id,  created_at_ts, points_to_entry_id,
                                                    points_to_user_id, points_to_project_id , points_to_tag_id,
                                                    flow_tag_attribute_guid,      
                                                    tag_attribute_name, tag_attribute_long, tag_attribute_text)  
                    VALUES (?,?,?,?,?,?,UNHEX(?),?,?,?) 
                    ON DUPLICATE KEY UPDATE    flow_tag_id = VALUES(flow_tag_id),   
                                                points_to_entry_id = VALUES(points_to_entry_id),
                                                points_to_user_id = VALUES(points_to_user_id),
                                                points_to_project_id = VALUES(points_to_project_id),
                                                points_to_tag_id = VALUES(points_to_tag_id),
                                                tag_attribute_name = VALUES(tag_attribute_name),
                                                tag_attribute_long = VALUES(tag_attribute_long),
                                                tag_attribute_text = VALUES(tag_attribute_text)
                                                          
                ";
                $insert_params = [
                    $this->flow_tag_id,
                    $this->attribute_created_at_ts,
                    $this->points_to_entry_id,
                    $this->points_to_user_id,
                    $this->points_to_project_id,
                    $this->points_to_tag_id,
                    $this->flow_tag_attribute_guid,
                    $this->tag_attribute_name,
                    $this->getLong(),
                    $this->getText()

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

    public static function merge_attribute(IFlowTagAttribute $top, ?IFlowTagAttribute $parent ) : IFlowTagAttribute {
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
            if ($key === 'tag_attribute_text') {
                $ret->$key = $val;
            } elseif ($key === 'tag_attribute_long') {
                $ret->$key = $val;
            } else {
                if (!empty($val)) {
                    $ret->$key = $val;
                }
            }

        }
        return $ret;
    }

    /**
     * @throws JsonException
     */
    public function jsonSerialize(): array
    {
        if ($this->get_brief_json_flag()) {
           $brief = new BriefFlowTagAttribute($this);
           return $brief->to_array();
        } else {
            return [
                "flow_tag_attribute_guid" => $this->flow_tag_attribute_guid,
                "flow_tag_guid" => $this->flow_tag_guid,
                "points_to_flow_entry_guid" => $this->points_to_flow_entry_guid,
                "points_to_flow_user_guid" => $this->points_to_flow_user_guid,
                "points_to_flow_project_guid" => $this->points_to_flow_project_guid,
                "points_to_flow_tag_guid" => $this->points_to_flow_tag_guid,
                "tag_attribute_name" => $this->tag_attribute_name,
                "tag_attribute_long" => $this->getLong(),
                "tag_attribute_text" => $this->getText(),
                "created_at_ts" => $this->attribute_created_at_ts,
                "updated_at_ts" => $this->attribute_updated_at_ts,
                "is_inherited" => $this->is_inherited,
                "points_to_title" => $this->points_to_title,
                "project_admin_guid_of_pointee" => $this->project_admin_guid_of_pointee,
                "project_admin_name_of_pointee" => $this->project_admin_name_of_pointee,
                "project_guid_of_pointee" => $this->project_guid_of_pointee,
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
                    "user_name" => $this->project_admin_guid_of_pointee,
                    "project_name" => $this->project_guid_of_pointee
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
        } elseif ( $this->points_to_flow_entry_guid)
        {
            $this->points_to_url = $routeParser->urlFor('show_entry',
                [
                    "user_name" => $this->project_admin_guid_of_pointee,
                    "project_name" => $this->project_guid_of_pointee,
                    "entry_name" => $this->points_to_flow_entry_guid
                ]
            );
        }
        elseif ( $this->points_to_flow_tag_guid)
        {
            $this->points_to_url = $routeParser->urlFor('show_tag',
                [
                    "user_name" => $this->project_admin_guid_of_pointee,
                    "project_name" => $this->project_guid_of_pointee,
                    "tag_name" => $this->points_to_flow_tag_guid
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
        if (empty($this->points_to_tag_id) && $this->points_to_flow_tag_guid) { $ret[] = $this->points_to_flow_tag_guid;}

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
        if (empty($this->points_to_tag_id) && $this->points_to_flow_tag_guid) {
            $this->points_to_tag_id= $guid_map_to_ids[$this->points_to_flow_tag_guid] ?? null;}
    }
}