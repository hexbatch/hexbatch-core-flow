<?php

namespace app\models\entry;

use app\hexlet\JsonHelper;
use app\models\base\FlowBase;
use app\models\project\FlowProject;
use Carbon\Carbon;
use PDO;
use RuntimeException;

class FlowEntrySummary extends FlowBase {
    protected IFlowEntry $entry;
    /*
     * the guid and title and last modified timestamp, and sha1 of the folder
     */


    public ?string $title;
    public ?string $updated_at;
    public ?string $guid;
    public ?string $folder_hash;

    public function __construct($object) {
        if (is_array($object)) {
            $object = JsonHelper::fromString(JsonHelper::toString($object),true,false);
        }
        $this->title = $object->title??null;
        $this->guid = $object->guid??null;

        if (property_exists($object,'updated_at_ts')) {
            $this->updated_at = Carbon::createFromTimestamp($object->updated_at_ts, 'America/Chicago')->toCookieString();
        } else if  (property_exists($object,'updated_at')) {
            $this->updated_at = $object->updated_at;
        } else {
            $this->updated_at = null;
        }

        if (property_exists($object,'entry_folder_path')) {
            $hash = static::md5_hash_for_directory($object->entry_folder_path);
            if (!$hash) {
                throw new RuntimeException("Hash failed for directory");
            }
            $this->folder_hash = $hash;
        } else if  (property_exists($object,'folder_hash')) {
            $this->folder_hash = $object->folder_hash;
        } else {
            $this->folder_hash = null;
        }
    }

    public static function get_entry_summaries_for_project(FlowProject $project) :array {
        $db = static::get_connection();
        $args = [$project->id];
        $sql = "SELECT 
                    e.flow_entry_title as title,
                    UNIX_TIMESTAMP(e.updated_at) as updated_at_ts,
                    HEX(e.flow_entry_guid) as guid
                FROM flow_entries e WHERE e.flow_project_id = ?";

        $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
        $blank = FlowEntry::create_entry($project,null);
        $ret = [];
        foreach ($res as $row) {
            $guid = $row->guid;
            $blank->set_guid($guid);
            $row->entry_folder_path = $blank->get_entry_folder();
            $ret[] = new static($row);
        }
        return $ret;
    }

    /**
     * @param string $directory
     * @return false|string
     * @author https://jonlabelle.com/snippets/view/php/generate-md5-hash-for-directory
     */
    protected function md5_hash_for_directory(string $directory)
    {
        if (! is_dir($directory))
        {
            return false;
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

        return md5(implode('', $files));
    }
}