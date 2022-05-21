<?php

namespace app\models\tag\brief;



use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\standard\FlowTagStandardAttribute;
use app\models\tag\IFlowTagAttribute;
use JsonException;
use JsonSerializable;
use stdClass;

class BriefFlowTagAttribute  implements JsonSerializable {

    protected string $flow_tag_attribute_guid ;
    protected ?string $flow_tag_guid ;
    protected ?string $points_to_flow_entry_guid ;
    protected ?string $points_to_flow_user_guid ;
    protected ?string $points_to_flow_project_guid ;
    protected ?string $points_to_flow_tag_guid ;
    protected string $tag_attribute_name ;
    protected ?int $tag_attribute_long ;
    protected ?string $tag_attribute_text ;
    protected int $attribute_created_at_ts ;
    protected int $attribute_updated_at_ts ;

    protected ?string $new_name;

    /**
     * @return int|mixed|null
     */
    public function getCreatedAtTs(): mixed
    {
        return $this->attribute_created_at_ts;
    }

    /**
     * @return int|mixed|null
     */
    public function getUpdatedAtTs(): mixed
    {
        return $this->attribute_updated_at_ts;
    }

    /**
     * @param string|null $new_name
     */
    public function setNewName(?string $new_name): void
    {
        $this->new_name = $new_name;
    }



    /**
     * @return string|null
     */
    public function getNewName(): ?string
    {
        return $this->new_name;
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

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->tag_attribute_name;
    }

    /**
     * @return string|null
     */
    public function getLong(): ?string
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
     * @param IFlowTagAttribute|BriefFlowTagAttribute|array|stdClass $att
     * @throws JsonException
     */
    public function __construct(IFlowTagAttribute|BriefFlowTagAttribute|array|stdClass $att){
        if (is_array($att)) {
            $att = JsonHelper::fromString(JsonHelper::toString($att),true,false);
        }
        $this->new_name = null;
        if($att instanceof IFlowTagAttribute){
            $this->flow_tag_attribute_guid = $att->getGuid();
            $this->flow_tag_guid = $att->getTagGuid();
            $this->points_to_flow_entry_guid = $att->getPointsToFlowEntryGuid();
            $this->points_to_flow_user_guid = $att->getPointsToFlowUserGuid();
            $this->points_to_flow_project_guid =  $att->getPointsToFlowProjectGuid();
            $this->points_to_flow_tag_guid = $att->getPointsToFlowTagGuid();
            $this->tag_attribute_name =  $att->getName();
            $this->tag_attribute_long = $att->getLong();
            $this->tag_attribute_text = $att->getText();
            $this->attribute_created_at_ts = $att->getCreatedAtTs();
            $this->attribute_updated_at_ts = $att->getUpdatedAtTs();
        } else {
            $this->flow_tag_attribute_guid = $att->flow_tag_attribute_guid??null;
            $this->flow_tag_guid = $att->flow_tag_guid??null ;
            $this->points_to_flow_entry_guid = $att->points_to_flow_entry_guid??null ;
            $this->points_to_flow_user_guid = $att->points_to_flow_user_guid??null ;
            $this->points_to_flow_project_guid = $att->points_to_flow_project_guid??null ;
            $this->points_to_flow_tag_guid = $att->points_to_flow_tag_guid??null ;
            $this->tag_attribute_name = $att->tag_attribute_name??null ;
            $this->tag_attribute_long = (int)($att->tag_attribute_long??null);
            $this->tag_attribute_text = $att->tag_attribute_text??null ;

            if ($att instanceof BriefFlowTagAttribute) {
                $this->attribute_created_at_ts =$att->getCreatedAtTs();
                $this->attribute_updated_at_ts =$att->getUpdatedAtTs();
            } else {
                $this->attribute_created_at_ts =
                    WillFunctions::value_from_property_names_or_default($att,
                        ['attribute_created_at_ts','created_at_ts']);

                $this->attribute_updated_at_ts =
                    WillFunctions::value_from_property_names_or_default($att,
                        ['attribute_updated_at_ts','updated_at_ts']);
            }

        }
        
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