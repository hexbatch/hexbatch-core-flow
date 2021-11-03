<?php

namespace app\models\entry\archive;


use app\models\entry\IFlowEntry;
use Exception;

Interface IFlowEntryArchive {


    public function get_entry() : IFlowEntry;


    /**
     * @param array $put_issues_here OUTREF
     * @return int
     * @throws
     */
    public function has_minimal_information(array &$put_issues_here = []) : int;

    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids() : array;

    /**
     * @param  array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids): void;


    public function to_array() : array;

    /**
     * Writes the entry, and its children , to the archive
     */
    public function write_archive() : void ;


    /**
     * sets any data found in archive into this, over-writing data in entry object
     * @throws
     */
    public function read_archive() : void ;

    /**
     * @param IFlowEntry $entry
     * @return IFlowEntryArchive
     * @throws Exception
     */
    public static function create_archive(IFlowEntry $entry) : IFlowEntryArchive ;


    /**
     * reads a yaml file at the project root that is a list of all the stored entrees with the guid and title
     * verifies that the folders exist, and return the guid strings
     * @return string[]
     */
    public static function discover_all_archived_entries() :array;


    /**
     * Writes a yaml file at the project root that is a list of all the stored entrees with the guid and title and last modified timestamp
     * To be run whenever an entry is saved, created, updated, deleted, etc
     */
    public static function record_all_stored_entries() : void ;

    /**
     * Removes all archives from the cache; but only if the cache has been finalized first, otherwise exception
     * prevents operations from overlapping
     */
    public static function clear_archive_cache(): void;

    public static function get_archive_from_cache(string $guid): ?IFlowEntryArchive;

    public static function set_archive_to_cache(IFlowEntryArchive $what): void ;


    /**
     * Allows the cache to be cleared again, prevents different operations from using same cache
     */
    public static function finalize_archive_cache(): void;

}