<?php

namespace app\models\tag;

class FlowTagSearchParams {

    public ?string $project_guid;

    /**
     * @var string[] $tag_guids
     */
    public array $tag_guids = [];

    /**
     * @var int[] $tag_ids
     */
    public array $tag_ids = [];

    function __construct(){
        $this->project_guid = null;
        $this->tag_guids = [];
        $this->tag_ids = [];
    }
}