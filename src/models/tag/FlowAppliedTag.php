<?php

namespace app\models\tag;

use JsonSerializable;

class FlowAppliedTag implements JsonSerializable {

    public ?int $id;
    public ?int $flow_tag_id;
    public ?int $tagged_flow_entry_id;
    public ?int $tagged_flow_user_id;
    public ?int $tagged_flow_project_id;
    public ?int $created_at_ts;
    public ?string $flow_applied_tag_guid;


    public ?string $tagged_flow_entry_guid;
    public ?string $tagged_flow_user_guid;
    public ?string $tagged_flow_project_guid;

    public ?FlowTag $tag;

    
    public function jsonSerialize(): array
    {
        return [
            "flow_applied_tag_guid" => $this->flow_applied_tag_guid,
            "tagged_flow_entry_guid" => $this->tagged_flow_entry_guid,
            "tagged_flow_user_guid" => $this->tagged_flow_user_guid,
            "tagged_flow_project_guid" => $this->tagged_flow_project_guid,
            "created_at_ts" => $this->created_at_ts,
            "tag" => $this->tag
        ];
    }
}