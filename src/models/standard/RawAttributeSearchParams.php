<?php

namespace app\models\standard;

use app\hexlet\JsonHelper;
use app\models\base\SearchParamBase;
use InvalidArgumentException;

class RawAttributeSearchParams  extends SearchParamBase {


    /**
     * @var string[] $tag_guids
     */
    public array $tag_guids = [];

    /**
     * @var int[] $tag_ids
     */
    public array $tag_ids = [];

    /**
     * @var string[] $attribute_names
     */
    public array $attribute_names = [];

    public function __construct()
    {
        parent::__construct();
        $this->tag_guids = [];
        $this->attribute_names = [];
        $this->tag_ids = [];
        $this->setPageSize(static::UNLIMITED_RESULTS_PER_PAGE);

    }


    /**
     * @return string[]
     */
    public function getTagGuids(): array
    {
        return $this->tag_guids;
    }

    /**
     * @return int[]
     */
    public function getTagIds(): array
    {
        return $this->tag_ids;
    }

    /**
     * @return string[]
     */
    public function getAttributeNames(): array
    {
        return $this->attribute_names;
    }

    /**
     * @param int $id
     */
    public function addTagID(int $id ): void
    {
        $this->tag_ids[] = $id;
    }

    /**
     * @param mixed $tag_guid_thing
     */
    public function addTagGuid(mixed $tag_guid_thing): void
    {
        if (JsonHelper::isJson($tag_guid_thing)) {
            $try_me = JsonHelper::fromString($tag_guid_thing);
            if (is_array($try_me)) { $this->addTagGuid($try_me); }
        } elseif (is_array($tag_guid_thing) && count($tag_guid_thing)) {
            foreach ($tag_guid_thing as $one_thing) {
                $this->addTagGuid($one_thing);
            }
        } else {
            $type = static::find_type_of_arg($tag_guid_thing);
            if ($type === static::ARG_IS_HEX ) {
                $this->tag_guids[] = $tag_guid_thing;
            } else {
                throw new InvalidArgumentException("Tag must be searched by guid: ". $type);
            }
        }
    }

    /**
     * @param mixed $name_thing
     */
    public function addAttributeName(mixed $name_thing): void
    {
        if (JsonHelper::isJson($name_thing)) {
            $try_me = JsonHelper::fromString($name_thing);
            if (is_array($try_me)) { $this->addAttributeName($try_me); }
        } elseif (is_array($name_thing) && count($name_thing)) {
            foreach ($name_thing as $one_thing) {
                $this->addAttributeName($one_thing);
            }
        } else {
            $type = static::find_type_of_arg($name_thing);
            if ($type === static::ARG_IS_NAME ) {
                $this->attribute_names[] = $name_thing;
            } else {
                throw new InvalidArgumentException("Attribute Name must be searched by name: ". $type);
            }
        }
    }
}