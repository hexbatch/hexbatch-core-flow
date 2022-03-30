<?php

namespace app\models\multi;

use app\models\base\SearchParamBase;

class GeneralSearchParams   extends SearchParamBase  {


    public ?string $words;

    /**
     * @var string[] $guids
     */
    public array $guids = [];

    /**
     * @var string[] $types
     */
    public array $types;

    public ?int $created_at_ts;

    public ?string $against_user_guid;

    public bool $b_get_secondary = false;

    public bool $b_only_public = false;


    function __construct(){
        parent::__construct();
        $this->words = null;
        $this->guids = [];
        $this->types  = [];
        $this->created_at_ts = null;
        $this->against_user_guid = null;
        $this->b_get_secondary = false;
        $this->b_only_public = false;
    }


}