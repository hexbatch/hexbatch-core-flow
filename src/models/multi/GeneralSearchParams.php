<?php

namespace app\models\multi;


class GeneralSearchParams   {


    public ?string $title;
    public ?string $guid;
    public ?string $type;
    public ?int $created_at_ts;


    function __construct($object=null){
        $this->title = null;
        $this->guid = null;
        $this->type = null;
        $this->created_at_ts = null;
    }

}