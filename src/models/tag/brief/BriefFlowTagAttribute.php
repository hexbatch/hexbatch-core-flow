<?php

namespace app\models\tag\brief;



use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\tag\FlowTagAttribute;
use JsonSerializable;

class BriefFlowTagAttribute  implements JsonSerializable {

    public string $flow_tag_attribute_guid ;
    public ?string $flow_tag_guid ;
    public ?string $flow_applied_tag_guid ;
    public ?string $points_to_flow_entry_guid ;
    public ?string $points_to_flow_user_guid ;
    public ?string $points_to_flow_project_guid ;
    public string $tag_attribute_name ;
    public ?string $tag_attribute_long ;
    public ?string $tag_attribute_text ;
    public int $attribute_created_at_ts ;
    public int $attribute_updated_at_ts ;

    public ?string $new_name;


    /**
     * @param FlowTagAttribute|BriefFlowTagAttribute $att
     */
    public function __construct($att){
        if (is_array($att)) {
            $att = JsonHelper::fromString(JsonHelper::toString($att),true,false);
        }
        $this->new_name = null;
        $this->flow_tag_attribute_guid = $att->flow_tag_attribute_guid;
        $this->flow_tag_guid = $att->flow_tag_guid;
        $this->flow_applied_tag_guid = $att->flow_applied_tag_guid;
        $this->points_to_flow_entry_guid = $att->points_to_flow_entry_guid;
        $this->points_to_flow_user_guid = $att->points_to_flow_user_guid;
        $this->points_to_flow_project_guid = $att->points_to_flow_project_guid;
        $this->tag_attribute_name = $att->tag_attribute_name;
        $this->tag_attribute_long = $att->tag_attribute_long;
        $this->tag_attribute_text = $att->tag_attribute_text;
        $this->attribute_created_at_ts =  WillFunctions::value_from_property_names_or_default($att,['attribute_created_at_ts','created_at_ts']);
        $this->attribute_updated_at_ts = WillFunctions::value_from_property_names_or_default($att,['attribute_updated_at_ts','updated_at_ts']);
    }


    public function jsonSerialize(): array {
        return $this->to_array();
    }

    public function to_array() : array {
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
            "updated_at_ts" => $this->attribute_updated_at_ts

        ];
    }
}