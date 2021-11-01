<?php

namespace app\models\entry\archive;


use app\hexlet\RecursiveClasses;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\entry\IFlowEntry;
use JsonSerializable;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

abstract class FlowEntryArchiveBase extends FlowBase implements JsonSerializable, IFlowEntryArchive {
    


    protected IFlowEntry $entry;
    
    /**
     * @param IFlowEntry $entry
     */
    public function __construct(IFlowEntry $entry){

        $this->entry = $entry;

    }

    public function get_entry() : IFlowEntry { return $this->entry;}

    public function jsonSerialize(): array {
        return $this->to_array();
    }

    public function to_array() : array {

        return [
            "flow_entry_guid" => $this->entry->get_guid(),
            "flow_entry_parent_guid" => $this->entry->get_parent_guid(),
            "flow_project_guid" => $this->entry->get_project_guid(),
            "entry_created_at_ts" => $this->entry->get_created_at_ts(),
            "entry_updated_at_ts" => $this->entry->get_updated_at_ts()

        ];
    }

    /**
     * @param string[] $put_issues_here
     * @return int  returns 0 or 1
     */
    public function has_minimal_information(array &$put_issues_here = []) : int {

        $us =
            (
            ($this->entry->get_project_guid() && WillFunctions::is_valid_guid_format($this->entry->get_project_guid()) ) &&
            ($this->entry->get_guid() && WillFunctions::is_valid_guid_format($this->entry->get_guid()) ) &&
            $this->entry->get_created_at_ts() &&
            $this->entry->get_title() &&
            $this->entry->get_blurb()

            );
        $missing_list = [];

        if (!$this->entry->get_project_guid() || !WillFunctions::is_valid_guid_format($this->entry->get_project_guid()) ) {
            $missing_list[] = 'project guid';
        }
        
        if (!$this->entry->get_guid() || !WillFunctions::is_valid_guid_format($this->entry->get_guid()) ) {$missing_list[] = 'own guid';}
        if (!$this->entry->get_created_at_ts()) {$missing_list[] = 'timestamp';}
        if (!$this->entry->get_title()  ) {$missing_list[] = 'name';}
        if (!$this->entry->get_blurb()  ) {$missing_list[] = 'name';}

        $name = $this->entry->get_title()??'{unnamed}';
        $guid = $this->entry->get_guid()??'{no-guid}';
        if (!$us) {
            $put_issues_here[] = "Entry $name of guid $guid missing: ". implode(',',$missing_list);
        }


        return intval($us);
    }

    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids(): array
    {
        $ret = [];
        if (empty($this->entry->get_project_id()) && $this->entry->get_project_guid()) { $ret[] = $this->entry->get_project_guid();}
        if (empty($this->entry->get_parent_id()) && $this->entry->get_parent_guid()) { $ret[] = $this->entry->get_parent_guid();}

        return $ret;
    }

    /**
     * @param array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids): void
    {
        if (empty($this->entry->get_project_id()) && $this->entry->get_project_guid()) {
            $this->entry->set_project_id( $guid_map_to_ids[$this->entry->get_project_guid()] ?? null);}
        if (empty($this->flow_entry_parent_id) && $this->entry->get_project_guid()) {
            $this->entry->set_parent_id($guid_map_to_ids[$this->entry->get_project_guid()] ?? null);}
    }


    /**
     * deletes the entry folder
     * @throws
     */
    public function delete_archive() : void {
        $path = $this->get_entry()->get_entry_folder();
        if (!is_readable($path)) {
            static::get_logger()->warning("Could not delete entry base folder of $path because it does not exist");
            return;
        }
        RecursiveClasses::rrmdir($path);
    }

    /**
     * Writes the entry, and its children , to the archive
     */
    public function write_archive() : void {
        //create folder if not existing
        $path = $this->get_entry()->get_entry_folder();
        if (!is_readable($path)) {
            $b_ok = mkdir($path,0777,false);
            if (!$b_ok) {
                throw new RuntimeException("Could not create entry base folder of  $path");
            }
        }


        $stuff = $this->to_array();
        $stuff_yaml = Yaml::dump($stuff);

        $yaml_path = $path. DIRECTORY_SEPARATOR . static::BASE_YAML_FILE_NAME;
        $b_ok = file_put_contents($yaml_path,$stuff_yaml);
        if ($b_ok === false) {throw new RuntimeException("Could not write to $yaml_path");}
    }


    /**
     * sets any data found in archive into this, over-writing data in entry object
     */
    public function read_archive() : void  {
        $path = $this->get_entry()->get_entry_folder();
        $yaml_path = $path. DIRECTORY_SEPARATOR . static::BASE_YAML_FILE_NAME;
        if (!is_readable($yaml_path)) {
            throw new RuntimeException("Could not read $yaml_path");
        }

        $saved = Yaml::parseFile($yaml_path);
        foreach ($saved as $key => $value) {

            switch ($key) {
                case 'flow_entry_guid': {
                    $this->get_entry()->set_guid($value);
                    break;
                }
                case 'flow_entry_parent_guid': {
                    $this->get_entry()->set_parent_guid($value);
                    break;
                }
                case 'flow_project_guid': {
                    $this->get_entry()->set_project_id($value);
                    break;
                }
                case 'entry_created_at_ts': {
                    $this->get_entry()->set_created_at_ts($value);
                    break;
                }
                case 'entry_updated_at_ts': {
                    $this->get_entry()->set_updated_at_ts($value);
                    break;
                }
                default: {
                    throw new RuntimeException("read_archive: Did not recognize the key of $key");
                }
            }
        }
    }



}