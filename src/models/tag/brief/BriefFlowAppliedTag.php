<?php

namespace app\models\tag\brief;



use app\hexlet\JsonHelper;
use app\models\tag\FlowAppliedTag;
use JsonSerializable;

class BriefFlowAppliedTag implements JsonSerializable {

    public string $flow_applied_tag_guid ;
    public string $flow_tag_guid ;
    public ?string $tagged_flow_entry_guid ;
    public ?string $tagged_flow_user_guid ;
    public ?string $tagged_flow_project_guid ;
    public int $created_at_ts ;


    /**
     * @param FlowAppliedTag|BriefFlowAppliedTag $app
     */
    public function __construct($app){
        if (is_array($app)) {
            $app = JsonHelper::fromString(JsonHelper::toString($app),true,false);
        }
        $this->flow_applied_tag_guid = $app->flow_applied_tag_guid;
        $this->flow_tag_guid = $app->flow_tag_guid;
        $this->tagged_flow_entry_guid = $app->tagged_flow_entry_guid;
        $this->tagged_flow_user_guid = $app->tagged_flow_user_guid;
        $this->tagged_flow_project_guid = $app->tagged_flow_project_guid;
        $this->created_at_ts = $app->created_at_ts;
    }

    public function jsonSerialize(): array {
       return $this->to_array();
    }

    public function to_array() : array {
        return [
            "flow_applied_tag_guid" => $this->flow_applied_tag_guid,
            "flow_tag_guid" => $this->flow_tag_guid,
            "tagged_flow_entry_guid" => $this->tagged_flow_entry_guid,
            "tagged_flow_user_guid" => $this->tagged_flow_user_guid,
            "tagged_flow_project_guid" => $this->tagged_flow_project_guid,
            "created_at_ts" => $this->created_at_ts
        ];
    }

    public function get_tagged_guid(): ?string {
        if ($this->tagged_flow_project_guid) {return $this->tagged_flow_project_guid;}
        if ($this->tagged_flow_user_guid) {return $this->tagged_flow_user_guid;}
        if ($this->tagged_flow_entry_guid) {return $this->tagged_flow_entry_guid;}
        return null;
    }
}