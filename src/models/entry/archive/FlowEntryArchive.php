<?php

namespace app\models\entry\archive;

use app\helpers\Utilities;
use app\hexlet\WillFunctions;
use app\models\base\SearchParamBase;
use app\models\entry\FlowEntry;
use app\models\entry\FlowEntrySearch;
use app\models\entry\FlowEntrySearchParams;
use app\models\entry\FlowEntryYaml;
use app\models\entry\IFlowEntry;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\project\IFlowProject;
use Exception;
use LogicException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

final class FlowEntryArchive extends FlowEntryArchiveFiles {

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
     * @param IFlowProject $project
     * @throws
     */
    public static function record_all_stored_entries(IFlowProject $project) : void {

        $stuff = FlowEntryYaml::get_yaml_data_from_database($project);
        $stuff_yaml = Yaml::dump(Utilities::deep_copy($stuff));
        $project_directory = $project->get_project_directory();
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
     * @param IFlowProject $project
     * @throws Exception
     */
    public static function update_all_entries_from_project_directory(IFlowProject $project) {
        $entries_in_files_flat_array = FlowEntry::load($project); //sorted by parent, with parent first
        $search = new FlowEntrySearchParams();
        $search->owning_project_guid = $project->get_project_guid();
        $search->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
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
            if ($in_file_entry instanceof IFlowEntryArchive) {
                $entries_in_files[$in_file_entry->get_entry()->get_guid()] = $in_file_entry;
            } else if ($in_file_entry instanceof IFlowEntry) {
                $entries_in_files[$in_file_entry->get_guid()] = $in_file_entry;
            } else {
                throw new LogicException("[update_all_entries_from_project_directory] Unknown interface ");
            }

        }


        foreach ($entries_in_files as $guid => $entry) {

            if ($entry instanceof IFlowEntryArchive) {
                $archive = FlowEntryArchive::create_archive($entry->get_entry());
            } else if ($entry instanceof IFlowEntry) {
                $archive = FlowEntryArchive::create_archive($entry);
            } else {
                throw new LogicException("[update_all_entries_from_project_directory B] Unknown interface ");
            }

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
            $search->setPage(1);
            $search->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
            $things = GeneralSearch::general_search($search_params );

            /**
             * @type array<string,int> $guid_map_to_ids
             */
            $guid_map_to_ids = [];
            foreach ($things as $thing) {
                $guid_map_to_ids[$thing->guid] = $thing->id;
            }
            //add all entry guids in the files in case children or members need them
            foreach ($entries_in_files as $guid => $entry) {
                if ($entry instanceof IFlowEntryArchive) {
                    $guid_map_to_ids[$guid] = $entry->get_entry()->get_id();
                } else if ($entry instanceof IFlowEntry) {
                    $guid_map_to_ids[$guid] = $entry->get_id();
                } else {
                    throw new LogicException("[update_all_entries_from_project_directory D] Unknown interface ");
                }

            }


            foreach ($entries_in_files as $guid => $entry) {
                $archive = $archive_map[$guid];
                $archive->fill_ids_from_guids($guid_map_to_ids);
                if (count($archive->get_needed_guids_for_empty_ids())) {
                    if ($entry instanceof IFlowEntryArchive) {
                        $title = $entry->get_entry()->get_title();
                    } else if ($entry instanceof IFlowEntry) {
                        $title = $entry->get_title();
                    } else {
                        throw new LogicException("[update_all_entries_from_project_directory C] Unknown interface ");
                    }
                    throw new RuntimeException(
                        "[update_all_entries_from_project_directory] Missing some filled guids for $title");

                }
            }



            foreach ($entries_in_files_flat_array as  $entry) {

                if ($entry instanceof IFlowEntryArchive) {
                    $entry->get_entry()->save_entry();
                } else if ($entry instanceof IFlowEntry) {
                    $entry->save_entry();
                } else {
                    throw new LogicException("[update_all_entries_from_project_directory C] Unknown interface ");
                }

            }

            //delete last to minimize dependencies being mixed up
            foreach ($entries_to_delete as $guid =>   $entry) {
                WillFunctions::will_do_nothing($guid);
                $entry->delete_entry();
            }

        }
    }



}