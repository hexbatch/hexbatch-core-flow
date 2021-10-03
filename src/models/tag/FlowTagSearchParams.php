<?php

namespace app\models\tag;

class FlowTagSearchParams {

    public ?string $project_guid;
    public ?string $tag_guid;
    public ?int $tag_id;

    function __construct(){
        $this->project_guid = null;
        $this->tag_guid = null;
        $this->tag_id = null;
    }
}