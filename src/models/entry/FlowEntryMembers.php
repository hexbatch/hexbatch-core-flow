<?php

namespace app\models\entry;


use app\models\project\FlowProject;
use Exception;

/**
 * All the public resources and files an entry uses is in the project resources folder
 * so the html is simply a file in the top project directory
 */
abstract class FlowEntryMembers extends FlowEntryFiles  {

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
     * @param array|object|FlowEntryDB|null $object
     * @param FlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?FlowProject $project){
        parent::__construct($object,$project);
        $this->member_guids = [];
        $this->member_entries = [];
        $this->member_entry_ids = [];
        $this->member_id_list_as_string = null;

        $this->host_guids = [];
        $this->host_entries = [];
        $this->host_entry_ids = [];
        $this->host_id_list_as_string = null;
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



}