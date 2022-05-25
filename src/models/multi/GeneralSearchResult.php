<?php

namespace app\models\multi;

use app\hexlet\JsonHelper;
use JsonException;
use JsonSerializable;
use stdClass;

class GeneralSearchResult implements JsonSerializable  {



    public ?int $id;

    public ?string $guid;
    public ?string $type;
    public ?int $created_at_ts;
    public ?int $updated_at_ts;
    public ?string $url;
    public ?string $title;

    public ?string $blurb;
    public ?string $words;
    public bool $is_public;
    public ?string $owning_user_guid;
    public ?string $owning_project_guid;
    public ?string $owning_entry_guid;
    public ?string $owning_entry_title;

    public ?string $allowed_readers_json;
    public ?string $tag_used_by_json;
    public ?string $css_json;


    public array $allowed_readers;
    public array $tag_used_by;
    public stdClass $css_object;

    /**
     * @var GeneralSearchResult[] $tag_used_by_results
     */
    public array $tag_used_by_results = [];

    /**
     * @var GeneralSearchResult[] $allowed_readers_results;
     */
    public array $allowed_readers_results = [];

    /**
     * @var GeneralSearchResult|null $owning_project_result;
     */
    public ?GeneralSearchResult $owning_project_result = null;

    /**
     * @var GeneralSearchResult|null $owning_user_result;
     */
    public ?GeneralSearchResult $owning_user_result = null;


    /**
     * @throws JsonException
     */
    function __construct($object=null){
        $this->id = null;
        $this->title = null;
        $this->blurb = null;
        $this->words = null;
        $this->guid = null;
        $this->type = null;
        $this->created_at_ts = null;
        $this->url = null;
        $this->is_public = false;
        $this->owning_user_guid = null;
        $this->owning_project_guid = null;
        $this->owning_entry_guid = null;
        $this->owning_entry_title = null;
        $this->allowed_readers_json = null;
        $this->tag_used_by_json = null;
        $this->css_json = null;
        $this->allowed_readers_results = [];
        $this->tag_used_by_results = [];
        $this->allowed_readers = [];
        $this->tag_used_by = [];
        $this->css_object = (object)[];

        $this->owning_project_result = null;
        $this->owning_user_result = null;


        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }
        $allowed_readers = JsonHelper::fromString($this->allowed_readers_json);
        if (empty($allowed_readers)) { $this->allowed_readers = [];}
        else { $this->allowed_readers = $allowed_readers;}

        $tag_used_by = JsonHelper::fromString($this->tag_used_by_json);
        if (empty($tag_used_by)) { $this->tag_used_by = [];}
        else { $this->tag_used_by = $tag_used_by;}

        $css = JsonHelper::fromString($this->css_json,true,false);
        if (empty($css)) { $this->css_object = (object)[];}
        else { $this->css_object = $css;}

    }

    public function jsonSerialize(): array
    {
        return [
            "guid" => $this->guid,
            "created_at_ts" => $this->created_at_ts,
            "updated_at_ts" => $this->updated_at_ts,
            "title" => $this->title,
            "blurb" => $this->blurb,
            "words" => $this->words,
            "type" => $this->type,
            "url" => $this->url,
            "is_public" => JsonHelper::var_to_boolean($this->is_public) ,
            "owning_user_guid" => $this->owning_user_guid ,
            "owning_project_guid" => $this->owning_project_guid ,
            "owning_entry_guid" => $this->owning_entry_guid ,
            "allowed_readers" => $this->allowed_readers ,
            "tag_used_by" => $this->tag_used_by ,
            "css_object" => $this->css_object,
            "allowed_readers_results" => $this->allowed_readers_results ,
            "tag_used_by_results" => $this->tag_used_by_results ,
            "owning_user_result" => $this->owning_user_result ,
            "owning_project_result" => $this->owning_project_result ,
            "owning_entry_title" => $this->owning_entry_title ,
        ];
    }

}