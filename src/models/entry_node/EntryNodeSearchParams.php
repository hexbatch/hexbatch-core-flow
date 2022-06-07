<?php

namespace app\models\entry_node;

use app\hexlet\JsonHelper;
use app\models\base\SearchParamBase;
use app\models\entry\IFlowEntry;
use app\models\tag\FlowTag;
use app\models\tag\IFlowAppliedTag;
use JsonException;

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
    protected array $tag_guids = [];

    /**
     * @var string[] $node_guids
     */
    protected array $node_guids = [];


    /**
     * @var string[] $entry_guids
     */
    protected array $entry_guids = [];

    /**
     * @var string[] $applied_guids
     */
    protected array $applied_guids = [];


    protected ?string $parent_guid;
    protected ?bool $is_top_node;

    /**
     * @return bool|null
     */
    public function getIsTopNode(): ?bool
    {
        return $this->is_top_node;
    }

    /**
     * @param bool|null $is_top_node
     */
    public function setIsTopNode(?bool $is_top_node): void
    {
        $this->is_top_node = $is_top_node;
    }


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

    /**
     * @return string|null
     */
    public function getParentGuid(): ?string
    {
        return $this->parent_guid;
    }

    /**
     * @param string|null $parent_guid
     */
    public function setParentGuid(?string $parent_guid): void
    {
        $this->parent_guid = $parent_guid;
    }


    /**
     * @throws JsonException
     */
    function __construct(object|array|null $object=null){
        parent::__construct();
        $this->parent_guid = null;
        $this->tag_guids = [];
        $this->entry_guids = [];
        $this->applied_guids = [];
        $this->node_guids = [];
        $this->is_top_node = null;

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

    public function is_empty() : bool {
        $ret = parent::is_empty();
        if ($this->is_top_node !== null ) {return false;}
        return $ret;
    }

    /**
     * @param mixed $guid_thing
     * @throws JsonException
     */
    public function addTagGuid(mixed $guid_thing): void
    {
        if ($guid_thing instanceof FlowTag) {
            $this->tag_guids[] = $guid_thing->getGuid();
        } else {
            $filter = [];
            if (is_array($guid_thing)) {
                foreach ($guid_thing as $thang ) {
                    if ($thang instanceof FlowTag) {
                        $this->tag_guids[] = $thang->getGuid();
                    } else {
                        $filter[] = $thang;
                    }
                }
            } else {
                $filter = $guid_thing;
            }
            $what = static::validate_cast_guid_array($filter);
            $this->tag_guids = array_unique(array_merge($this->tag_guids,$what));
        }
    }

    /**
     * @param mixed $guid_thing
     * @throws JsonException
     */
    public function addEntryGuid(mixed $guid_thing): void
    {
        if ($guid_thing instanceof IFlowEntry) {
            $this->entry_guids[] = $guid_thing->get_guid();
        } else {
            $filter = [];
            if (is_array($guid_thing)) {
                foreach ($guid_thing as $thang ) {
                    if ($thang instanceof IFlowEntry) {
                        $this->entry_guids[] = $thang->get_guid();
                    } else {
                        $filter[] = $thang;
                    }
                }
            } else {
                $filter = $guid_thing;
            }
            $what = static::validate_cast_guid_array($filter);
            $this->entry_guids = array_unique(array_merge($this->entry_guids,$what));
        }

    }

    /**
     * @param mixed $guid_thing
     * @throws JsonException
     */
    public function addNodeGuid(mixed $guid_thing): void
    {
        if ($guid_thing instanceof IFlowEntryNode) {
            $this->node_guids[] = $guid_thing->get_node_guid();
        } else {
            $filter = [];
            if (is_array($guid_thing)) {
                foreach ($guid_thing as $thang ) {
                    if ($thang instanceof IFlowEntryNode) {
                        $this->node_guids[] = $thang->get_node_guid();
                    } else {
                        $filter[] = $thang;
                    }
                }
            } else {
                $filter = $guid_thing;
            }
            $what = static::validate_cast_guid_array($filter);
            $this->node_guids = array_unique(array_merge($this->node_guids,$what));
        }

    }

    /**
     * @param mixed $guid_thing
     * @throws JsonException
     */
    public function addAppliedGuid(mixed $guid_thing): void
    {
        if ($guid_thing instanceof IFlowAppliedTag) {
            $this->applied_guids[] = $guid_thing->getGuid();
        } else {
            $filter = [];
            if (is_array($guid_thing)) {
                foreach ($guid_thing as $thang ) {
                    if ($thang instanceof IFlowAppliedTag) {
                        $this->applied_guids[] = $thang->getGuid();
                    } else {
                        $filter[] = $thang;
                    }
                }
            } else {
                $filter = $guid_thing;
            }
            $what = static::validate_cast_guid_array($filter);
            $this->applied_guids = array_unique(array_merge($this->applied_guids,$what));
        }

    }
}