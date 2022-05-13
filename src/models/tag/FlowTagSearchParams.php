<?php

namespace app\models\tag;

use app\models\base\SearchParamBase;

class FlowTagSearchParams  extends SearchParamBase {

    protected ?string $owning_project_guid;
    public ?string $tag_name_term;

    /**
     * @var bool $flag_get_applied   if true will also get the applied in the set of tags found
     */
    public bool $flag_get_applied = false;

    /**
     * @var string[] $tag_guids
     */
    protected array $tag_guids = [];

    /**
     * @return string[]
     */
    public function get_guids() : array { return $this->tag_guids;}

    /**
     * @return string[]
     */
    public function get_names() : array { return $this->tag_names;}

    /**
     * @return string[]
     */
    public function get_ids() : array { return $this->tag_ids;}

    public function getOwningProjectGuid() : ?string {return $this->owning_project_guid;}

    /**
     * @var string[] $tag_names
     */
    protected array $tag_names = [];

    /**
     * @var int[] $tag_ids
     */
    public array $tag_ids = [];

    public array $only_applied_to_guids = [];

    public array $not_applied_to_guids = [];

    function __construct(){
        parent::__construct();
        $this->owning_project_guid = null;
        $this->tag_name_term = null;
        $this->tag_guids = [];
        $this->tag_names = [];
        $this->tag_ids = [];
        $this->flag_get_applied = false;
        $this->only_applied_to_guids = [];
        $this->not_applied_to_guids = [];
    }

    public function setOwningProjectGuid(?string $project_guid) : FlowTagSearchParams {
        $this->owning_project_guid = $project_guid;
        return $this;
    }



    function addGuidsOrNames(mixed $thing) : FlowTagSearchParams{
        if ($thing instanceof FlowTag) {
            $this->tag_guids[] = $thing->flow_tag_guid;
        } else {
            $filter = [];
            if (is_array($thing)) {
                foreach ($thing as $thang ) {
                    if ($thang instanceof FlowTag) {
                        $this->tag_guids[] = $thang->flow_tag_guid;
                    } else {
                        $filter[] = $thang;
                    }
                }
            } else {
                $filter = $thing;
            }
            $what = static::validate_cast_guid_array($filter,false);
            $this->tag_guids = array_unique(array_merge($this->tag_guids,$what));

            $what = static::validate_cast_name_array($filter,true,false);
            $this->tag_names = array_unique(array_merge($this->tag_names,$what));
        }
        return $this;
    }
}