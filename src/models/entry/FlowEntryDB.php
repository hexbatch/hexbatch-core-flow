<?php

namespace app\models\entry;


use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\entry\brief\BriefFlowEntry;
use app\models\project\FlowProject;
use BlueM\Tree;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;


class FlowEntryDB extends FlowBase implements JsonSerializable {

    const LENGTH_ENTRY_TITLE = 40;
    

    public ?int $flow_entry_id;
    public ?int $flow_entry_parent_id;
    public ?int $flow_project_id;
    public ?int $entry_created_at_ts;
    public ?int $entry_updated_at_ts;
    public ?string $flow_entry_guid;
    public ?string $flow_entry_title;
    public ?string $flow_entry_blurb;
    public ?string $flow_entry_body_bb_code;
    public ?string $flow_entry_body_bb_text;

    public ?string $flow_project_guid;
    public ?string $flow_entry_parent_guid;

    /**
     * @var FlowEntryDB[] $child_entries
     */
    public array $child_entries;

    /**
     * @var string[] $child_guids
     */
    public array $child_guids;

    /**
     * @var int[]
     */
    public array $child_entry_ids;

    protected ?string $child_id_list_as_string;


    /**
     * @var FlowEntryDB[] $member_entries
     */
    public array $member_entries;

    /**
     * @var string[] $member_guids
     */
    public array $member_guids;

    /**
     * @var int[]
     */
    public array $member_entry_ids;

    protected ?string $member_id_list_as_string;


    /**
     * @var FlowEntryDB|null $flow_entry_parent
     */
    public ?FlowEntryDB $flow_entry_parent;




    public function jsonSerialize(): array
    {

        if ($this->get_brief_json_flag()) {

            $brief = new BriefFlowEntry($this);
            return $brief->to_array();
        } else {

            return [
                "flow_entry_guid" => $this->flow_entry_guid,
                "flow_entry_parent_guid" => $this->flow_entry_guid,
                "flow_project_guid" => $this->flow_entry_guid,
                "entry_created_at_ts" => $this->entry_created_at_ts,
                "entry_updated_at_ts" => $this->entry_updated_at_ts,
                "flow_entry_title" => $this->flow_entry_title,
                "flow_entry_blurb" => $this->flow_entry_blurb,
                "flow_entry_body_bb_code" => $this->flow_entry_body_bb_code,
                "child_entries" => $this->child_entries,
                "member_guids" => $this->member_guids,

            ];
        }
    }





    public function __construct($object=null){


        $this->flow_entry_id = null;
        $this->flow_entry_parent_id = null;
        $this->flow_project_id = null;
        $this->entry_created_at_ts = null;
        $this->entry_updated_at_ts = null;
        $this->flow_entry_guid = null;
        $this->flow_entry_title = null;
        $this->flow_entry_blurb = null;
        $this->flow_entry_body_bb_code = null;
        $this->flow_entry_body_bb_text = null;
        $this->flow_project_guid = null;
        $this->flow_entry_parent_guid = null;
        $this->child_id_list_as_string = null;
        $this->member_id_list_as_string = null;
        $this->flow_entry_parent = null;

        $this->child_entries = [];
        $this->child_guids = [];
        $this->child_entry_ids = [];
        $this->member_entries = [];
        $this->member_guids = [];
        $this->member_entry_ids = [];

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if ($key === 'flow_entry_parent') {continue;}
            if ($key === 'child_entries') {continue;}
            if ($key === 'member_entries') {continue;}
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

        if (is_object($object) && property_exists($object,'flow_entry_parent') && !empty($object->flow_entry_parent)) {
            $parent_to_copy = $object->flow_tag_parent;
        } elseif (is_array($object) && array_key_exists('flow_entry_parent',$object) && !empty($object['flow_entry_parent'])) {
            $parent_to_copy  = $object['flow_entry_parent'];
        } else {
            $parent_to_copy = null;
        }
        if ($parent_to_copy) {
            $this->flow_entry_parent = new FlowEntryDB($parent_to_copy);
        }

        if (is_object($object) && property_exists($object,'child_entries') && is_array($object->child_entries)) {
            $members_to_copy = $object->child_entries;
        } elseif (is_array($object) && array_key_exists('child_entries',$object) && is_array($object['attributes'])) {
            $members_to_copy  = $object['child_entries'];
        } else {
            $members_to_copy = [];
        }
        if (count($members_to_copy)) {
            foreach ($members_to_copy as $att) {
                $this->child_entries[] = new FlowEntryDB($att);
            }
        }


        if (is_object($object) && property_exists($object,'member_entries') && is_array($object->member_entries)) {
            $members_to_copy = $object->member_entries;
        } elseif (is_array($object) && array_key_exists('member_entries',$object) && is_array($object['member_entries'])) {
            $members_to_copy  = $object['member_entries'];
        } else {
            $members_to_copy = [];
        }
        if (count($members_to_copy)) {
            foreach ($members_to_copy as $member) {
                $this->member_entries[] = new FlowEntryDB($member);
            }
        }


        if ($this->child_id_list_as_string) {
            $dat_ids = explode(',',$this->child_id_list_as_string);
            foreach ($dat_ids as $dat_id) {
                if (intval($dat_id)) {
                    $this->child_entry_ids[] = (int)$dat_id;
                }
            }
        }

        if ($this->member_id_list_as_string) {
            $dat_ids = explode(',',$this->member_id_list_as_string);
            foreach ($dat_ids as $dat_id) {
                if (intval($dat_id)) {
                    $this->member_entry_ids[] = (int)$dat_id;
                }
            }
        }

    }


