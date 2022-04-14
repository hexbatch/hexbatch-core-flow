<?php

namespace app\models\tag\brief;



use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\standard\FlowTagStandardAttribute;
use app\models\tag\FlowTagAttribute;
use JsonSerializable;
use stdClass;

class BriefFlowTagAttribute  implements JsonSerializable {

    public string $flow_tag_attribute_guid ;
    public ?string $flow_tag_guid ;
    public ?string $points_to_flow_entry_guid ;
    public ?string $points_to_flow_user_guid ;
    public ?string $points_to_flow_project_guid ;
    public ?string $points_to_flow_tag_guid ;
    public string $tag_attribute_name ;
    public ?int $tag_attribute_long ;
    public ?string $tag_attribute_text ;
    public int $attribute_created_at_ts ;
    public int $attribute_updated_at_ts ;

    public ?string $new_name;

    /**
     * @return string|null
     */
    public function getFlowTagAttributeGuid(): ?string
    {
        return $this->flow_tag_attribute_guid;
    }

    /**
     * @return string|null
     */
    public function getFlowTagGuid(): ?string
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

    /**
     * @return string|null
     */
    public function getTagAttributeName(): ?string
    {
        return $this->tag_attribute_name;
    }

    /**
     * @return string|null
     */
    public function getTagAttributeLong(): ?string
    {
        return $this->tag_attribute_long;
    }

    /**
     * @return string|null
     */
    public function getTagAttributeText(): ?string
    {
        return $this->tag_attribute_text;
    }

    /**
     * @return int|mixed|null
     */
    public function getAttributeCreatedAtTs()
    {
        return $this->attribute_created_at_ts;
    }

    /**
     * @return int
     */
    public function getAttributeUpdatedAtTs(): int
    {
        return $this->attribute_updated_at_ts;
    }



    /**
     * @param FlowTagAttribute|BriefFlowTagAttribute|stdClass  $att
     */
    public function __construct($att){
        if (is_array($att)) {
            $att = JsonHelper::fromString(JsonHelper::toString($att),true,false);
        }
        $b_standard = false;
        if($att instanceof stdClass){
            $b_standard = true;
        }
        $this->new_name = null;
        $this->flow_tag_attribute_guid = $b_standard? $att->flow_tag_attribute_guid  :$att->getFlowTagAttributeGuid();
        $this->flow_tag_guid = $b_standard? $att->flow_tag_guid :$att->getFlowTagGuid();
        $this->points_to_flow_entry_guid = $b_standard? $att->points_to_flow_entry_guid : $att->getPointsToFlowEntryGuid();
        $this->points_to_flow_user_guid = $b_standard? $att->points_to_flow_user_guid : $att->getPointsToFlowUserGuid();
        $this->points_to_flow_project_guid = $b_standard? $att->points_to_flow_project_guid : $att->getPointsToFlowProjectGuid();
        $this->points_to_flow_tag_guid = $b_standard? $att->points_to_flow_tag_guid : $att->getPointsToFlowTagGuid();
        $this->tag_attribute_name = $b_standard? $att->tag_attribute_name : $att->getTagAttributeName();
        $this->tag_attribute_long = (int)($b_standard? $att->tag_attribute_long : $att->getTagAttributeLong());
        $this->tag_attribute_text = $b_standard? $att->tag_attribute_text : $att->getTagAttributeText();
        $this->attribute_created_at_ts =  $b_standard?
                            WillFunctions::value_from_property_names_or_default($att,['attribute_created_at_ts','created_at_ts']):
                                            $att->getAttributeCreatedAtTs();
        $this->attribute_updated_at_ts = $b_standard?
                            WillFunctions::value_from_property_names_or_default($att,['attribute_updated_at_ts','updated_at_ts']):
                                            $att->getAttributeUpdatedAtTs();
    }


    public function jsonSerialize(): array {
        return $this->to_array();
    }

    public function to_array() : array {
        $what =  [
            "flow_tag_attribute_guid" => $this->flow_tag_attribute_guid,
            "flow_tag_guid" => $this->flow_tag_guid,
            "points_to_flow_entry_guid" => $this->points_to_flow_entry_guid,
            "points_to_flow_user_guid" => $this->points_to_flow_user_guid,
            "points_to_flow_project_guid" => $this->points_to_flow_project_guid,
            "points_to_flow_tag_guid" => $this->points_to_flow_tag_guid,
            "tag_attribute_name" => $this->tag_attribute_name,
            "tag_attribute_long" => $this->tag_attribute_long,
            "tag_attribute_text" => $this->tag_attribute_text,
            "created_at_ts" => $this->attribute_created_at_ts,
            "updated_at_ts" => $this->attribute_updated_at_ts

        ];

        if (FlowTagStandardAttribute::isNameKey($this->tag_attribute_name,true)) {
            unset($what["tag_attribute_long"]);
            unset($what["tag_attribute_text"]);
        }

        return $what;
    }

    /**
     * @param string[] $put_issues_here
     * @return int  returns 0 or 1
     */
    public function has_minimal_information(array &$put_issues_here = []) : int {

        $what =
            (
                ($this->flow_tag_attribute_guid && WillFunctions::is_valid_guid_format($this->flow_tag_attribute_guid)) &&
                ($this->flow_tag_guid && WillFunctions::is_valid_guid_format($this->flow_tag_guid)) &&
                $this->attribute_created_at_ts &&
                $this->tag_attribute_name

            );

        $missing_list = [];
        if (!$this->flow_tag_attribute_guid || !WillFunctions::is_valid_guid_format($this->flow_tag_attribute_guid) ) {$missing_list[] = 'own guid';}
        if (!$this->flow_tag_guid || !WillFunctions::is_valid_guid_format($this->flow_tag_guid) ) {$missing_list[] = 'owning tag guid';}
        if (!$this->attribute_created_at_ts) {$missing_list[] = 'timestamp';}
        if (!$this->tag_attribute_name) {$missing_list[] = 'name';}

        $own_name = $this->tag_attribute_name??'{unnamed}';
        $own_guid = $this->flow_tag_attribute_guid??'{no-guid}';
        if (!$what) {
            $put_issues_here[] = "Attribute $own_name of guid $own_guid missing: ". implode(',',$missing_list);
        }
        return intval($what);
    }
}