<?php

namespace app\models\entry;


use app\hexlet\JsonHelper;
use app\models\entry\brief\IFlowEntryBrief;
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

    public ?FlowProject $project;


    /**
     * @param array|object|IFlowEntry|IFlowEntryBrief|null $object
     * @param FlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?FlowProject $project){
        parent::__construct($object,$project);
        $this->project = $project;
        $this->flow_entry_body_html = $this->get_html();
    }



    /**
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false, bool $b_save_children = false) :void {

        parent::save_entry($b_do_transaction,$b_save_children);

        try {

            $path_html = $this->get_html_path();
            $b_ok = file_put_contents($path_html,$this->flow_entry_body_html);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $path_html");}

        } catch (Exception $e) {
            static::get_logger()->alert("Entry Files model cannot save ",['exception'=>$e]);
            throw $e;
        }
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
     * @return string|null
     * @throws Exception
     */
    public function get_html_path() : ?string{
        if (!$this->project) {return null;}
        if (!$this->flow_entry_guid) {return null;}
        $project_dir = $this->project->get_project_directory();
        if (empty($project_dir)) {return null;}
        $path = $project_dir . DIRECTORY_SEPARATOR . "entry-$this->flow_entry_guid.html";
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