<?php

namespace app\models\entry;


use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\entry\archive\FlowEntryArchive;
use app\models\entry\archive\IFlowEntryArchive;
use app\models\entry\public_json\FlowEntryJsonBase;
use app\models\entry\public_json\IFlowEntryJson;
use app\models\project\FlowProject;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use malkusch\lock\mutex\FlockMutex;
use PDO;
use RuntimeException;


abstract class FlowEntryBase extends FlowBase implements JsonSerializable,IFlowEntry {

    public function get_max_title_length() : int { return static::LENGTH_ENTRY_TITLE;}
    public function get_max_blurb_length() : int { return static::LENGTH_ENTRY_BLURB;}


    protected ?int $flow_entry_id;
    protected ?int $flow_entry_parent_id;
    protected ?int $flow_project_id;
    protected ?int $entry_created_at_ts;
    protected ?int $entry_updated_at_ts;
    protected ?string $flow_entry_guid;
    protected ?string $flow_entry_title;
    protected ?string $flow_entry_blurb;
    protected ?string $flow_project_guid;
    protected ?string $flow_entry_parent_guid;

    protected FlowProject $project;


    /**
     * @var IFlowEntry|null $flow_entry_parent
     */
    protected ?IFlowEntry $flow_entry_parent;

    /**
     * @var string[] $flow_entry_ancestor_guids
     */
    protected array $flow_entry_ancestor_guids = [];



