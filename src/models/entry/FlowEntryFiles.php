<?php

namespace app\models\entry;


use app\helpers\ProjectHelper;
use app\helpers\Utilities;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\archive\IFlowEntryArchive;
use app\models\project\IFlowProject;
use DirectoryIterator;
use Exception;
use InvalidArgumentException;
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
     * @param IFlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?IFlowProject $project){
        parent::__construct($object,$project);
        $this->flow_entry_body_html = null;
        $this->flow_entry_body_bb_code = null;
        $this->flow_entry_body_text = null;

        if ($object instanceof IFlowEntry || $object instanceof IFlowEntryArchive) {
            $this->flow_entry_body_html = $object->get_html();
            $this->flow_entry_body_bb_code = $object->get_bb_code();
            $this->flow_entry_body_text = $object->get_text();

        } else {
            if (is_array($object)) {
                $object = Utilities::convert_to_object($object);
            }

            if (is_object($object))
            {
                $this->flow_entry_body_bb_code = $object->flow_entry_body_bb_code?? null;
                $this->flow_entry_body_html = $object->flow_entry_body_html?? null;
                $this->flow_entry_body_text = $object->flow_entry_body_text?? null;
            }

        }

        $this->set_body_bb_code($this->get_bb_code());

    }

    public function get_calculated_entry_folder() : ?string {
        if (!$this->get_guid()) {return null;}
        if (!$this->get_title()) {return null;}
        $project_dir = $this->project->get_project_directory();
        if (empty($project_dir)) {return null;}
        $calculated_path = $project_dir . DIRECTORY_SEPARATOR . static::ENTRY_FOLDER_PREFIX . $this->get_title() .'-' . $this->flow_entry_guid;
        return $calculated_path;
    }


    public function deduce_existing_entry_folder() : ?string {
        $calculated_path = $this->get_calculated_entry_folder();
        if (!$calculated_path) {return null;}
        if (is_dir($calculated_path)) {
            //look inside of it
            $yaml = FlowEntryYaml::maybe_read_yaml_in_folder($calculated_path);
            if ($yaml) {return $calculated_path;}
        }

        $project_dir = $this->project->get_project_directory();
        $older_version_calculated_path = $project_dir . DIRECTORY_SEPARATOR . static::ENTRY_FOLDER_PREFIX . $this->flow_entry_guid;
        if (is_dir($older_version_calculated_path)) {
            $yaml = FlowEntryYaml::maybe_read_yaml_in_folder($older_version_calculated_path);
            if ($yaml) {return $older_version_calculated_path;}
        }

        //find any top level directory that has this guid in it

        $dir = new DirectoryIterator($project_dir);

        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $maybe_path =   $fileinfo->getPathname();
                $file_name =   $fileinfo->getFilename();
                if (strpos($file_name,$this->get_guid()) !== false) {
                    //look inside of it
                    $yaml = FlowEntryYaml::maybe_read_yaml_in_folder($maybe_path);
                    if ($yaml) {return $maybe_path;}
                }
            }
        }
        return null;
    }
    /**
     * returns folder with no trailing slash
     *
     * @param string|null $new_folder_name a folder name to use instead, can be name or path
     * @return string|null
     */
    public function get_entry_folder(?string $new_folder_name = null) : ?string{

        $calculated_path = $this->get_calculated_entry_folder();
        if (!$calculated_path) {return null;}
        //see if existing folder
        $deduced_path = $this->deduce_existing_entry_folder();

        //if no existing path found, and we are not using the given folder, then make default calculated and return with it
        if (!$new_folder_name) {
            if ($deduced_path) {
                return realpath($deduced_path);
            } else {
                if (!is_readable($calculated_path)) {
                    $check =  mkdir($calculated_path,0777,true);
                    if (!$check) {
                        throw new RuntimeException("[get_entry_folder] Could not create the calculated directory of $calculated_path");
                    }

                    if (!is_readable($calculated_path)) {
                        throw new RuntimeException("[get_entry_folder] Could not make a readable calculated directory of $calculated_path");
                    }
                }
                return realpath($calculated_path);
            }
        }



        $project_dir = $this->project->get_project_directory();

        if (dirname($new_folder_name)) {
            //is a path
            if (dirname($new_folder_name) !== $project_dir) {
                throw new InvalidArgumentException(
                    "[get_entry_folder]  must be in the project directory as top level folder:".
                    " given/project $new_folder_name $project_dir");
            }
            $given_entry_folder_path = $new_folder_name;
        } else {
            //is a folder name
            $given_entry_folder_path = $project_dir . DIRECTORY_SEPARATOR . $new_folder_name;
        }

        if (dirname($given_entry_folder_path) !== $project_dir) {
            throw new InvalidArgumentException(
                "[get_entry_folder]  must be in the project directory as top level folder:".
                " given/project $given_entry_folder_path $project_dir");
        }

        if (!is_readable($given_entry_folder_path) || !is_dir($given_entry_folder_path)) {

            //make new directory with given
            $check = mkdir($given_entry_folder_path, 0777, true);
            if (!$check) {
                throw new RuntimeException("[get_entry_folder]  Could not create the directory of $given_entry_folder_path");
            }

            if (!is_readable($given_entry_folder_path)) {
                throw new RuntimeException("[get_entry_folder]  Could not make a readable directory of $given_entry_folder_path");
            }
        }
        $given_entry_folder_path =  realpath($given_entry_folder_path);

        if ($deduced_path) {
            //rename to given
            $b_ok = rename($deduced_path,$given_entry_folder_path);
            if (!$b_ok) {
                throw new RuntimeException("[get_entry_folder] Could not rename dir from ".
                    "$deduced_path, to $given_entry_folder_path");
            }
        }
        return $given_entry_folder_path;


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
                                            stub_from_file_paths($this->get_project(),$bb_code);


        //may need to convert from the stubs back to the full paths for the html !
        $nu_read_me = ProjectHelper::get_project_helper()->
                                            stub_to_file_paths($this->get_project(),$origonal_bb_code);
        $this->flow_entry_body_html = JsonHelper::html_from_bb_code($nu_read_me);
        $this->flow_entry_body_text = str_replace('&nbsp;',' ',strip_tags($this->flow_entry_body_html));

    }


    /**
     * @param IFlowProject $project
     * @param IFlowProject|null $new_project
     * @return IFlowEntry
     * @throws Exception
     */
    public function clone_with_missing_data(IFlowProject $project,?IFlowProject $new_project = null ) : IFlowEntry {

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