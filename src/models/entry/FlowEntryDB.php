<?php

namespace app\models\entry;


use app\hexlet\JsonHelper;
use app\models\entry\brief\IFlowEntryBrief;
use app\models\project\FlowProject;
use BlueM\Tree;
use Exception;
use InvalidArgumentException;
use LogicException;
use PDO;
use RuntimeException;


abstract class FlowEntryDB extends FlowEntryBase {


    

    public ?int $flow_entry_id;
    public ?int $flow_entry_parent_id;
    public ?int $flow_project_id;
    public ?int $entry_created_at_ts;
    public ?int $entry_updated_at_ts;
    public ?string $flow_entry_guid;
    public ?string $flow_entry_title;
    public ?string $flow_entry_blurb;


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
     * @var FlowEntryDB|null $flow_entry_parent
     */
    public ?FlowEntryDB $flow_entry_parent;



    /**
     * @param array|object|IFlowEntry|null $object
     * @param FlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?FlowProject $project){


        $this->flow_entry_id = null;
        $this->flow_entry_parent_id = null;
        $this->flow_project_id = null;
        $this->entry_created_at_ts = null;
        $this->entry_updated_at_ts = null;
        $this->flow_entry_guid = null;
        $this->flow_entry_title = null;
        $this->flow_entry_blurb = null;
        $this->flow_project_guid = null;
        $this->flow_entry_parent_guid = null;
        $this->child_id_list_as_string = null;
        $this->flow_entry_parent = null;

        $this->child_entries = [];
        $this->child_guids = [];
        $this->child_entry_ids = [];

        if (empty($object)) {
            return;
        }

        if ($object instanceof IFlowEntry || $object instanceof IFlowEntryBrief) {
            $this->flow_entry_guid = $object->get_guid();
            $this->flow_entry_parent_guid = $object->get_parent_guid();
            $this->flow_project_guid = $object->get_project_guid();
            $this->entry_created_at_ts =  $object->get_created_at_ts();
            $this->entry_updated_at_ts = $object->get_updated_at_ts();
            $this->flow_entry_title = $object->get_title();
            $this->flow_entry_blurb = $object->get_blurb();
            $this->child_entries = [];

            foreach ($object->get_children() as $child) {
                $this->child_entries[] = static::create_entry($project,$child);
            }

            if ($object instanceof IFlowEntry ) {
                foreach ($object->get_children_guids() as $child_guid) {
                    $this->child_guids[] = $child_guid;
                }

                foreach ($object->get_children_id() as $child_id) {
                    $this->child_entry_ids[] = $child_id;
                }
            }


        } else {

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
                $this->flow_entry_parent = static::create_entry($project,$parent_to_copy);
            }

            if (is_object($object) && property_exists($object,'child_entries') && is_array($object->child_entries)) {
                $children_to_copy = $object->child_entries;
            } elseif (is_array($object) && array_key_exists('child_entries',$object) && is_array($object['attributes'])) {
                $children_to_copy  = $object['child_entries'];
            } else {
                $children_to_copy = [];
            }
            if (count($children_to_copy)) {
                foreach ($children_to_copy as $att) {
                    $this->child_entries[] = static::create_entry($project,$att);
                }
            }


            if (isset($this->child_id_list_as_string)) {
                $dat_ids = explode(',',$this->child_id_list_as_string);
                foreach ($dat_ids as $dat_id) {
                    if (intval($dat_id)) {
                        $this->child_entry_ids[] = (int)$dat_id;
                    }
                }
            }
        }

    }  //end constructor


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
    public function save_entry(bool $b_do_transaction = false, bool $b_save_children = false) :void {
        $db = null;

        try {
            if (empty($this->flow_entry_title)) {
                throw new InvalidArgumentException("Entry Title cannot be empty");
            }

            if (empty($this->get_blurb())) {
                throw new InvalidArgumentException("Entry Blurb cannot be empty");
            }


            $db = static::get_connection();


            if (!$this->get_id() && $this->get_guid()) {
                $this->flow_project_id = $db->cell(
                    "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                    $this->get_guid());
            }

            if(  !$this->get_id()) {
                throw new InvalidArgumentException("When saving an entry for the first time, need its project id or guid");
            }

            if (!$this->get_parent_id() && $this->get_parent_guid()) {
                $this->flow_entry_parent_id = $db->cell(
                    "SELECT id  FROM flow_entries WHERE flow_entry_guid = UNHEX(?)",
                    $this->get_parent_guid());
            }

            if (empty($this->get_parent_id())) {$this->set_parent_id(null) ;}


            $save_info = [
                'flow_project_id' => $this->get_project_id(),
                'flow_entry_parent_id' => $this->get_parent_id(),
                'flow_entry_title' => $this->get_title(),
                'flow_entry_blurb' => $this->get_blurb(),
                'flow_entry_body_bb_code' => $this->get_bb_code(),
                'flow_entry_body_bb_text' => $this->get_text(),
            ];


            if ($b_do_transaction) {$db->beginTransaction();}
            if ($this->get_guid() && $this->get_id()) {

                $db->update('flow_entries',$save_info,[
                    'id' => $this->get_id()
                ]);

            }
            elseif ($this->get_guid()) {
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
                    $this->get_project_id(),
                    $this->get_parent_id(),
                    $this->get_created_at_ts(),
                    $this->get_guid(),
                    $this->get_title(),
                    $this->get_blurb(),
                    $this->get_bb_code(),
                    $this->get_text()
                ];
                $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
                $this->set_id($db->lastInsertId());
            }
            else {
                $db->insert('flow_entries',$save_info);
                $this->set_id($db->lastInsertId());
            }

            if (!$this->flow_entry_guid) {
                $new_guid = $db->cell(
                    "SELECT HEX(flow_entry_guid) as flow_entry_guid FROM flow_entries WHERE id = ?",
                    $this->get_id());

                if (!$new_guid) {
                    throw new RuntimeException("Could not get entry guid using id of ". $this->get_id);
                }
                $this->set_guid($new_guid);
            }

            if ($b_save_children) {
                foreach ($this->child_entries as $child_entry) {
                    $child_entry->set_parent_id( $this->flow_entry_id);
                    $child_entry->save_entry();
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
        if (empty($this->get_id()) && empty($this->get_guid())) {
            $me = static::create_entry($project,$this); //new to db
            return $me;
        }
        $search = new FlowEntrySearchParams();
        if ($this->get_guid()) {
            $search->entry_guids[] = $this->get_guid();
        } elseif ($this->get_id()) {
            $search->entry_ids[] = $this->get_id();
        }

        $me_array = FlowEntrySearch::search($search);
        if (empty($me_array)) {
            $me_guid = $this->get_guid();
            $me_id = $this->get_id();
            throw new InvalidArgumentException(
                "Entry is not found from guid of $me_guid or id of $me_id");
        }
        $me = $me_array[0];
        return $me;
    }


    /**
     * @param FlowProject $project
     * @return FlowEntryDB
     * @throws Exception
     */
    public function clone_with_missing_data(FlowProject $project) : IFlowEntry
    {
        $me = $this->fetch_this($project);
        if (empty($me->get_id()) && empty($me->get_guid())) {
            return $me;
        }
        //clear out the settable ids in the $me, if not set in this
        //set new data for this, overwriting the old
        if ($me->get_parent_id() && !$this->get_parent_guid()) {
            $me->set_parent_id(null);
            $me->set_parent_guid(null) ;
        }

        if (!$this->get_parent_id() && $this->get_parent_guid()) {
            $me->set_parent_id(null);
            $me->set_parent_guid($this->get_parent_guid());
        }

        $me->set_title($this->get_title());
        $me->set_blurb($this->get_blurb()) ;
        $me->set_body_bb_code($this->get_bb_code()) ;

        return $me;
    }

