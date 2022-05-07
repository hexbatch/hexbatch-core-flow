<?php

namespace app\models\entry;



use app\helpers\Utilities;
use app\hexlet\RecursiveClasses;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\entry\archive\IFlowEntryArchive;
use app\models\project\IFlowProject;
use Carbon\Carbon;
use DirectoryIterator;
use InvalidArgumentException;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use PDO;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class FlowEntryYaml extends FlowBase implements JsonSerializable,IFlowEntryReadBasicProperties {

    const FILENAME_TO_MARK_INVALID = '.flow-ignored';

    protected ?string $entry_name;
    protected ?string $flow_entry_guid;
    protected ?string $flow_entry_parent_guid;
    protected ?string $flow_project_guid;
    protected ?int $entry_created_at_ts;
    protected ?int $entry_updated_at_ts;
    protected ?string $human_date_time;
    protected ?string $folder_hash;
    /**
     * @var FlowEntryYaml[] $dese_child_entries
     */
    protected array $dese_child_entries = [];

    protected ?string $folder_path;

    public function get_parent_guid(): ?string {
       return $this->flow_entry_parent_guid ;
    }

    public function get_parent_id(): ?int{
        return null ;
    }

    public function get_created_at_ts(): ?int {
        return $this->entry_created_at_ts ;
    }

    public function get_updated_at_ts(): ?int {
        return $this->entry_updated_at_ts ;
    }

    public function get_id(): ?int {
        return null ;
    }

    public function get_guid(): ?string {
        return $this->flow_entry_guid ;
    }

    public function get_title(): ?string {
        return $this->entry_name ;
    }

    public function get_blurb(): ?string {
        return null ;
    }

    public function get_project_guid(): ?string {
        return $this->flow_project_guid ;
    }

    public function get_project_id(): ?int {
        return null ;
    }



    public function __construct($object,? IFlowProject $project = null ) {
        $this->folder_hash = null;
        $this->folder_path = null;
        if (is_array($object)) {
            $object = Utilities::convert_to_object($object);
        }
        foreach ($this as $key => $val) {
            if (!is_array($this->$key) && !is_bool($this->$key)) {
                $this->$key = null;
            }
        }

        if ($object instanceof IFlowEntry) {
            $this->entry_name = $object->get_title() ;
            $this->flow_entry_guid = $object->get_guid() ;
            $this->flow_entry_parent_guid = $object->get_parent_guid() ;
            $this->flow_project_guid = $object->get_project_guid() ;
            $this->entry_created_at_ts = $object->get_created_at_ts() ;
            $this->entry_updated_at_ts = $object->get_updated_at_ts() ;
            $this->human_date_time = Carbon::now()->toIso8601String() ;
            $this->dese_child_entries = [] ;
            foreach ($object->get_children() as $child_entry) {
                $this->dese_child_entries[] = new static($child_entry);
            }
            $this->folder_path = $object->deduce_existing_entry_folder();
        } else {
            foreach ($object as $key => $val) {
                if (property_exists($this,$key)) {
                    if ($key === 'child_entries') {
                        foreach ($val as $child_info) {
                            $this->dese_child_entries[] = new static($child_info,$project);
                        }
                    } else {
                        $this->$key = $val;
                    }
                }
            }
        }


        if (empty($this->entry_updated_at_ts)) {
            $this->entry_updated_at_ts = $this->entry_created_at_ts;
        }
        $this->setFolderPath($this->folder_path);

        if ($this->flow_entry_guid && ! WillFunctions::is_valid_guid_format($this->flow_entry_guid)) {
            throw new InvalidArgumentException("flow_entry_guid is not a valid guid: ".$this->flow_entry_guid);
        }

        if ($project) {$this->flow_project_guid = $project->get_project_guid();}
        if ($this->flow_project_guid && ! WillFunctions::is_valid_guid_format($this->flow_project_guid)) {
            throw new InvalidArgumentException("flow_project_guid is not a valid guid: ".$this->flow_project_guid);
        }

        if ($this->flow_entry_parent_guid && ! WillFunctions::is_valid_guid_format($this->flow_entry_parent_guid)) {
            throw new InvalidArgumentException("flow_entry_parent_guid is not a valid guid: ".$this->flow_entry_parent_guid);
        }
    }


    /**
     * @param string|null $folder_path
     */
    protected function setFolderPath(?string $folder_path): void
    {
        $this->folder_path = $folder_path;
        $this->folder_hash = $this->md5_hash_for_directory($folder_path);
    }


    public function is_valid() : bool {
        if (empty($this->entry_name)) {return false;}
        if (empty($this->flow_entry_guid)) {return false;}
        if (empty($this->flow_project_guid)) {return false;}
        return true;
    }

    public function get_folder_path() : ?string { return $this->folder_path;}

    public static function maybe_read_yaml_in_folder(string $folder_path,?IFlowProject $project = null) : ?FlowEntryYaml {
        if (empty($folder_path) || !is_dir($folder_path)) {return null;}
        $real_path = realpath($folder_path);
        if (!$real_path) {return null;}
        $maybe_yaml_path = $real_path. DIRECTORY_SEPARATOR . IFlowEntryArchive::BASE_YAML_FILE_NAME;
        if (!is_readable($maybe_yaml_path)) {
            return null;
        }

        $goods = Yaml::parseFile($maybe_yaml_path);
        $node = new static($goods,$project);
        $node->setFolderPath($maybe_yaml_path);
        if (!$node->is_valid()) {return null;}
        return $node;
    }

    public static function read_yaml_entry(IFlowEntry $e) : FlowEntryYaml {

        $maybe_folder = $e->deduce_existing_entry_folder();

        if (!$maybe_folder) {
            throw new RuntimeException("[read_yaml_entry] Could not read entry folder for ". $e->get_guid() .
                                                ' '. $e->get_title());
        }

        $yaml_path = $maybe_folder. DIRECTORY_SEPARATOR .  IFlowEntryArchive::BASE_YAML_FILE_NAME;
        if (!is_readable($yaml_path)) {
            throw new RuntimeException("[read_yaml_entry] Could not read $yaml_path");
        }
        $goods = Yaml::parseFile($yaml_path);
        $node = new static($goods,$e->get_project());
        $node->setFolderPath($yaml_path);
        if (!$node->is_valid()) {
            throw new RuntimeException("[read_yaml_entry] Cannot read enough information from the yaml path");
        }
        return $node;
    }

    public static function write_yaml_entry(IFlowEntry $e) : FlowEntryYaml {

        $stuff = new FlowEntryYaml($e);
        $stuff_yaml = Yaml::dump($stuff->toArray());

        $yaml_path = $e->get_entry_folder(). DIRECTORY_SEPARATOR . IFlowEntryArchive::BASE_YAML_FILE_NAME;
        $b_ok = file_put_contents($yaml_path,$stuff_yaml);
        if ($b_ok === false) {throw new RuntimeException("[write_yaml_entry] Could not write to $yaml_path");}
        return $stuff;
    }

    /**
     * @param IFlowProject $project
     * @return FlowEntryYaml[]
     */
    public static function get_yaml_data_from_directory(IFlowProject $project) :array  {
        $folder_path = $project->get_project_directory();
        if (!$folder_path) {
            throw new InvalidArgumentException("[scan_project_folder] Project folder cannot be found or made");
        }
        /**
         * @var array<string,FlowEntryYaml[]> $guid_hash
         */
        $guid_hash = [];
        $yaml_name = IFlowEntryArchive::BASE_YAML_FILE_NAME;
        $pattern = "/.+($yaml_name)\$/";
        $yaml_found =  RecursiveClasses::rsearch_for_paths($folder_path,$pattern);
        foreach ($yaml_found as $yaml_path) {
            if ($folder_path === dirname($yaml_path,2) ) {
                $goods = Yaml::parseFile($yaml_path);
                $node = new static($goods,$project);
                $node->setFolderPath($yaml_path);
                if ($node->is_valid()) {
                    if (!isset($guid_hash[$node->get_guid()])) {
                        $guid_hash[$node->get_guid()] = [];
                    }
                    $guid_hash[$node->get_guid()][] = $node;
                }
            }
        }

        //resolve duplicate guids in different folders, newest folder timestamp wins
        $ret = [];
        foreach ($guid_hash as $array_self) {
            if (count($array_self) === 1) {$ret[] = $array_self[0];}
            else {
                $max_updated = null;
                foreach ($array_self as $stang) {
                    if (!$max_updated) { $max_updated = $stang; } else {
                        if ($stang->entry_updated_at_ts > $max_updated->entry_updated_at_ts) {
                            $max_updated = $stang;
                        }
                    }
                }
                $ret[] = $max_updated;
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
                    e.id as flow_entry_id,
                    e.flow_entry_title as flow_entry_title,
                    e.created_at_ts as entry_created_at_ts,
                    UNIX_TIMESTAMP(e.updated_at) as entry_updated_at_ts,
                    HEX(e.flow_entry_guid) as flow_entry_guid,
                    HEX(fp.flow_project_guid) as flow_project_guid
                FROM flow_entries e 
                INNER JOIN flow_projects fp on e.flow_project_id = fp.id
                WHERE e.flow_project_id = ? AND e.flow_entry_parent_id IS NULL";

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
        $folder_path = $project->get_project_directory();
        if (!$folder_path) {
            throw new InvalidArgumentException("[scan_project_folder] Project folder cannot be found or made");
        }

        //make list of valid directories
        $valid = [
            $project->get_resource_directory(),
            $project->get_files_directory(),
            $project->get_project_directory() . DIRECTORY_SEPARATOR . '.git'
        ];

        if ($b_use_db) {
            $found_ones = static::get_yaml_data_from_database($project);
        } else {
            $found_ones = static::get_yaml_data_from_directory($project);
        }

        foreach ($found_ones as $foundling) {
            if ($foundling->get_folder_path()) {
                $valid[] = $foundling->get_folder_path();
            }
        }

        //remove mark from valid, if exists
        foreach ($valid as $maybe_path) {
            $maybe_ignore_path = $maybe_path.DIRECTORY_SEPARATOR.static::FILENAME_TO_MARK_INVALID;
            if (is_readable($maybe_ignore_path)) {
                unlink($maybe_ignore_path);
            }
        }

        //find all the top level directories that are not in the valid array, and add the mark to them
        $dir = new DirectoryIterator($folder_path);
        $invalid = [];
        foreach ($dir as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $maybe_path =   $fileinfo->getPathname();
                if (!in_array($maybe_path,$valid)) {
                    $invalid[] = $maybe_path;
                }
            }
        }

        foreach ($invalid as $invalid_folder) {
            $invalid_file_path = $invalid_folder.DIRECTORY_SEPARATOR.static::FILENAME_TO_MARK_INVALID;
            if (!is_readable($invalid_file_path)) {
                file_put_contents($invalid_file_path,Carbon::now()->toIso8601String());
            }

        }

        return $invalid;
    }

    #[ArrayShape(['entry_name' => "mixed", 'flow_entry_guid' => "mixed", 'flow_entry_parent_guid' => "mixed", 'flow_project_guid' => "mixed", 'entry_created_at_ts' => "mixed", 'entry_updated_at_ts' => "mixed", 'human_date_time' => "\null|string", 'folder_hash' => "\null|string", 'child_entries' => "\app\models\entry\FlowEntryYaml[]|array"])]
    public function jsonSerialize() : array
    {
       return $this->toArray();
    }

    #[ArrayShape(['entry_name' => "mixed", 'flow_entry_guid' => "mixed", 'flow_entry_parent_guid' => "mixed",
                    'flow_project_guid' => "mixed", 'entry_created_at_ts' => "mixed", 'entry_updated_at_ts' => "mixed",
                    'human_date_time' => "null|string", 'folder_hash' => "null|string",
                    'child_entries' => "\app\models\entry\FlowEntryYaml[]|array"])]
    public function toArray() : array  {
        return [
            'entry_name' => $this->entry_name,
            'flow_entry_guid' => $this->flow_entry_guid,
            'flow_entry_parent_guid' => $this->flow_entry_parent_guid,
            'flow_project_guid' => $this->flow_project_guid,
            'entry_created_at_ts' => $this->entry_created_at_ts,
            'entry_updated_at_ts' => $this->entry_updated_at_ts,
            'human_date_time' => $this->human_date_time,
            'folder_hash' => $this->folder_hash,
            'child_entries' => $this->dese_child_entries,
        ];
    }

    /**
     * @param string|null $directory
     * @return string|null
     * @author https://jonlabelle.com/snippets/view/php/generate-md5-hash-for-directory
     */
    protected function md5_hash_for_directory(?string $directory) : ?string
    {
        if (empty($directory)) {
            return null;
        }

        if (! is_dir($directory))
        {
            return null;
        }

        $files = array();
        $dir = dir($directory);

        while (false !== ($file = $dir->read()))
        {
            if ($file != '.' and $file != '..')
            {
                if (is_dir($directory . '/' . $file))
                {
                    $files[] = static::md5_hash_for_directory($directory . '/' . $file);
                }
                else
                {
                    $files[] = md5_file($directory . '/' . $file);
                }
            }
        }

        $dir->close();

        $ret =  md5(implode('', $files));
        if (empty($ret)) {$ret = null;}
        return $ret;
    }


}