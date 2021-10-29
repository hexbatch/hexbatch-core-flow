<?php

namespace app\models\multi;

class GeneralSearchParams   {


    public ?string $title;

    /**
     * @var string[] $guids
     */
    public array $guids = [];

    /**
     * @var string[] $types
     */
    public array $types;

    public ?int $created_at_ts;




    function __construct(){
        $this->title = null;
        $this->guids = [];
        $this->types  = [];
        $this->created_at_ts = null;
    }


}