    public function delete_entry() : void {
        if (count($this->get_children()) || count($this->get_children_id())) {
            throw new InvalidArgumentException("Cannot delete entry, it has children");
        }
        $db = static::get_connection();
        if ($this->get_id()) {
            $db->delete('flow_entries',['id'=>$this->get_id()]);
        } else if($this->get_guid()) {
            $sql = "DELETE FROM flow_entries WHERE flow_entry_guid = UNHEX(?)";
            $params = [$this->get_guid()];
            $db->safeQuery($sql, $params, PDO::FETCH_BOTH, true);
        } else {
            throw new LogicException("Cannot delete flow_entries without an id or guid");
        }

    }





    /**
     * sort parents before children
     * if there are tags with a parent set, but not in the array, then those are put at the end
     * @param IFlowEntry[] $entry_array_to_sort
     * @return IFlowEntry[]
     */
    public static function sort_array_by_parent(array $entry_array_to_sort) : array {

        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','tag'=>null];
        foreach ($entry_array_to_sort as $entry) {
            $data[] = [
                'id' => $entry->get_id(),
                'parent' => $entry->get_parent_id()??0,
                'title' => $entry->get_title(),
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

    public function get_parent_id(): ?int { return $this->flow_entry_parent_id;}
    public function get_parent_guid(): ?string { return $this->flow_entry_parent_guid;}
    public function get_parent(): ?IFlowEntry { return $this->flow_entry_parent;}
    public function get_created_at_ts(): ?int { return $this->entry_created_at_ts;}
    public function get_updated_at_ts(): ?int { return $this->entry_updated_at_ts;}
    public function get_guid(): ?string { return $this->flow_entry_guid;}
    public function get_id(): ?int { return $this->flow_entry_id;}
    public function get_title(): ?string { return $this->flow_entry_title;}
    public function get_blurb(): ?string { return $this->flow_entry_blurb;}

    public function get_project_guid(): ?string {return $this->flow_project_guid;}
    public function get_project_id(): ?int {return $this->flow_project_id;}

     /**
     * @return IFlowEntry[]
     */
    public function get_children(): array {return $this->child_entries;}

    /**
     * @return string[]
     */
    public function get_children_guids(): array {return $this->child_guids;}

    /**
     * @return int[]
     */
    public function get_children_id() : array {return $this->child_entry_ids;}


    public function set_id(?int $what): void {$this->flow_entry_id = $what;}
    public function set_guid(?string $what): void {$this->flow_entry_guid = $what;}
    public function set_parent_id(?int $what): void {$this->flow_entry_parent_id = $what;}
    public function set_parent_guid(?string $what): void {$this->flow_entry_parent_guid = $what;}
    public function set_project_id(?int $what): void {$this->flow_project_id = $what;}

    public function set_title(?string $what): void {
        $safe_what = JsonHelper::to_utf8($what);

        if (mb_strlen($safe_what > static::LENGTH_ENTRY_TITLE)) {
            throw new InvalidArgumentException(
                sprintf("Title Must be %s or less characters ",static::LENGTH_ENTRY_TITLE)
            );
        }

        $b_match = static::check_valid_name($safe_what);
        if (!$b_match) {
            throw new InvalidArgumentException(
                "Entry title invalid! ".
                "First character cannot be a number. ".
                " Title cannot be a hex number greater than 25 and cannot be a decimal number. No quotes or greater or less than");
        }

        $this->flow_entry_title = $safe_what;
    }

    public function set_blurb(?string $what): void {
        $safe_what = JsonHelper::to_utf8($what);

        if (mb_strlen($safe_what > static::LENGTH_ENTRY_BLURB)) {
            throw new InvalidArgumentException(
                sprintf("Blurb Must be %s or less characters ",static::LENGTH_ENTRY_BLURB)
            );
        }

        $this->flow_entry_blurb = $safe_what;
    }


    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids(): array
    {
        $ret = [];
        if (empty($this->get_project_id()) && $this->get_project_guid()) { $ret[] = $this->get_project_guid();}
        if (empty($this->get_parent_id()) && $this->get_parent_guid()) { $ret[] = $this->get_parent_guid();}

        return $ret;
    }

    /**
     * @param array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids): void
    {
        if (empty($this->get_project_id()) && $this->get_project_guid()) {
            $this->set_project_id( $guid_map_to_ids[$this->get_project_guid()] ?? null);}
        if (empty($this->flow_entry_parent_id) && $this->get_project_guid()) {
            $this->set_parent_id($guid_map_to_ids[$this->get_project_guid()] ?? null);}
    }

}