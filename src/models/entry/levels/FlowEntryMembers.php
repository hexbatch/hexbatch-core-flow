<?php

namespace app\models\entry\levels;


use app\hexlet\WillFunctions;
use app\models\entry\archive\IFlowEntryArchive;
use app\models\entry\IFlowEntry;
use app\models\project\IFlowProject;
use Exception;

/**
 * All the public resources and files an entry uses is in the project resources folder
 * so the html is simply a file in the top project directory
 */
abstract class FlowEntryMembers extends FlowEntryChildren  {

    /**
     * @var IFlowEntry[] $member_entries
     */
    public array $member_entries;

    /**
     * @var string[] $member_guids
     */
    public array $member_guids;


    /**
     * @var IFlowEntry[] $member_entries
     */
    public array $host_entries;

    /**
     * @var string[] $host_guids
     */
    public array $host_guids;

    /**
     * @var int[]
     */
    protected array $member_entry_ids;

    /**
     * @var int[]
     */
    protected array $host_entry_ids;

    protected ?string $member_id_list_as_string;
    protected ?string $host_id_list_as_string;


    /**
     * @param array|object|FlowEntryBase|IFlowEntryArchive|null $object
     * @param IFlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?IFlowProject $project){
        parent::__construct($object,$project);
        $this->member_guids = [];
        $this->member_entries = [];
        $this->member_entry_ids = [];
        $this->member_id_list_as_string = null;

        $this->host_guids = [];
        $this->host_entries = [];
        $this->host_entry_ids = [];
        $this->host_id_list_as_string = null;

        if ($object instanceof IFlowEntry ) {


            foreach ($object->get_members() as $member) {
                $this->member_entries[] = static::create_entry($project,$member);
            }

            foreach ($object->get_member_guids() as $member_guid) {
                $this->member_guids[] = $member_guid;
            }

            foreach ($object->get_children_ids() as $member_id) {
                $this->member_entry_ids[] = $member_id;
            }

        } else {


            if (is_object($object) && property_exists($object,'member_entries') ) {
                $members_to_copy = $object->member_entries;
            } elseif (is_array($object) && array_key_exists('member_entries',$object) && is_array($object['member_entries'])) {
                $members_to_copy  = $object['member_entries'];
            } else {
                $members_to_copy = [];
            }
            if (count($members_to_copy)) {
                foreach ($members_to_copy as $member) {
                    $this->member_entries[] = static::create_entry($project,$member);
                }
            }


            if (isset($this->member_id_list_as_string)) {
                $dat_ids = explode(',',$this->member_id_list_as_string);
                foreach ($dat_ids as $dat_id) {
                    if (intval($dat_id)) {
                        $this->member_entry_ids[] = (int)$dat_id;
                    }
                }
            }
        }
    }

    /**
     * @return IFlowEntry[]
     */
    public function get_members() : array {return $this->member_entries;}

    /**
     * @return string[]
     */
    public function get_member_guids() : array {return $this->member_guids;}


    public function get_host_guids() : array {return $this->host_guids;}

    /**
     * @return IFlowEntry[]
     */
    public function get_hosts() : array {return $this->host_entries;}


    /**
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false, bool $b_save_children = false) :void {

        parent::save_entry($b_do_transaction,$b_save_children);

        try {
            WillFunctions::will_do_action_later();

        } catch (Exception $e) {
            static::get_logger()->alert("Entry Member model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }

}