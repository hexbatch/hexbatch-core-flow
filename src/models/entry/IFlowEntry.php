<?php

namespace app\models\entry;

use app\models\entry\brief\IFlowEntryBrief;
use app\models\project\FlowProject;
use Exception;

Interface IFlowEntry {

    const LENGTH_ENTRY_TITLE = 40;
    const LENGTH_ENTRY_BLURB = 120;

    public function get_parent_guid() : ?string;
    public function get_parent_id() : ?int;
    public function get_parent() : ?IFlowEntry;

    public function get_created_at_ts() : ?int;
    public function get_updated_at_ts() : ?int;

    public function get_id() : ?int;
    public function get_guid() : ?string;
    public function get_title() : ?string;
    public function get_blurb() : ?string;


    public function get_bb_code() : ?string;
    public function get_html() : ?string;
    public function get_html_path() : ?string;
    public function get_text() : ?string;


    public function get_project_guid() : ?string;
    public function get_project_id() : ?int;


    /**
     * @return IFlowEntry[]
     */
    public function get_children() : array;

    /**
     * @return string[]
     */
    public function get_children_guids() : array;

    /**
     * @return int[]
     */
    public function get_children_id() : array;


    /**
     * @return IFlowEntry[]
     */
    public function get_members() : array;

    /**
     * @return string[]
     */
    public function get_member_guids() : array;


    public function get_host_guids() : array;

    /**
     * @return IFlowEntry[]
     */
    public function get_hosts() : array;


    public function set_id(?int $what): void;
    public function set_guid(?string $what): void;
    public function set_parent_id(?int $what): void;
    public function set_parent_guid(?string $what): void;
    public function set_project_id(?int $what): void;

    public function set_title(?string $what): void;
    public function set_blurb(?string $what): void;



    /**
     * @param FlowProject $project
     * @return IFlowEntry
     * @throws Exception
     */
    public function clone_with_missing_data(FlowProject $project) : IFlowEntry;

    /**
     * @param FlowProject $project
     * @return IFlowEntry
     * @throws Exception
     */
    public function fetch_this(FlowProject $project) : IFlowEntry ;


    public function set_body_bb_code(string $bb_code);

    /**
     * @throws Exception
     */
    public function save_entry(bool $b_do_transaction = false, bool $b_save_children = false) :void ;

    /**
     * @throws Exception
     */
    public function delete_entry(): void;

    /**
     * @param FlowProject $project
     * @param object|array|IFlowEntry
     * @return IFlowEntry
     * @throws
     */
    public static function create_entry(FlowProject $project,$object) : IFlowEntry ;



    /**
     * sort parents before children
     * if there are tags with a parent set, but not in the array, then those are put at the end
     * @param IFlowEntry[] $entry_array_to_sort
     * @return IFlowEntry[]
     */
    public static function sort_array_by_parent(array $entry_array_to_sort) : array;


    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids() : array;

    /**
     * @param  array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids): void;

    public function to_i_flow_entry_brief() : IFlowEntryBrief ;

}