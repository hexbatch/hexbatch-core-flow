<?php

namespace app\models\entry;


use app\helpers\ProjectHelper;
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
        $this->flow_entry_body_html = null;
        $this->flow_entry_body_bb_code = null;
        $this->flow_entry_body_text = null;

        if ($object instanceof IFlowEntry || $object instanceof IFlowEntryArchive) {
            $this->flow_entry_body_html = $object->get_html();
            $this->flow_entry_body_bb_code = $object->get_bb_code();
            $this->flow_entry_body_text = $object->get_text();

        } else {

            if (is_object($object))
            {
                $this->flow_entry_body_bb_code = $object->flow_entry_body_bb_code?? null;
                $this->flow_entry_body_html = $object->flow_entry_body_html?? null;
                $this->flow_entry_body_text = $object->flow_entry_body_text?? null;
            }
            elseif (is_array($object) )
            {
                $this->flow_entry_body_bb_code = $object['flow_entry_body_bb_code']?? null;
                $this->flow_entry_body_html = $object['flow_entry_body_html']?? null;
                $this->flow_entry_body_text = $object['flow_entry_body_text']?? null;
            }
        }

        $this->set_body_bb_code($this->get_bb_code());

    }


    /**
     * returns folder with no trailing slash
     * @return string|null
     * @throws Exception
     */
    public function get_entry_folder() : ?string{

        if (!$this->flow_entry_guid) {return null;}
        $project_dir = $this->project->getFlowProjectFiles()->get_project_directory();
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
     * @param ?string $bb_code
     * @throws
     */
    public function set_body_bb_code(?string $bb_code) : void {
        if (empty($bb_code)) {
            $this->flow_entry_body_bb_code = null;
            $this->flow_entry_body_html = null;
            $this->flow_entry_body_text = null;
            return;
        }

        $bb_code = JsonHelper::to_utf8($bb_code);
        $origonal_bb_code = $bb_code;

        $this->flow_entry_body_bb_code = ProjectHelper::get_project_helper()->
                                            stub_from_file_paths($this->get_project()->getFlowProjectFiles(),$bb_code);


        //may need to convert from the stubs back to the full paths for the html !
        $nu_read_me = ProjectHelper::get_project_helper()->
                                            stub_to_file_paths($this->get_project()->getFlowProjectFiles(),$origonal_bb_code);
        $this->flow_entry_body_html = JsonHelper::html_from_bb_code($nu_read_me);
        $this->flow_entry_body_text = str_replace('&nbsp;',' ',strip_tags($this->flow_entry_body_html));

    }


    /**
     * @param FlowProject $project
     * @param FlowProject|null $new_project
     * @return IFlowEntry
     * @throws Exception
     */
    public function clone_with_missing_data(FlowProject $project,?FlowProject $new_project = null ) : IFlowEntry {

        $ret = parent::clone_with_missing_data($project,$new_project);
        $ret->set_body_bb_code($this->flow_entry_body_bb_code);
        return $ret;
    }

    public function save_entry(bool $b_do_transaction = false, bool $b_save_children = false): void
    {
        parent::save_entry($b_do_transaction,$b_save_children);
        $this->set_body_bb_code($this->get_bb_code());
        $db = static::get_connection();

        $db->update('flow_entries',[
                'flow_entry_body_bb_code'=> $this->get_bb_code(),
                'flow_entry_body_text'=> $this->get_text()
            ],
            [
                'id' => $this->get_id()
            ]
        );

    }


}