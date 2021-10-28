<?php

namespace app\models\tag\brief;



use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\tag\FlowTag;
use JsonSerializable;

class BriefFlowTag implements JsonSerializable {

    public string $flow_tag_guid;
    public ?string $parent_tag_guid;
    public string $flow_project_guid;
    public ?int $tag_created_at_ts;
    public ?int $tag_updated_at_ts;
    public string $flow_tag_name;

    public ?string $new_name;

    /**
     * @var BriefFlowTagAttribute[] $attributes
     */
    public array $attributes = [];

    /**
     * @var BriefFlowAppliedTag[] $applied
     */
    public array $applied = [];


    /**
     * @param FlowTag|BriefFlowTag $tag
     */
    public function __construct( $tag){

        if (is_array($tag)) {
            $tag = JsonHelper::fromString(JsonHelper::toString($tag),true,false);
        }

        $this->new_name = null;

        $this->flow_tag_guid = $tag->flow_tag_guid;
        $this->parent_tag_guid = $tag->parent_tag_guid;
        $this->flow_project_guid = $tag->flow_project_guid;
        $this->tag_created_at_ts =  WillFunctions::value_from_property_names_or_default($tag,['tag_created_at_ts','updated_at_ts']);
        $this->tag_updated_at_ts = WillFunctions::value_from_property_names_or_default($tag,['tag_updated_at_ts','updated_at_ts']);
        $this->flow_tag_name = $tag->flow_tag_name;

        $brief_attributes = [];
        foreach ($tag->attributes as $att) {
            $brief_attributes[] = new BriefFlowTagAttribute($att);
        }
        $this->attributes = $brief_attributes;

        $brief_applied = [];
        foreach ($tag->applied as $app) {
            $brief_applied[] = new BriefFlowAppliedTag($app);
        }
        $this->applied = $brief_applied;
    }

    public function jsonSerialize(): array {
        return $this->to_array();
    }

    public function to_array() : array {
        return [
            "flow_tag_guid" => $this->flow_tag_guid,
            "parent_tag_guid" => $this->parent_tag_guid,
            "flow_project_guid" => $this->flow_project_guid,
            "created_at_ts" => $this->tag_created_at_ts,
            "updated_at_ts" => $this->tag_updated_at_ts,
            "flow_tag_name" => $this->flow_tag_name,
            "attributes" => $this->attributes,
            "applied" => $this->applied
        ];
    }
}