<?php

namespace app\models\entry\levels;


use app\helpers\ProjectHelper;
use app\helpers\Utilities;
use app\hexlet\BBHelper;
use app\hexlet\WillFunctions;
use app\models\entry\archive\IFlowEntryArchive;
use app\models\entry_node\EntryNodeDocument;
use app\models\entry\FlowEntryYaml;
use app\models\entry\IFlowEntry;
use app\models\entry\IFlowEntryReadBasicProperties;
use app\models\project\IFlowProject;
use DirectoryIterator;
use Exception;
use InvalidArgumentException;
use JsonException;
use RuntimeException;


/**
 * All the public resources and files an entry uses is in the project resources folder
 * so the html is simply a file in the top project directory
 */
abstract class FlowEntryFiles extends FlowEntryBase  {



    protected ?string $flow_entry_body_html;
    protected ?string $flow_entry_body_bb_code;

    protected ?EntryNodeDocument $node_document ;

    public function  get_document() : EntryNodeDocument {
        if (!$this->node_document) {
            $this->node_document = new EntryNodeDocument($this);
        }
        return $this->node_document;
    }

    /**
     * @param array|object|IFlowEntry|IFlowEntryArchive|IFlowEntryReadBasicProperties|null $object
     * @param IFlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?IFlowProject $project){
        parent::__construct($object,$project);
        $this->flow_entry_body_html = null;
        $this->flow_entry_body_bb_code = null;

        if ($object instanceof IFlowEntry || $object instanceof IFlowEntryArchive) {
            $this->flow_entry_body_html = $object->get_html();
            $this->flow_entry_body_bb_code = $object->get_bb_code();

        } else {
            if (is_array($object)) {
                $object = Utilities::convert_to_object($object);
            }

            if (is_object($object))
            {
                $this->flow_entry_body_bb_code = $object->flow_entry_body_bb_code?? null;
                $this->flow_entry_body_html = $object->flow_entry_body_html?? null;
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


    /**
     * @throws JsonException
     */
    public function deduce_existing_entry_folder() : ?string {
        $calculated_path = $this->get_calculated_entry_folder();
        if (!$calculated_path) {return null;}
        if (is_dir($calculated_path)) {
            //look inside of it
            $yaml = FlowEntryYaml::maybe_read_yaml_in_folder($calculated_path,$this->get_project());
            if ($yaml) {return $calculated_path;}
        }

        $project_dir = $this->project->get_project_directory();
        $older_version_calculated_path = $project_dir . DIRECTORY_SEPARATOR . static::ENTRY_FOLDER_PREFIX . $this->flow_entry_guid;
        if (is_dir($older_version_calculated_path)) {
            $yaml = FlowEntryYaml::maybe_read_yaml_in_folder($older_version_calculated_path,$this->get_project());
            if ($yaml) {return $older_version_calculated_path;}
        }

        //find any top level directory that has this guid in it

        $dir = new DirectoryIterator($project_dir);

        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $maybe_path =   $fileinfo->getPathname();
                $file_name =   $fileinfo->getFilename();
                if (str_contains($file_name, $this->get_guid())) {
                    //look inside of it
                    $yaml = FlowEntryYaml::maybe_read_yaml_in_folder($maybe_path,$this->get_project());
                    if ($yaml) {return $maybe_path;}
                }
            }
        }

        //if not guid, look for name

        $dir = new DirectoryIterator($project_dir);

        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $maybe_path =   $fileinfo->getPathname();
                $yaml = FlowEntryYaml::maybe_read_yaml_in_folder($maybe_path,$this->get_project());
                if ($yaml) {
                    if ($yaml->get_title() === $this->get_title()) {
                        return $maybe_path;
                    }
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
     * @throws JsonException
     */
    public function get_entry_folder(?string $new_folder_name = null) : ?string{

        $calculated_path = $this->get_calculated_entry_folder();
        if (!$calculated_path) {return null;}
        //see if existing folder
        $deduced_path = $this->deduce_existing_entry_folder();

        //if no existing path found, and we are not using the given folder, then make default calculated and return with it
        if (!$new_folder_name) {
            if ($deduced_path) {
                if ($deduced_path === $calculated_path) {
                    return realpath($deduced_path);
                } else {
                    //rename to calculated
                    $b_ok = rename($deduced_path,$calculated_path);
                    if (!$b_ok) {
                        throw new RuntimeException("[get_entry_folder] Could not rename dir from ".
                            "$deduced_path, to $calculated_path");
                    }
                    if (!is_readable($calculated_path)) {
                        throw new RuntimeException("Could not find folder $calculated_path after renaming it from $deduced_path");
                    }
                    return realpath($calculated_path);
                }

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
     * @param bool $b_try_file_first
     * @return string|null
     * @throws Exception
     */
    public function get_html(bool $b_try_file_first = true) : ?string {
        if (!$this->flow_entry_body_html) {
            $path = $this->get_html_path();
            if ($b_try_file_first && is_readable($path)){
                $this->flow_entry_body_html = file_get_contents($this->get_html_path());
                if ($this->flow_entry_body_html === false) {
                    throw new RuntimeException("Entry html path exists but could not read");
                }
            } else {
                //may need to convert from the stubs back to the full paths for the html !
                $nu_read_me = ProjectHelper::get_project_helper()->
                stub_to_file_paths($this->get_project(),$this->get_bb_code());
                $this->flow_entry_body_html = BBHelper::html_from_bb_code($nu_read_me);
            }

        }
        return $this->flow_entry_body_html;

    }

    public function get_bb_code(): ?string { return $this->flow_entry_body_bb_code;}


    /**
     * gets the full file path of the generated html for the entry
     * @return string|null
     * @throws Exception
     */
    public function get_html_path() : ?string{
        $base_path = $this->get_entry_folder();
        if (!$base_path) {return null;}
        $path = $base_path . DIRECTORY_SEPARATOR . IFlowEntryArchive::HTML_FILE_NAME;
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
            return;
        }

        $bb_code = Utilities::to_utf8($bb_code);

        $this->flow_entry_body_bb_code = ProjectHelper::get_project_helper()->
                                            stub_from_file_paths($this->get_project(),$bb_code);



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
        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) { $db->beginTransaction();}


            parent::save_entry(false,$b_save_children);



            $this->node_document = new EntryNodeDocument($this);
            $this->node_document->save();

            $bb_code_with_updated_guids = $this->node_document->get_top_node_of_entry()?->get_as_bb_code();
            $this->set_body_bb_code($bb_code_with_updated_guids);

            $db->update('flow_entries',[
                'flow_entry_body_bb_code'=> $this->get_bb_code(),
            ],
                [
                    'id' => $this->get_id()
                ]
            );

            if ($b_do_transaction && $db->inTransaction()) { $db->commit();}
        } catch (Exception $e) {
            if ($b_do_transaction && $db->inTransaction()) { $db->rollBack();}
            throw $e;
        }







    }


}