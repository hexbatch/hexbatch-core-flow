<?php

namespace app\models\entry\brief;



use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\FlowEntryDB;
use JsonSerializable;

class BriefFlowEntry implements JsonSerializable {

    public ?int $entry_created_at_ts;
    public ?int $entry_updated_at_ts;
    public ?string $flow_entry_guid;
    public ?string $flow_entry_title;
    public ?string $flow_entry_blurb;
    public ?string $flow_entry_body_bb_code;
    public ?string $flow_project_guid;
    public ?string $flow_entry_parent_guid;

    /**
     * @var BriefFlowEntry[] $child_entries
     */
    public array $child_entries;

    /**
     * @var string[] $member_guids
     */
    public array $member_guids;


    /**
     * @param FlowEntryDB|BriefFlowEntry $entry
     */
    public function __construct($entry){

        if (is_array($entry)) {
            $entry = JsonHelper::fromString(JsonHelper::toString($entry),true,false);
        }



        $this->flow_entry_guid = $entry->flow_entry_guid;
        $this->flow_entry_parent_guid = $entry->flow_entry_parent_guid;
        $this->flow_project_guid = $entry->flow_project_guid;
        $this->entry_created_at_ts =  $entry->entry_created_at_ts;
        $this->entry_updated_at_ts = $entry->entry_updated_at_ts;
        $this->flow_entry_title = $entry->flow_entry_title;
        $this->flow_entry_blurb = $entry->flow_entry_blurb;
        $this->flow_entry_body_bb_code = $entry->flow_entry_body_bb_code;

        $this->child_entries = [];
        foreach ($entry->children as $child) {
            $this->child_entries[] = new BriefFlowEntry($child);
        }

        $this->member_guids = [];

        foreach ($entry->member_guids as $member_guid) {
            $this->member_guids[] = $member_guid;
        }

    }

    public function jsonSerialize(): array {
        return $this->to_array();
    }

    public function to_array() : array {

        return [
            "flow_entry_guid" => $this->flow_entry_guid,
            "flow_entry_parent_guid" => $this->flow_entry_guid,
            "flow_project_guid" => $this->flow_entry_guid,
            "entry_created_at_ts" => $this->entry_created_at_ts,
            "entry_updated_at_ts" => $this->entry_updated_at_ts,
            "flow_entry_title" => $this->flow_entry_title,
            "flow_entry_blurb" => $this->flow_entry_blurb,
            "flow_entry_body_bb_code" => $this->flow_entry_body_bb_code,
            "child_entries" => $this->child_entries,
            "member_guids" => $this->member_guids,

        ];
    }

    /**
     * @param string[] $put_issues_here
     * @return int  returns 0 or 1
     */
    public function has_minimal_information(array &$put_issues_here = []) : int {

        $us =
            (
            ($this->flow_project_guid && WillFunctions::is_valid_guid_format($this->flow_project_guid) ) &&
            ($this->flow_entry_guid && WillFunctions::is_valid_guid_format($this->flow_entry_guid) ) &&
            $this->entry_created_at_ts &&
            $this->flow_entry_title &&
            $this->flow_entry_blurb &&
            $this->flow_entry_body_bb_code

            );
        $missing_list = [];

        if (!$this->flow_project_guid || !WillFunctions::is_valid_guid_format($this->flow_project_guid) ) {$missing_list[] = 'project guid';}
        if (!$this->flow_entry_guid || !WillFunctions::is_valid_guid_format($this->flow_entry_guid) ) {$missing_list[] = 'own guid';}
        if (!$this->entry_created_at_ts) {$missing_list[] = 'timestamp';}
        if (!$this->flow_entry_title  ) {$missing_list[] = 'name';}
        if (!$this->flow_entry_blurb  ) {$missing_list[] = 'name';}
        if (!$this->flow_entry_body_bb_code  ) {$missing_list[] = 'name';}

        $name = $this->flow_entry_title??'{unnamed}';
        $guid = $this->flow_entry_guid??'{no-guid}';
        if (!$us) {
            $put_issues_here[] = "Entry $name of guid $guid missing: ". implode(',',$missing_list);
        }
        $b_bad_children = false;

        foreach ($this->child_entries as $child) {
            $what =  $child->has_minimal_information($put_issues_here);
            if (!$what) {$b_bad_children = true;}
            $us &= $what;
        }

        if ($b_bad_children) {
            $put_issues_here[] = "Entry $name of guid $guid children missing data ";
        }

        return intval($us);
    }
    //todo verify all member guids in db or file when doing verification later
}