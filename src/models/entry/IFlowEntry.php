<?php

namespace app\models\entry;

use app\models\entry\public_json\IFlowEntryJson;
use app\models\project\IFlowProject;
use Exception;

Interface IFlowEntry {

    const LENGTH_ENTRY_TITLE = 40;
    const LENGTH_ENTRY_BLURB = 120;
    const ENTRY_FOLDER_PREFIX = 'entry-';

    public function get_max_title_length() : int;
    public function get_max_blurb_length() : int;

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

    public function get_text() : ?string;


    public function get_project_guid() : ?string;
    public function get_project_id() : ?int;
    public function get_project() : IFlowProject;

    /**
     * @return string[]
     */
    public function get_ancestor_guids(): array;

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
    public function get_children_ids() : array;



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
    public function set_project_guid(?string $what): void;

    public function set_created_at_ts(?int $what) : void;
    public function set_updated_at_ts(?int $what) : void;

    public function set_title(?string $what): void;
    public function set_blurb(?string $what): void;


    public function add_child(IFlowEntry $what): void;
    public function remove_child(IFlowEntry $what): void;


    /**
     * @param IFlowProject $project
     * @param IFlowProject|null $new_project
     * @return IFlowEntry
     * @throws Exception
     */
    public function clone_with_missing_data(IFlowProject $project,?IFlowProject $new_project = null) : IFlowEntry;

    /**
     * @param IFlowProject $project
     * @return IFlowEntry
     * @throws Exception
     */
    public function fetch_this(IFlowProject $project) : IFlowEntry ;


    /**
     * @param string|null $bb_code
     * @return void
     * @throws
     */
    public function set_body_bb_code(?string $bb_code) : void;

    /**
     * called before a save, any child can do logic and throw an exception to stop the save
     */
    public function validate_entry_before_save() :void ;

    /**
     * called after the save is made
     */
    public function on_after_save_entry() :void ;
    /**
     * @throws Exception
     */
    public function save_entry(bool $b_do_transaction = false, bool $b_save_children = false) :void ;

    /**
     * @throws Exception
     */
    public function delete_entry(): void;

    /**
     * called after the delete is done
     * @throws
     */
    public function on_after_delete_entry() :void ;

    /**
     * @param IFlowProject $project
     * @param object|array|IFlowEntry
     * @return IFlowEntry
     * @throws
     */
    public static function create_entry(IFlowProject $project,$object) : IFlowEntry ;



    /**
     * Used to make json
     * @return IFlowEntryJson
     */
    public function to_public_json() : IFlowEntryJson ;

    public function get_entry_folder() : ?string;



    /**
     * gets the full file path of the generated html for the entry
     * @return string|null
     * @throws Exception
     */
    public function get_html_path() : ?string;

    /**
     * Write the entry state to the entry folder
     * @throws
     */
    public function store(): void;

    /**
     * Loads entries from the entry folder (does not use db)
     * if no guids listed, then will return an array of all
     * else will only return the guids asked for, if some or all missing will only return the found, if any
     * @param IFlowProject $project
     * @param string[] $only_these_guids
     * @return IFlowEntry[]
     * @throws
     */
    public static function load(IFlowProject $project,array $only_these_guids = []) : array;

}