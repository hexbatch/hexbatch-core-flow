<?php

namespace app\models\entry;

use app\hexlet\JsonHelper;

class FlowEntrySearchParams {

    const DEFAULT_PAGE_SIZE = 30;
    const UNLIMITED_RESULTS_PER_PAGE = 100000;

    public ?string $owning_project_guid;
    public ?string $owning_user_guid;

    public ?string $full_text_term;

    /**
     * @var bool $flag_full_text_natural_languages   if true will do natural search, else binary search
     */
    public bool $flag_full_text_natural_languages = false;


    /**
     * @var bool $flag_top_entries_only   if true not return child entries (but will return members if no parent)
     */
    public bool $flag_top_entries_only = false;


    public ?string $parent_entry_guid = null;

    public ?string $host_entry_guid = null;

    /**
     * @var string[] $entry_guids
     */
    public array $entry_guids = [];

    /**
     * @var string[] $entry_titles
     */
    public array $entry_titles = [];

    /**
     * @var int[] $entry_ids
     */
    public array $entry_ids = [];


    protected int     $page = 1;
    protected int     $page_size =  self::DEFAULT_PAGE_SIZE;

    public function get_page() :int  {return $this->page;}
    public function get_page_size() :int  {return $this->page_size;}

    public function set_page(int $what) {
        $this->page = intval($what);
        if ($this->page < 1) {$this->page = 1;}
    }

    public function set_page_size( int $what) {
        $this->page_size = intval($what);
        if ($this->page_size < 1) { $this->page_size = 1;}
    }



    function __construct($object=null){
        $this->owning_project_guid = null;
        $this->owning_user_guid = null;
        $this->full_text_term = null;
        $this->parent_entry_guid = null;
        $this->host_entry_guid = null;
        $this->entry_guids = [];
        $this->entry_titles = [];
        $this->entry_ids = [];
        $this->flag_full_text_natural_languages = false;
        $this->flag_top_entries_only = false;
        $this->page = 1;
        $this->page_size = self::DEFAULT_PAGE_SIZE;

        if (empty($object)) {
            return;
        }

        if (is_array($object)) {
            $object = JsonHelper::fromString(JsonHelper::toString($object),true,false);
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }
    }
}