    public static function check_valid_name($words) : bool  {
        $b_min_ok =  static::minimum_check_valid_name($words,static::LENGTH_ENTRY_TITLE);
        if (!$b_min_ok) {return false;}
        //no special punctuation
        if (preg_match('/[\'"<>`]/', $words, $output_array)) {
            return false;
        }
        return true;
    }



    /**
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false, bool $b_save_children = false) :void {
        $db = null;

        try {
            if (empty($this->flow_entry_title)) {
                throw new InvalidArgumentException("Entry Title cannot be empty");
            }

            $b_match = static::check_valid_name($this->flow_entry_title);
            if (!$b_match) {
                $max_len = static::LENGTH_ENTRY_TITLE;
                throw new InvalidArgumentException(
                    "Entry title invalid! ".
                    "First character cannot be a number. Name Cannot be greater than $max_len. ".
                    " Title cannot be a hex number greater than 25 and cannot be a decimal number. No quotes or greater or less than");
            }

            $this->flow_entry_title = JsonHelper::to_utf8($this->flow_entry_title);
            if (empty($this->flow_entry_blurb)) {
                throw new InvalidArgumentException("Entry Blurb cannot be empty");
            }
            $this->flow_entry_blurb = JsonHelper::to_utf8($this->flow_entry_blurb);


            $db = static::get_connection();



            if (!$this->flow_project_id && $this->flow_project_guid) {
                $this->flow_project_id = $db->cell(
                    "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                    $this->flow_project_guid);
            }

            if(  !$this->flow_project_id) {
                throw new InvalidArgumentException("When saving an entry for the first time, need its project id or guid");
            }

            if (!$this->flow_entry_parent_id && $this->flow_entry_parent_guid) {
                $this->flow_entry_parent_id = $db->cell(
                    "SELECT id  FROM flow_entries WHERE flow_entry_guid = UNHEX(?)",
                    $this->flow_entry_parent_guid);
            }

            if (empty($this->flow_entry_parent_id)) {$this->flow_entry_parent_id = null;}


            $save_info = [
                'flow_project_id' => $this->flow_project_id,
                'flow_entry_parent_id' => $this->flow_entry_parent_id,
                'flow_entry_title' => $this->flow_entry_title,
                'flow_entry_blurb' => $this->flow_entry_blurb,
                'flow_entry_body_bb_code' => $this->flow_entry_body_bb_code,
                'flow_entry_body_bb_text' => $this->flow_entry_body_bb_text,
            ];


            if ($b_do_transaction) {$db->beginTransaction();}
            if ($this->flow_entry_guid && $this->flow_entry_id) {

                $db->update('flow_entries',$save_info,[
                    'id' => $this->flow_entry_id
                ]);

            }
            elseif ($this->flow_entry_guid) {
                $insert_sql = "
                    INSERT INTO flow_entries(flow_project_id, flow_entry_parent_id, created_at_ts, flow_entry_guid,
                                             flow_entry_title, flow_entry_blurb, flow_entry_body_bb_code, flow_entry_body_bb_text)  
                    VALUES (?,?,?,UNHEX(?),?,?,?,?)
                    ON DUPLICATE KEY UPDATE flow_project_id =           VALUES(flow_project_id),
                                            flow_entry_parent_id =      VALUES(flow_entry_parent_id),
                                            flow_entry_guid =           VALUES(flow_entry_guid) ,      
                                            flow_entry_title =          VALUES(flow_entry_title) ,      
                                            flow_entry_blurb =          VALUES(flow_entry_blurb) ,      
                                            flow_entry_body_bb_code =   VALUES(flow_entry_body_bb_code) ,      
                                            flow_entry_body_bb_text =   VALUES(flow_entry_body_bb_text)       
                ";
                $insert_params = [
                    $this->flow_project_id,
                    $this->flow_entry_parent_id,
                    $this->entry_created_at_ts,
                    $this->flow_entry_guid,
                    $this->flow_entry_title,
                    $this->flow_entry_blurb,
                    $this->flow_entry_body_bb_code,
                    $this->flow_entry_body_bb_text
                ];
                $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
                $this->flow_entry_id = $db->lastInsertId();
            }
            else {
                $db->insert('flow_entries',$save_info);
                $this->flow_entry_id = $db->lastInsertId();
            }

            if (!$this->flow_entry_guid) {
                $this->flow_entry_guid = $db->cell(
                    "SELECT HEX(flow_entry_guid) as flow_entry_guid FROM flow_entries WHERE id = ?",
                    $this->flow_entry_id);

                if (!$this->flow_entry_guid) {
                    throw new RuntimeException("Could not get entry guid using id of ". $this->flow_entry_id);
                }
            }

            if ($b_save_children) {
                foreach ($this->child_entries as $child_entry) {
                    $child_entry->flow_entry_parent_id = $this->flow_entry_id;
                    $child_entry->save();
                }
            }


            if ($b_do_transaction) {$db->commit(); }


        } catch (Exception $e) {
            if ($b_do_transaction && $db) { $db->rollBack(); }
            static::get_logger()->alert("Entry DB model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * @param FlowProject $project
     * @return FlowEntryDB
     * @throws Exception
     */
    public function fetch_this(FlowProject $project) : FlowEntryDB {
        WillFunctions::will_do_nothing($project);
        if (empty($this->flow_entry_id) && empty($this->flow_entry_guid)) {
            $me = new FlowEntryDB($this); //new to db
            return $me;
        }
        $search = new FlowEntrySearchParams();
        if ($this->flow_entry_guid) {
            $search->entry_guids[] = $this->flow_entry_guid;
        } elseif ($this->flow_entry_id) {
            $search->entry_ids[] = $this->flow_entry_id;
        }

        $me_array = FlowEntrySearch::search($search);
        if (empty($me_array)) {
            throw new InvalidArgumentException("Entry is not found from guid of $this->flow_entry_guid or id of $this->flow_entry_id");
        }
        $me = $me_array[0];
        return $me;
    }


