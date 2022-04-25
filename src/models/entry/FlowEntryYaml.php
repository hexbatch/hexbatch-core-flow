<?php

namespace app\models\entry;



use app\helpers\Utilities;
use app\hexlet\RecursiveClasses;
use app\models\base\FlowBase;
use app\models\entry\archive\IFlowEntryArchive;
use app\models\project\IFlowProject;
use Carbon\Carbon;
use DirectoryIterator;
use InvalidArgumentException;
use JsonSerializable;
use PDO;
use Symfony\Component\Yaml\Yaml;

class FlowEntryYaml extends FlowBase implements JsonSerializable {

    const FILENAME_TO_MARK_INVALID = '.flow-ignored';

    protected ?string $entry_name;
    protected ?string $flow_entry_guid;
    protected ?string $flow_entry_parent_guid;
    protected ?string $flow_project_guid;
    protected ?int $entry_created_at_ts;
    protected ?int $entry_updated_at_ts;
    protected ?string $human_date_time;
    /**
     * @var FlowEntryYaml[] $child_entries
     */
    protected array $child_entries = [];

    protected ?string $folder_path;

    public function __construct($object) {
        $this->folder_path = null;

        if (is_array($object)) {
            $object = Utilities::convert_to_object($object);
        }
        if ($object instanceof IFlowEntry) {
            $this->entry_name = $object->get_title() ;
            $this->flow_entry_guid = $object->get_guid() ;
            $this->flow_entry_parent_guid = $object->get_parent_guid() ;
            $this->flow_project_guid = $object->get_project_guid() ;
            $this->entry_created_at_ts = $object->get_created_at_ts() ;
             $this->entry_updated_at_ts = $object->get_updated_at_ts() ;
            $this->human_date_time = Carbon::now()->toIso8601String() ;
            $this->child_entries = [] ;
            foreach ($object->get_children() as $child_entry) {
                $this->child_entries[] = new static($child_entry);
            }
            $this->folder_path = realpath(dirname($object->get_entry_folder()));
        }

        foreach ($this as $key => $val) {
            if (!is_array($this->$key) && !is_bool($this->$key)) {
                $this->$key = null;
            }
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                if ($key === 'child_entries') {
                    foreach ($val as $child_info) {
                        $this->child_entries[] = new static($child_info);
                    }
                } else {
                    $this->$key = $val;
                }

            }
        }
    }

    public function is_valid() : bool {
        if (empty($this->entry_name)) {return false;}
        if (empty($this->flow_entry_guid)) {return false;}
        if (empty($this->flow_project_guid)) {return false;}
        if (empty($this->folder_path)) {return false;}
        return true;
    }

    public function get_folder_path() : ?string { return $this->folder_path;}

    /**
     * @param IFlowProject $project
     * @return FlowEntryYaml[]
     */
    public static function get_yaml_data_from_directory(IFlowProject $project) :array  {
        $folder_path = $project->get_project_directory($b_already_created);
        if (!$folder_path) {
            throw new InvalidArgumentException("[scan_project_folder] Project folder cannot be found or made");
        }
        $yaml_name = IFlowEntryArchive::BASE_YAML_FILE_NAME;
        $pattern = "/.+($yaml_name)\$/";
        $yaml_found =  RecursiveClasses::rsearch_for_paths($folder_path,$pattern);
        $ret  = [];
        foreach ($yaml_found as $yaml_path) {
            if ($folder_path === dirname($yaml_path) ) {
                $goods = Yaml::parseFile($yaml_path);
                $node = new static($goods);
                $node->folder_path = $yaml_path;
                if ($node->is_valid()) {
                    $ret[] = $node;
                }
            }
        }

        return $ret;

    }


    /**
     * @param IFlowProject $project
     * @return FlowEntryYaml[]
     */
    public static function get_yaml_data_from_database(IFlowProject $project) :array {
        $db = static::get_connection();
        $args = [$project->get_id()];
        $sql = "SELECT 
                    e.flow_entry_title as title,
                    UNIX_TIMESTAMP(e.updated_at) as updated_at_ts,
                    HEX(e.flow_entry_guid) as guid
                FROM flow_entries e WHERE e.flow_project_id = ?";

        $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
        $entries = [];
        foreach ($res as $row) {
            $entries[] = FlowEntry::create_entry($project,$row);
        }

        $ret = [];
        foreach ($entries as $entry) {
            $ret[] = new static($entry);
        }
        return $ret;
    }

    /**
     *
     * @param IFlowProject $project
     * @param bool $b_use_db  if true, valid are only what are now in db, else any valid entry yaml is ok
     * @return string[]  returns array of invalid folder paths
     */
    public static function mark_invalid_folders_in_project_folder(IFlowProject $project,bool $b_use_db ) :array
    {
        $folder_path = $project->get_project_directory($b_already_created);
        if (!$folder_path) {
            throw new InvalidArgumentException("[scan_project_folder] Project folder cannot be found or made");
        }
        //make list of valid directories
        $valid = [
            $project->get_resource_directory(),
            $project->get_files_directory(),
        ];

        if ($b_use_db) {
            $found_ones = static::get_yaml_data_from_database($project);
        } else {
            $found_ones = static::get_yaml_data_from_directory($project);
        }

        foreach ($found_ones as $foundling) {
            $valid[] = $foundling->get_folder_path();
        }

        //find all the top level directories that are not in the valid array, and add the mark to them
        $dir = new DirectoryIterator($folder_path);
        $invalid = [];
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $maybe_path =   $fileinfo->getFilename();
                if (!in_array($maybe_path,$valid)) {
                    $invalid[] = $maybe_path;
                }
            }
        }

        foreach ($invalid as $invalid_folder) {
            file_put_contents($invalid_folder.DIRECTORY_SEPARATOR.static::FILENAME_TO_MARK_INVALID,Carbon::now()->toIso8601String());
        }

        return $invalid;
    }

    public function jsonSerialize() : array
    {
        return [
            'entry_name' => $this->entry_name,
            'flow_entry_guid' => $this->flow_entry_guid,
            'flow_entry_parent_guid' => $this->flow_entry_parent_guid,
            'flow_project_guid' => $this->flow_project_guid,
            'entry_created_at_ts' => $this->entry_created_at_ts,
            'entry_updated_at_ts' => $this->entry_updated_at_ts,
            'human_date_time' => $this->human_date_time,
            'child_entries' => $this->child_entries,
        ];

    }
}