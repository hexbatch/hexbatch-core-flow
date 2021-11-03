<?php

namespace app\models\entry\archive;

use app\models\entry\IFlowEntry;
use Exception;
use RuntimeException;

final class FlowEntryArchive extends FlowEntryArchiveMembers {

    /**
     * @param IFlowEntry $entry
     * @return IFlowEntryArchive
     * @throws Exception
     */
    public static function create_archive(IFlowEntry $entry) : IFlowEntryArchive {
        return new FlowEntryArchive($entry);
    }

    /**
     * reads a yaml file at the project root that is a list of all the stored entrees with the guid and title
     * verifies that the folders exist, and return the guid strings
     * @return string[]
     */
    public static function discover_all_archived_entries() :array {
        //todo read in yaml list that has all the guids for all the entries (found in project root)
        return [];
    }

    /**
     * todo Writes a yaml file at the project root that is a list of all the stored entrees with:
     *      the guid and title and last modified timestamp, and sha1 of the folder
     * todo To be run whenever an entry is saved, created, updated, deleted, etc
     */
    public static function record_all_stored_entries() : void {

    }

    private static bool $archive_cache_flag = false;

    /**
     * @var array<string,IFlowEntryArchive> $archive_cache
     */
    private static array $archive_cache = [];
    /**
     * Removes all archives from the cache; but only if the cache has been finalized first, otherwise exception
     * prevents operations from overlapping
     */
    public static function clear_archive_cache(): void {
        if (FlowEntryArchive::$archive_cache_flag) {
            throw new RuntimeException("Cannot clear cache before its finalized");
        }
        FlowEntryArchive::$archive_cache_flag = true;
    }

    public static function get_archive_from_cache(string $guid): ?IFlowEntryArchive {
        return FlowEntryArchive::$archive_cache[$guid]??null;
    }

    public static function set_archive_to_cache(IFlowEntryArchive $what): void {
        FlowEntryArchive::$archive_cache[$what->get_entry()->get_guid()] = $what;
    }


    /**
     * Allows the cache to be cleared again, prevents different operations from using same cache
     */
    public static function finalize_archive_cache(): void { FlowEntryArchive::$archive_cache_flag = false;}



}