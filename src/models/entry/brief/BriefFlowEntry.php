<?php

namespace app\models\entry\brief;



use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\IFlowEntry;
use JsonSerializable;

class BriefFlowEntry implements JsonSerializable, IFlowEntryBrief {

    public ?int $entry_created_at_ts;
    public ?int $entry_updated_at_ts;
    public ?string $flow_entry_guid;
    public ?string $flow_entry_title;
    public ?string $flow_entry_blurb;
    public ?string $flow_entry_body_bb_code;
    public ?string $flow_project_guid;
    public ?string $flow_entry_parent_guid;

    public function get_parent_guid(): ?string { return $this->flow_entry_parent_guid;}
    public function get_project_guid(): ?string { return $this->flow_project_guid;}
    public function get_created_at_ts(): ?int { return $this->entry_created_at_ts;}
    public function get_updated_at_ts(): ?int { return $this->entry_updated_at_ts;}
    public function get_guid(): ?string { return $this->flow_entry_guid;}
    public function get_title(): ?string { return $this->flow_entry_title;}
    public function get_blurb(): ?string { return $this->flow_entry_blurb;}
    public function get_bb_code(): ?string { return $this->flow_entry_body_bb_code;}

    /**
     * @var BriefFlowEntry[] $child_entries
     */
    public array $child_entries;

    /**
     * @var string[] $member_guids
     */
    public array $member_guids;


    /**
     * @param IFlowEntry|IFlowEntryBrief|object|array $entry
     */
    public function __construct($entry){

        $this->flow_entry_guid = null;
        $this->flow_entry_parent_guid = null;
        $this->flow_project_guid = null;
        $this->entry_created_at_ts =  null;
        $this->entry_updated_at_ts = null;
        $this->flow_entry_title = null;
        $this->flow_entry_blurb = null;
        $this->flow_entry_body_bb_code = null;
        $this->child_entries = [];

        if (empty($entry)) {
            return;
        }

        if (is_array($entry)) {
            $entry = JsonHelper::fromString(JsonHelper::toString($entry),true,false);
        }

        if ($entry instanceof IFlowEntry || $entry instanceof IFlowEntryBrief) {
            $this->flow_entry_guid = $entry->get_guid();
            $this->flow_entry_parent_guid = $entry->get_parent_guid();
            $this->flow_project_guid = $entry->get_project_guid();
            $this->entry_created_at_ts =  $entry->get_created_at_ts();
            $this->entry_updated_at_ts = $entry->get_updated_at_ts();
            $this->flow_entry_title = $entry->get_title();
            $this->flow_entry_blurb = $entry->get_blurb();
            $this->flow_entry_body_bb_code = $entry->get_bb_code();
            $this->child_entries = [];
            foreach ($entry->get_children() as $child) {
                $this->child_entries[] = new BriefFlowEntry($child);
            }

            $this->member_guids = [];

            foreach ($entry->get_member_guids() as $member_guid) {
                $this->member_guids[] = $member_guid;
            }
        } else {
            $this->flow_entry_guid = $entry->flow_entry_guid ?? null;
            $this->flow_entry_parent_guid = $entry->flow_entry_parent_guid ?? null;
            $this->flow_project_guid = $entry->flow_project_guid ?? null;
            $this->entry_created_at_ts =  $entry->entry_created_at_ts ?? null;
            $this->entry_updated_at_ts = $entry->entry_updated_at_ts ?? null;
            $this->flow_entry_title = $entry->flow_entry_title ?? null;
            $this->flow_entry_blurb = $entry->flow_entry_blurb ?? null;
            $this->flow_entry_body_bb_code = $entry->flow_entry_body_bb_code ?? null;

            $this->child_entries = [];
            if (isset($entry->children)) {
                foreach ($entry->children as $child) {
                    $this->child_entries[] = new BriefFlowEntry($child);
                }
            }


            $this->member_guids = [];
            if (isset($entry->member_guids)) {
                foreach ($entry->member_guids as $member_guid) {
                    $this->member_guids[] = $member_guid;
                }
            }

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



    /**
     * @inheritDoc
     */
    public function get_children(): array { return $this->child_entries;}

    /**
     * @inheritDoc
     */
    public function get_member_guids(): array { return $this->member_guids;}

    /**
     * @inheritDoc
     */
    public static function create_entry_brief( $entry_or_brief_orobject_or_array): IFlowEntryBrief
    {
        return new BriefFlowEntry($entry_or_brief_orobject_or_array);
    }


}