    /**
     * @param FlowProject $project
     * @return FlowEntryDB
     * @throws Exception
     */
    public function clone_with_missing_data(FlowProject $project) : FlowEntryDB
    {
        WillFunctions::will_do_nothing($project);
        $me = $this->fetch_this($project);
        if (empty($me->flow_entry_id) && empty($me->flow_entry_guid)) {
            return $me;
        }
        //clear out the settable ids in the $me, if not set in this
        //set new data for this, overwriting the old
        if ($me->flow_entry_parent_id && !$this->flow_entry_parent_guid) {
            $me->flow_entry_parent_id = null;
            $me->flow_entry_parent_guid = null;
        }

        if (!$this->flow_entry_parent_id && $this->flow_entry_parent_guid) {
            $me->flow_entry_parent_id = null;
            $me->flow_entry_parent_guid = $this->flow_entry_parent_guid;
        }

        $me->flow_entry_title = $this->flow_entry_title;
        $me->flow_entry_blurb = $this->flow_entry_blurb;
        $me->flow_entry_body_bb_code = $this->flow_entry_body_bb_code;
        $me->flow_entry_body_bb_text = $this->flow_entry_body_bb_text;

        return $me;
    }

    public function delete_entry() {
        if (count($this->child_entries) || count($this->child_entry_ids)) {
            throw new InvalidArgumentException("Cannot delete entry, it has children");
        }
        $db = static::get_connection();
        if ($this->flow_entry_id) {
            $db->delete('flow_entries',['id'=>$this->flow_entry_id]);
        } else if($this->flow_entry_guid) {
            $sql = "DELETE FROM flow_entries WHERE flow_entry_guid = UNHEX(?)";
            $params = [$this->flow_entry_guid];
            $db->safeQuery($sql, $params, PDO::FETCH_BOTH, true);
        } else {
            throw new LogicException("Cannot delete flow_entries without an id or guid");
        }

    }


    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids() : array
    {
        $ret = [];
        if (empty($this->flow_project_id) && $this->flow_project_guid) { $ret[] = $this->flow_project_guid;}
        if (empty($this->flow_entry_parent_id) && $this->flow_entry_parent_guid) { $ret[] = $this->flow_entry_parent_guid;}


        return $ret;
    }

    /**
     * @@param  array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids)
    {

        if (empty($this->flow_project_id) && $this->flow_project_guid) {
            $this->flow_project_id = $guid_map_to_ids[$this->flow_project_guid] ?? null;}
        if (empty($this->flow_entry_parent_id) && $this->flow_entry_parent_guid) {
            $this->flow_entry_parent_id = $guid_map_to_ids[$this->flow_entry_parent_guid] ?? null;}

    }



    /**
     * sort parents before children
     * if there are tags with a parent set, but not in the array, then those are put at the end
     * @param FlowEntryDB[] $entry_array_to_sort
     * @return FlowEntryDB[]
     */
    public static function sort_array_by_parent(array $entry_array_to_sort) : array {

        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','tag'=>null];
        foreach ($entry_array_to_sort as $entry) {
            $data[] = [
                'id' => $entry->flow_entry_id,
                'parent' => $entry->flow_entry_parent_id??0,
                'title' => $entry->flow_entry_title,
                'entry'=>$entry
            ];
        }
        $tree = new Tree(
            $data,
            ['rootId' => -1]
        );

        $sorted_nodes =  $tree->getNodes();
        $ret = [];
        foreach ($sorted_nodes as $node) {
            $what =  $node->entry??null;
            if ($what) {$ret[] = $what;}
        }
        return $ret;
    }

}