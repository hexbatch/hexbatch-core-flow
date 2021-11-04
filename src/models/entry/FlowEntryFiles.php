<?php

namespace app\models\entry;


use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\archive\IFlowEntryArchive;
use app\models\project\FlowProject;
use Exception;
use RuntimeException;

/**
 * All the public resources and files an entry uses is in the project resources folder
 * so the html is simply a file in the top project directory
 */
abstract class FlowEntryFiles extends FlowEntryBase  {

    const ENTRY_FOLDER_PREFIX = 'entry-';

    public ?string $flow_entry_body_html;
    public ?string $flow_entry_body_bb_code;
    public ?string $flow_entry_body_text;


    /**
     * @param array|object|IFlowEntry|IFlowEntryArchive|null $object
     * @param FlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?FlowProject $project){
        parent::__construct($object,$project);
        $this->flow_entry_body_html = $this->get_html();
    }


    /**
     * returns folder with no trailing slash
     * @return string|null
     * @throws Exception
     */
    public function get_entry_folder() : ?string{
        if (!$this->project) {return null;}
        if (!$this->flow_entry_guid) {return null;}
        $project_dir = $this->project->get_project_directory();
        if (empty($project_dir)) {return null;}
        $path = $project_dir . DIRECTORY_SEPARATOR . static::ENTRY_FOLDER_PREFIX . $this->flow_entry_guid;
        return $path;
    }


    /**
     * called before a save, any child can do logic and throw an exception to stop the save
     */
    public function validate_entry_before_save() :void {
        parent::validate_entry_before_save();
        WillFunctions::will_do_action_later('validate and maybe change some tags in the html');

    }



    /**
     * @return string|null
     * @throws Exception
     */
    public function get_html() : ?string {
        if (!$this->flow_entry_body_html) {
            $path = $this->get_html_path();
            if (is_readable($path)){
                $this->flow_entry_body_html = file_get_contents($this->get_html_path());
                if ($this->flow_entry_body_html === false) {
                    throw new RuntimeException("Entry html path exists but could not read");
                }
            } else {
                $this->flow_entry_body_html = null;
            }

        }
        return $this->flow_entry_body_html;

    }

    public function get_text() : ?string { return $this->flow_entry_body_text;}
    public function get_bb_code(): ?string { return $this->flow_entry_body_bb_code;}


    /**
     * gets the full file path of the generated html for the entry
     * @return string|null
     * @throws Exception
     */
    public function get_html_path() : ?string{
        $base_path = $this->get_entry_folder();
        if (!$base_path) {return null;}
        $path = $base_path . DIRECTORY_SEPARATOR . "entry.html";
        return $path;
    }

    /**
     * files not written until save called
     * @param string $bb_code
     */
    public function set_body_bb_code(string $bb_code) {
        $this->flow_entry_body_bb_code = $bb_code;
        $this->flow_entry_body_html = JsonHelper::html_from_bb_code($bb_code);
        $this->flow_entry_body_text = str_replace('&nbsp;',' ',strip_tags($this->flow_entry_body_html));
    }


    /**
     * @param FlowProject $project
     * @return IFlowEntry
     * @throws Exception
     */
    public function clone_with_missing_data(FlowProject $project ) : IFlowEntry {

        $ret = parent::clone_with_missing_data($project);
        $ret->set_body_bb_code($this->flow_entry_body_bb_code);
        return $ret;
    }


}