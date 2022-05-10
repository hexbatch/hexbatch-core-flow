<?php

namespace app\models\entry\entry_node;

use app\hexlet\JsonHelper;
use app\models\base\SearchParamBase;
use app\models\entry\IFlowEntry;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;

class EntryNodeSearchParams extends SearchParamBase {

    /*
      *      entry can be constrained by node, tag, applied
         *      node can be constrained by entry, tag, applied
         *      tag can be constrained by entry, node, applied
         *      applied can be constrained by entry, node, tag
     */
    const DEFAULT_PAGE_SIZE = 30;


    /**
     * @var string[] $tag_guids
     */
    public array $tag_guids = [];

    /**
     * @var string[] $node_guids
     */
    public array $node_guids = [];


    /**
     * @var string[] $entry_guids
     */
    public array $entry_guids = [];

    /**
     * @var string[] $applied_guids
     */
    public array $applied_guids = [];

    /**
     * @return string[]
     */
    public function getTagGuids(): array
    {
        return $this->tag_guids;
    }

    /**
     * @return string[]
     */
    public function getNodeGuids(): array
    {
        return $this->node_guids;
    }

    /**
     * @return string[]
     */
    public function getEntryGuids(): array
    {
        return $this->entry_guids;
    }

    /**
     * @return string[]
     */
    public function getAppliedGuids(): array
    {
        return $this->applied_guids;
    }




    function __construct(object|array|null $object=null){
        parent::__construct();
        $this->tag_guids = [];
        $this->entry_guids = [];
        $this->applied_guids = [];
        $this->node_guids = [];

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

    /**
     * @param mixed $guid_thing
     */
    public function addTagGuid(mixed $guid_thing): void
    {
        $filter = [];
        if (is_array($guid_thing)) {
            foreach ($guid_thing as $thang ) {
                if ($thang instanceof FlowTag) {
                    $this->tag_guids[] = $thang->flow_tag_guid;
                } else {
                    $filter[] = $thang;
                }
            }
        }
        $what = static::validate_cast_guid_array($filter);
        $this->tag_guids = array_unique(array_merge($this->tag_guids,static::validate_cast_guid_array($what)));
    }

    /**
     * @param mixed $guid_thing
     */
    public function addEntryGuid(mixed $guid_thing): void
    {
        $filter = [];
        if (is_array($guid_thing)) {
            foreach ($guid_thing as $thang ) {
                if ($thang instanceof IFlowEntry) {
                    $this->entry_guids[] = $thang->get_guid();
                } else {
                    $filter[] = $thang;
                }
            }
        }
        $what = static::validate_cast_guid_array($filter);
        $this->entry_guids = array_unique(array_merge($this->entry_guids,static::validate_cast_guid_array($what)));
    }

    /**
     * @param mixed $guid_thing
     */
    public function addNodeGuid(mixed $guid_thing): void
    {
        $filter = [];
        if (is_array($guid_thing)) {
            foreach ($guid_thing as $thang ) {
                if ($thang instanceof IFlowEntryNode) {
                    $this->node_guids[] = $thang->get_node_guid();
                } else {
                    $filter[] = $thang;
                }
            }
        }
        $what = static::validate_cast_guid_array($filter);
        $this->node_guids = array_unique(array_merge($this->node_guids,static::validate_cast_guid_array($what)));
    }

    /**
     * @param mixed $guid_thing
     */
    public function addAppliedGuid(mixed $guid_thing): void
    {
        $filter = [];
        if (is_array($guid_thing)) {
            foreach ($guid_thing as $thang ) {
                if ($thang instanceof FlowAppliedTag) {
                    $this->applied_guids[] = $thang->flow_applied_tag_guid;
                } else {
                    $filter[] = $thang;
                }
            }
        }
        $what = static::validate_cast_guid_array($filter);
        $this->applied_guids = array_unique(array_merge($this->applied_guids,static::validate_cast_guid_array($what)));
    }
}