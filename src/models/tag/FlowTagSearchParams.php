<?php

namespace app\models\tag;

use app\models\base\SearchParamBase;

class FlowTagSearchParams  extends SearchParamBase {

    public ?string $owning_project_guid;
    public ?string $tag_name_term;

    /**
     * @var bool $flag_get_applied   if true will also get the applied in the set of tags found
     */
    public bool $flag_get_applied = false;

    /**
     * @var string[] $tag_guids
     */
    public array $tag_guids = [];

    /**
     * @var int[] $tag_ids
     */
    public array $tag_ids = [];

    public array $only_applied_to_guids = [];

    public array $not_applied_to_guids = [];

    function __construct(){
        $this->owning_project_guid = null;
        $this->tag_name_term = null;
        $this->tag_guids = [];
        $this->tag_ids = [];
        $this->flag_get_applied = false;
        $this->only_applied_to_guids = [];
        $this->not_applied_to_guids = [];
    }
}