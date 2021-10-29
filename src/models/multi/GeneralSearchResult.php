<?php

namespace app\models\multi;

use JsonSerializable;

class GeneralSearchResult implements JsonSerializable  {



    public ?int $id;
    public ?string $title;
    public ?string $guid;
    public ?string $type;
    public ?int $created_at_ts;


    function __construct($object=null){
        $this->id = null;
        $this->title = null;
        $this->guid = null;
        $this->type = null;
        $this->created_at_ts = null;

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

    }

    public function jsonSerialize(): array
    {
        return [
            "guid" => $this->guid,
            "created_at_ts" => $this->created_at_ts,
            "title" => $this->title,
            "type" => $this->type,
        ];
    }

}