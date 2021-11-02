<?php

namespace app\models\entry\brief;


Interface IFlowEntryBrief {


    public function get_parent_guid() : ?string;
    public function get_project_guid() : ?string;
    public function get_created_at_ts() : ?int;
    public function get_updated_at_ts() : ?int;
    public function get_guid() : ?string;
    public function get_title() : ?string;
    public function get_blurb() : ?string;
    public function get_bb_code() : ?string;


    /**
     * @return IFlowEntryBrief[]
     */
    public function get_children() : array;

    /**
     * @return string[]
     */
    public function get_member_guids() : array;


    /**
     * @param mixed $entry_or_brief_orobject_or_array
     */
    public static function create_entry_brief( $entry_or_brief_orobject_or_array) : IFlowEntryBrief ;


    public function has_minimal_information(array &$put_issues_here = []) : int;

    public function to_array() : array;


}