    /**
     * @param array|object|IFlowEntry|IFlowEntryArchive|null $object
     * @param FlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?FlowProject $project){

        $this->project = $project;
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

        $this->flow_entry_parent = null;



        if (empty($object)) {
            return;
        }

        if ($object instanceof IFlowEntry || $object instanceof IFlowEntryArchive) {
            $this->flow_entry_guid = $object->get_guid();
            $this->flow_entry_parent_guid = $object->get_parent_guid();
            $this->flow_project_guid = $object->get_project_guid();
            $this->entry_created_at_ts =  $object->get_created_at_ts();
            $this->entry_updated_at_ts = $object->get_updated_at_ts();
            $this->flow_entry_title = $object->get_title();
            $this->flow_entry_blurb = $object->get_blurb();



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

        }

    }  //end constructor


    public static function check_valid_name($words) : bool  {
        $b_min_ok =  static::minimum_check_valid_name($words,static::LENGTH_ENTRY_TITLE);
        if (!$b_min_ok) {return false;}
        //no special punctuation
        if (preg_match('/[\'"<>`]/', $words, $output_array)) {
            WillFunctions::will_do_nothing($output_array);
            return false;
        }
        return true;
    }



    /**
     * @throws Exception
     */
    public function save_entry(bool $b_do_transaction = false, bool $b_save_children = false) :void {


        try {

            $this->validate_entry_before_save();

            $db = static::get_connection();


            if(  !$this->get_project()->id) {
                throw new InvalidArgumentException("When saving an entry for the first time, need its project id set");
            }

            $this->set_project_id($this->get_project()->id);
            $this->set_project_guid($this->get_project()->flow_project_guid);

            if (!$this->get_parent_id() && $this->get_parent_guid()) {
                $this->flow_entry_parent_id = $db->cell(
                    "SELECT id  FROM flow_entries WHERE flow_entry_guid = UNHEX(?)",
                    $this->get_parent_guid());
            }

            if (empty($this->get_parent_id())) {$this->set_parent_id(null) ;}


            $save_info = [
                'flow_project_id' => $this->get_project()->id,
                'flow_entry_parent_id' => $this->get_parent_id(),
                'flow_entry_title' => $this->get_title(),
                'flow_entry_blurb' => $this->get_blurb(),
                'flow_entry_body_bb_code' => $this->get_bb_code(),
                'flow_entry_body_text' => $this->get_text(),
            ];

            try {
                if ($b_do_transaction) {
                    $db->beginTransaction();
                }
                if ($this->get_guid() && $this->get_id()) {

                    $db->update('flow_entries', $save_info, [
                        'id' => $this->get_id()
                    ]);

                } elseif ($this->get_guid()) {
                    $insert_sql = "
                    INSERT INTO flow_entries(flow_project_id, flow_entry_parent_id, created_at_ts, flow_entry_guid,
                                             flow_entry_title, flow_entry_blurb, flow_entry_body_bb_code, flow_entry_body_text)  
                    VALUES (?,?,?,UNHEX(?),?,?,?,?)
                    ON DUPLICATE KEY UPDATE flow_project_id =           VALUES(flow_project_id),
                                            flow_entry_parent_id =      VALUES(flow_entry_parent_id),
                                            flow_entry_guid =           VALUES(flow_entry_guid) ,      
                                            flow_entry_title =          VALUES(flow_entry_title) ,      
                                            flow_entry_blurb =          VALUES(flow_entry_blurb) ,      
                                            flow_entry_body_bb_code =   VALUES(flow_entry_body_bb_code) ,      
                                            flow_entry_body_text =   VALUES(flow_entry_body_text)       
                ";
                    $insert_params = [
                        $this->get_project()->id,
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
                } else {
                    $db->insert('flow_entries', $save_info);
                    $this->set_id($db->lastInsertId());
                }

                $update_info = $db->row(
                    "SELECT 
                                        HEX(flow_entry_guid) as flow_entry_guid ,
                                        created_at_ts,
                                        UNIX_TIMESTAMP(updated_at) as updated_at_ts
                                  FROM flow_entries WHERE id = ?",
                    $this->get_id());

                if (empty($update_info)) {
                    throw new RuntimeException("Could not get entry refresh data using id of " . $this->get_id);
                }

                $this->set_guid($update_info['flow_entry_guid']);
                $this->set_created_at_ts($update_info['created_at_ts']);
                $this->set_updated_at_ts($update_info['updated_at_ts']);

                if ($b_save_children) {
                    /**
                     * @var IFlowEntry $child_entry
                     */
                    foreach ($this->child_entries as $child_entry) {
                        $child_entry->set_parent_id($this->flow_entry_id);
                        $child_entry->save_entry();
                    }
                }


                if ($b_do_transaction && $db->inTransaction()) {
                    $db->commit();
                }
            } catch (Exception $e) {
                if ($b_do_transaction &&   $db->inTransaction()) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
                }
                throw $e;
            }

        } catch (Exception $e) {
            static::get_logger()->alert("Entry DB model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }


    /**
     * @param FlowProject $project
     * @return IFlowEntry
     * @throws Exception
     */
    public function fetch_this(FlowProject $project) : IFlowEntry {
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
     * @param FlowProject|null $new_project
     * @return IFlowEntry
     * @throws Exception
     */
    public function clone_with_missing_data(FlowProject $project,?FlowProject $new_project = null) : IFlowEntry
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

        if ($new_project) {
            $me->project = $new_project;
            $me->set_project_guid($new_project->flow_project_guid);
            $me->set_project_id($new_project->id) ;
            $me->set_id(null);
            $me->set_guid(null);
        }

        return $me;
    }

    public function delete_entry() : void {
        if (count($this->get_children()) || count($this->get_children_ids())) {
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
        $this->on_after_delete_entry();

    }



    public function get_parent_id(): ?int { return $this->flow_entry_parent_id;}
    public function get_parent_guid(): ?string { return $this->flow_entry_parent_guid;}
    public function get_parent(): ?IFlowEntry { return $this->flow_entry_parent;}

    /**
     * @return string[]
     */
    public function get_ancestor_guids(): array {
        return $this->flow_entry_ancestor_guids;
    }


    public function get_created_at_ts(): ?int { return $this->entry_created_at_ts;}
    public function get_updated_at_ts(): ?int { return $this->entry_updated_at_ts;}
    public function get_guid(): ?string { return $this->flow_entry_guid;}
    public function get_id(): ?int { return $this->flow_entry_id;}
    public function get_title(): ?string { return $this->flow_entry_title;}
    public function get_blurb(): ?string { return $this->flow_entry_blurb;}

    public function get_project_guid(): ?string {return $this->flow_project_guid;}
    public function get_project_id(): ?int {return $this->flow_project_id;}
    public function get_project() : FlowProject {return $this->project;}




    public function set_id(?int $what): void {$this->flow_entry_id = $what;}
    public function set_guid(?string $what): void {$this->flow_entry_guid = $what;}
    public function set_parent_id(?int $what): void {$this->flow_entry_parent_id = $what;}
    public function set_parent_guid(?string $what): void {$this->flow_entry_parent_guid = $what;}
    public function set_project_id(?int $what): void {$this->flow_project_id = $what;}
    public function set_project_guid(?string $what) : void {$this->flow_project_guid = $what;}

    public function set_created_at_ts(?int $what) : void {
        if ($what <0 ) {throw new InvalidArgumentException("Timestamps cannot be negative");}
        $this->entry_created_at_ts = $what;
    }

    public function set_updated_at_ts(?int $what) : void {
        if ($what <0 ) {throw new InvalidArgumentException("Timestamps cannot be negative");}
        $this->entry_updated_at_ts = $what;
    }

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
     * Used to make json
     * @return IFlowEntryJson
     */
    public function to_public_json() : IFlowEntryJson  {
        return new FlowEntryJsonBase($this);
    }

    public function jsonSerialize() : array {
        return $this->to_public_json()->to_array();
    }





    /**
     * Write the entry state to the entry folder
     * @throws
     */
    public function store(): void {

        $mutex = new FlockMutex(fopen(__FILE__, "r"));
        $mutex->synchronized(function ()  {
            FlowEntryArchive::start_archive_write();
            FlowEntryArchive::create_archive($this)->write_archive(); //can have many children or members to also save if changed
            FlowEntryArchive::finish_archive_write();
            FlowEntryArchive::record_all_stored_entries($this->get_project());
        });

    }

    /**
     * Loads entries from the entry folder (does not use db)
     * if no guids listed, then will return an array of all
     * else will only return the guids asked for, if some or all missing will only return the found, if any
     * @param FlowProject $project
     * @param string[] $only_these_guids
     * @return IFlowEntry[]
     * @throws
     */
    public static function load(FlowProject $project,array $only_these_guids = []) : array {
        $archive_list = [];
        $guid_list = $only_these_guids;
        if (empty($guid_list)) {
            $guid_list = FlowEntryArchive::discover_all_archived_entries($project);
        }
        foreach ($guid_list as $guid) {
            $node = FlowEntry::create_entry($project,null);
            $node->set_guid($guid);
            $archive_list[] = FlowEntryArchive::create_archive($node);
        }
        $mutex = new FlockMutex(fopen(__FILE__, "r"));
        $mutex->synchronized(function ()  use(&$archive_list) {
            FlowEntryArchive::start_archive_write();
            /**
             * @var IFlowEntryArchive $archive
             */
            foreach ($archive_list as $archive) {
                $archive->read_archive();
            }
            FlowEntryArchive::finish_archive_write();
        });

        $archive_list_sorted = FlowEntryJsonBase::sort_array_by_parent($archive_list);
        return $archive_list_sorted;
    }

    /**
     * called before a save, any child can do logic and throw an exception to stop the save
     */
    public function validate_entry_before_save() :void {
        if (empty($this->flow_entry_title)) {
            throw new InvalidArgumentException("Entry Title cannot be empty");
        }

        if (empty($this->get_blurb())) {
            throw new InvalidArgumentException("Entry Blurb cannot be empty");
        }
    }

    /**
     * called after the save is made
     */
    public function on_after_save_entry() :void {
        WillFunctions::will_do_nothing("base method");
    }

    /**
     * called after the delete is done
     * @throws
     */
    public function on_after_delete_entry() :void {
        $mutex = new FlockMutex(fopen(__FILE__, "r"));
        $mutex->synchronized(function ()  {
            $archive = FlowEntryArchive::create_archive($this);
            $archive->delete_archive();
            FlowEntryArchive::record_all_stored_entries($this->get_project());
        });
    }

}