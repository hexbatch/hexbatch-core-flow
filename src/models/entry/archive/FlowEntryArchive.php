<?php

namespace app\models\entry\archive;

use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\FlowEntry;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\entry\FlowEntrySummary;
use app\models\entry\IFlowEntry;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\project\FlowProject;
use Exception;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class FlowEntryArchive extends FlowEntryArchiveMembers {

    const ALL_FILES_YAML_NAME = 'entry-summary.yaml';
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
     * @param FlowProject $project
     * @return string[]
     * @throws
     */
    public static function discover_all_archived_entries(FlowProject $project) :array {
        $b_already_created = false;
        $project_directory = $project->get_project_directory($b_already_created);
        $yaml_path = $project_directory. DIRECTORY_SEPARATOR . self::ALL_FILES_YAML_NAME;
        $data_array = Yaml::parseFile($yaml_path);
        $ret = [];
        $blank = FlowEntry::create_entry($project,null);
        foreach ($data_array as $data) {
            try {
                $node = new FlowEntrySummary($data);
            } catch (Exception $whooo) {
                FlowEntryArchive::get_logger()->warning("Could not calculate archive entry for record",['exception'=>$whooo]);
                continue;
            }

            if ($node->guid) {
                $blank->set_guid($node->guid);
                $entry_folder_path = $blank->get_entry_folder();
                if (is_readable($entry_folder_path)) {
                    $ret[] = $node->guid;
                } else {
                    FlowEntryArchive::get_logger()->warning("[discover_all_archived_entries] Could not read the entry folder $entry_folder_path");
                }
            }
        }
        return $ret;

    }

    /**
     * @param FlowProject $project
     * @throws
     */
    public static function record_all_stored_entries(FlowProject $project) : void {

        $stuff = FlowEntrySummary::get_entry_summaries_for_project($project);
        $pigs_in_space = JsonHelper::toString($stuff);
        $stuff_serialized = JsonHelper::fromString($pigs_in_space);

        $stuff_yaml = Yaml::dump($stuff_serialized);
        $b_already_created = false;
        $project_directory = $project->get_project_directory($b_already_created);
        $yaml_path = $project_directory. DIRECTORY_SEPARATOR . self::ALL_FILES_YAML_NAME;
        $b_ok = file_put_contents($yaml_path,$stuff_yaml);
        if ($b_ok === false) {throw new RuntimeException("Could not write to $yaml_path");}
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
    public static function start_archive_write(): void {
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
    public static function finish_archive_write(): void {
        FlowEntryArchive::$archive_cache_flag = false;
    }

    /**
     * @param FlowProject $project
     * @throws Exception
     */
    public static function update_all_entries_from_project_directory(FlowProject $project) {
        $entries_in_files_flat_array = FlowEntry::load($project); //sorted by parent, with parent first
        $search = new FlowEntrySearchParams();
        $search->owning_project_guid = $project->flow_project_guid;
        $search->set_page_size(GeneralSearch::UNLIMITED_RESULTS_PER_PAGE);
        $entries_in_db_flat_array = FlowEntrySearch::search($search);


        /**
         * @var array<string,IFlowEntry> $entries_in_files
         */
        $entries_in_files = [];

        /**
         * @var array<string,IFlowEntry> $entries_in_db
         */
        $entries_in_db = [];


        /**
         * @var array<string,IFlowEntry> $entries_to_delete
         */
        $entries_to_delete = [];


        /**
         * @var array<string,IFlowEntryArchive> $archive_map
         */
        $archive_map = [];

        /**
         * @var array<string,string> $guids_needed
         */
        $guids_needed = [];


        foreach ($entries_in_db_flat_array as $in_db_entry) {
            $entries_in_db[$in_db_entry->get_guid()] = $in_db_entry;
        }

        foreach ($entries_in_files_flat_array as $in_file_entry) {
            $entries_in_files[$in_file_entry->get_guid()] = $in_file_entry;
        }


        foreach ($entries_in_files as $guid => $entry) {

            $archive = FlowEntryArchive::create_archive($entry);
            $archive_map[$guid] = $archive;
            $guids = $archive->get_needed_guids_for_empty_ids();
            foreach ($guids as $guid) {
                $guids_needed[$guid] = true;
            }
        }


        foreach ($entries_in_db as $guid => $entry) {
            if (!array_key_exists($guid,$entries_in_files)) {
                $entries_to_delete[$guid] = $entry;
            }
        }

        if (count($guids_needed)) {
            $search_params = new GeneralSearchParams();
            $search_params->guids = array_keys($guids_needed);
            $things = GeneralSearch::general_search($search_params, 1, GeneralSearch::UNLIMITED_RESULTS_PER_PAGE);

            /**
             * @type array<string,int> $guid_map_to_ids
             */
            $guid_map_to_ids = [];
            foreach ($things as $thing) {
                $guid_map_to_ids[$thing->guid] = $thing->id;
            }
            //add all entry guids in the files in case children or members need them
            foreach ($entries_in_files as $guid => $entry) {
                $guid_map_to_ids[$guid] = $entry->get_id();
            }


            foreach ($entries_in_files as $guid => $entry) {
                $archive = $archive_map[$guid];
                $archive->fill_ids_from_guids($guid_map_to_ids);
                if (count($archive->get_needed_guids_for_empty_ids())) {
                    $title = $entry->get_title();
                    throw new RuntimeException(
                        "[update_all_entries_from_project_directory] Missing some filled guids for $title");

                }
            }



            foreach ($entries_in_files_flat_array as  $entry) {
                $entry->save_entry(false,false);
            }

            //delete last to minimize dependencies being mixed up
            foreach ($entries_to_delete as $guid =>   $entry) {
                WillFunctions::will_do_nothing($guid);
                $entry->delete_entry();
            }

        }
    }



}