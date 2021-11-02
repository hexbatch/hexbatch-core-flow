<?php

namespace app\models\entry;



use app\models\base\FlowBase;
use JsonSerializable;

abstract class FlowEntryBase extends FlowBase implements JsonSerializable,IFlowEntry  {


    public function jsonSerialize() : array
    {
        return [
            "flow_entry_guid" => $this->get_guid(),
            "flow_entry_parent_guid" => $this->get_parent_guid(),
            "flow_project_guid" => $this->get_project_guid(),
            "entry_created_at_ts" => $this->get_created_at_ts(),
            "entry_updated_at_ts" => $this->get_updated_at_ts(),
            "flow_entry_title" => $this->get_title(),
            "flow_entry_blurb" => $this->get_blurb(),
            "flow_entry_body_bb_code" => $this->get_bb_code(),
            "child_entries" => $this->get_children(),
            "member_guids" => $this->get_member_guids(),
        ];
    }
}