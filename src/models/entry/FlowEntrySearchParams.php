<?php

namespace app\models\entry;

use app\hexlet\JsonHelper;
use app\models\base\SearchParamBase;

class FlowEntrySearchParams extends SearchParamBase {

    const DEFAULT_PAGE_SIZE = 30;

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






    function __construct($object=null){
        parent::__construct();
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

    public function setOwningProjectGuid(?string $project_guid) : FlowEntrySearchParams {
        $this->owning_project_guid = $project_guid;
        return $this;
    }

    function addGuidsOrNames(mixed $thing) : FlowEntrySearchParams{
        if ($thing instanceof IFlowEntry) {
            $this->entry_guids[] = $thing->get_guid();
        } else {
            $filter = [];
            if (is_array($thing)) {
                foreach ($thing as $thang ) {
                    if ($thang instanceof IFlowEntry) {
                        $this->entry_guids[] = $thang->get_guid();
                    } else {
                        $filter[] = $thang;
                    }
                }
            } else {
                $filter = $thing;
            }
            $what = static::validate_cast_guid_array($filter,false);
            $this->entry_guids = array_unique(array_merge($this->entry_guids,$what));

            $what = static::validate_cast_name_array($filter,false,false);
            $this->entry_titles = array_unique(array_merge($this->entry_titles,$what));
        }
        return $this;
    }
}