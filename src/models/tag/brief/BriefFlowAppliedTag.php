<?php

namespace app\models\tag\brief;



use app\hexlet\WillFunctions;
use app\models\tag\FlowAppliedTag;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use stdClass;

class BriefFlowAppliedTag implements JsonSerializable {

    public ?string $flow_applied_tag_guid ;
    public ?string $flow_tag_guid ;
    public ?string $tagged_flow_entry_guid ;
    public ?string $tagged_flow_user_guid ;
    public ?string $tagged_flow_project_guid ;
    public ?int $created_at_ts ;


    /**
     * @param BriefFlowAppliedTag|FlowAppliedTag|stdClass $app
     */
    public function __construct(BriefFlowAppliedTag|FlowAppliedTag|stdClass $app){
        $this->flow_applied_tag_guid = $app->flow_applied_tag_guid;
        $this->flow_tag_guid = $app->flow_tag_guid;
        $this->tagged_flow_entry_guid = $app->tagged_flow_entry_guid;
        $this->tagged_flow_user_guid = $app->tagged_flow_user_guid;
        $this->tagged_flow_project_guid = $app->tagged_flow_project_guid;
        $this->created_at_ts = $app->created_at_ts;
    }

    #[ArrayShape(["flow_applied_tag_guid" => "null|string", "flow_tag_guid" => "null|string",
        "tagged_flow_entry_guid" => "null|string", "tagged_flow_user_guid" => "null|string",
        "tagged_flow_project_guid" => "null|string", "created_at_ts" => "\int|null"])]
    public function jsonSerialize(): array {
       return $this->to_array();
    }

    #[ArrayShape(["flow_applied_tag_guid" => "null|string", "flow_tag_guid" => "null|string",
        "tagged_flow_entry_guid" => "null|string", "tagged_flow_user_guid" => "null|string",
        "tagged_flow_project_guid" => "null|string", "created_at_ts" => "int|null"])]
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

    /**
     * @param string[] $put_issues_here
     * @return int  returns 0 or 1
     */
    public function has_minimal_information(array &$put_issues_here = []) : int {

        $what =
            (
            ($this->flow_applied_tag_guid && WillFunctions::is_valid_guid_format($this->flow_applied_tag_guid)  ) &&
            ($this->flow_tag_guid && WillFunctions::is_valid_guid_format($this->flow_tag_guid)  ) &&
            $this->created_at_ts &&
            (
                ($this->tagged_flow_entry_guid && WillFunctions::is_valid_guid_format($this->tagged_flow_entry_guid) ) ||
                ($this->tagged_flow_user_guid && WillFunctions::is_valid_guid_format($this->tagged_flow_user_guid) ) ||
                ($this->tagged_flow_project_guid && WillFunctions::is_valid_guid_format($this->tagged_flow_project_guid) )
            )
            );
        $missing_list = [];
        if (!$this->flow_applied_tag_guid || !WillFunctions::is_valid_guid_format($this->flow_applied_tag_guid) ) {$missing_list[] = 'own guid';}
        if (!$this->flow_tag_guid || !WillFunctions::is_valid_guid_format($this->flow_tag_guid) ) {$missing_list[] = 'owning tag guid';}
        if (!$this->created_at_ts) {$missing_list[] = 'timestamp';}

        if (!(
            ($this->tagged_flow_entry_guid && WillFunctions::is_valid_guid_format($this->tagged_flow_entry_guid) ) ||
            ($this->tagged_flow_user_guid && WillFunctions::is_valid_guid_format($this->tagged_flow_user_guid) ) ||
            ($this->tagged_flow_project_guid && WillFunctions::is_valid_guid_format($this->tagged_flow_project_guid) )
        )) {
            $missing_list[] = 'target';
        }

        $own_guid = $this->flow_applied_tag_guid??'{no-guid}';
        if (!$what) {
            $put_issues_here[] = "Applied of guid $own_guid missing: ". implode(',',$missing_list);
        }
        return intval($what);
    }
}