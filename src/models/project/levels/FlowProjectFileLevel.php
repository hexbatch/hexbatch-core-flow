<?php
namespace app\models\project\levels;

use app\helpers\ProjectHelper;
use app\hexlet\JsonHelper;
use app\hexlet\RecursiveClasses;
use app\models\project\FlowProjectFiles;
use app\models\project\IFlowProject;
use Exception;
use InvalidArgumentException;
use RuntimeException;

abstract class FlowProjectFileLevel extends FlowProjectUserLevelLevel {

    protected ?string $flow_project_readme_html;


    protected ?FlowProjectFiles $project_files;


    /**
     * @param null|array|object $object
     * @throws Exception
     */
    public function __construct($object=null)
    {
        parent::__construct($object);
        $this->flow_project_readme_html = null;
        $this->project_files = null;

        $this->get_html(); //sets the html var
    }

    /**
     * @return string|null
     * @throws Exception
     */
    protected function get_html_path() : ?string{
        $dir = $this->getFlowProjectFiles()->get_project_directory();
        if (empty($dir)) {return null;}
        $path = $dir . DIRECTORY_SEPARATOR . 'flow_project_readme_html.html';
        return $path;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function get_html() : ?string {
        if (!$this->flow_project_readme_html) {
            $path = $this->get_html_path();
            if (is_readable($path)){
                $this->flow_project_readme_html = file_get_contents($this->get_html_path());
                if ($this->flow_project_readme_html === false) {
                    throw new RuntimeException("Project html path exists but could not read");
                }
            } else {
                $this->flow_project_readme_html = null;
            }

        }
        return $this->flow_project_readme_html;

    }


    /**
     * @return FlowProjectFiles
     * @throws Exception
     */
    public function getFlowProjectFiles() : FlowProjectFiles {
        if (empty($this->project_files)) {
            $this->project_files = new FlowProjectFiles($this->flow_project_guid,$this->get_admin_user()->flow_user_guid);
        }
        return $this->project_files;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function delete_project_directory() : void {
        $folder_to_remove = $this->getFlowProjectFiles()->get_project_directory();
        if ($folder_to_remove) {
            RecursiveClasses::rrmdir($folder_to_remove);
        }
    }


    /**
     * files not written until save called
     * @param string $bb_code
     * @throws Exception
     */
    public function set_read_me(string $bb_code) : void  {
        if (mb_strlen($bb_code) > IFlowProject::MAX_SIZE_READ_ME_IN_CHARACTERS) {
            throw new InvalidArgumentException("bb code is too large");
        }
        $bb_code = JsonHelper::to_utf8($bb_code);
        $origonal_bb_code = $bb_code;

        $this->flow_project_readme_bb_code = ProjectHelper::get_project_helper()->
        stub_from_file_paths($this->getFlowProjectFiles(),$bb_code);


        //may need to convert from the stubs back to the full paths for the html !
        $nu_read_me = ProjectHelper::get_project_helper()->
        stub_to_file_paths($this->getFlowProjectFiles(),$origonal_bb_code);

        $this->flow_project_readme_html = JsonHelper::html_from_bb_code($nu_read_me);
        $this->flow_project_readme = str_replace('&nbsp;',' ',strip_tags($this->flow_project_readme_html));
    }

    /**
     * @return string
     * @throws Exception
     */
    public function get_read_me_bb_code_with_paths(): string {

        $resource_url = $this->getFlowProjectFiles()->get_resource_url().'/';
        $read_me_full = str_replace(FlowProjectFiles::RESOURCE_PATH_STUB,$resource_url,$this->flow_project_readme_bb_code);

        $file_url = $this->getFlowProjectFiles()->get_files_url().'/';
        $read_me_full = str_replace(FlowProjectFiles::FILES_PATH_STUB,$file_url,$read_me_full);

        return $read_me_full;
    }

    /**
     * @param $command
     * @return string
     * @throws Exception
     */
    protected function do_project_directory_command($command) :string  {
        $directory = $this->getFlowProjectFiles()->get_project_directory();
        $full_command = "cd $directory && $command";
        exec($full_command,$output,$result_code);
        if ($result_code) {
            throw new RuntimeException("Cannot do $command,  returned code of $result_code : " . implode("\n",$output));
        }
        return implode("\n",$output);
    }

    /**
     * @param bool $b_do_transaction
     * @return void
     * @throws Exception
     */
    public function destroy_project(bool $b_do_transaction = true) : void {

        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) { $db->beginTransaction();}
            parent::destroy_project(false);
            $this->delete_project_directory();
            if ($b_do_transaction && $db->inTransaction()) { $db->commit();}
        } catch (Exception $e) {
            if ($b_do_transaction && $db->inTransaction()) { $db->rollBack();}
            throw $e;
        }

    }

    public function save(bool $b_do_transaction = true): void
    {
        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) { $db->beginTransaction();}
            parent::save(false);


            $dir = $this->getFlowProjectFiles()->get_project_directory($b_already_created);


            $read_me_path_bb = $dir . DIRECTORY_SEPARATOR . 'flow_project_readme_bb_code.bbcode';
            $read_me_path_html = $this->get_html_path();
            $blurb_path = $dir . DIRECTORY_SEPARATOR . 'flow_project_blurb';
            $title_path = $dir . DIRECTORY_SEPARATOR . 'flow_project_title';

            $b_ok = file_put_contents($read_me_path_bb,$this->flow_project_readme_bb_code);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $read_me_path_bb");}

            $b_ok = file_put_contents($read_me_path_html,$this->flow_project_readme_html);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $read_me_path_html");}

            $b_ok = file_put_contents($blurb_path,$this->flow_project_blurb);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $blurb_path");}

            $b_ok = file_put_contents($title_path,$this->flow_project_title);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $title_path");}

            if ($b_do_transaction && $db->inTransaction()) { $db->commit();}
        } catch (Exception $e) {
            if ($b_do_transaction && $db->inTransaction()) { $db->rollBack();}
            throw $e;
        }
    }


}