<?php

namespace app\models\tag\standard;

use app\hexlet\JsonHelper;
use app\models\base\SearchParamBase;
use InvalidArgumentException;

class StandardAttributeSearchParams  extends SearchParamBase {

    protected ?string $owner_user_guid = null;

    public array $owning_project_guids = [];

    /**
     * @var string[] $tag_guids
     */
    public array $tag_guids = [];

    /**
     * @var string[] $standard_attribute_names
     */
    public array $standard_attribute_names = [];

    public function __construct()
    {
        $this->owner_user_guid = null;
        $this->owning_project_guids = [];
        $this->tag_guids = [];
        $this->standard_attribute_names = [];
    }


    /**
     * @return string|null
     */
    public function getOwnerUserGuid(): ?string
    {
        return $this->owner_user_guid;
    }

    /**
     * @return array
     */
    public function getOwningProjectGuids(): array
    {
        return $this->owning_project_guids;
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
    public function getStandardAttributeNames(): array
    {
        return $this->standard_attribute_names;
    }



    /**
     * @param mixed $project_guid_thing
     */
    public function addOwningProjectGuid($project_guid_thing): void
    {
        if (JsonHelper::isJson($project_guid_thing)) {
            $try_me = JsonHelper::fromString($project_guid_thing);
            if (is_array($try_me)) { $this->addOwningProjectGuid($try_me); }
        } elseif (is_array($project_guid_thing) && count($project_guid_thing)) {
            foreach ($project_guid_thing as $one_thing) {
                $this->addOwningProjectGuid($one_thing);
            }
        } else {
            $type = static::find_type_of_arg($project_guid_thing);
            if ($type === static::ARG_IS_HEX ) {
                $this->owning_project_guids[] = $project_guid_thing;
            } else {
                throw new InvalidArgumentException("Owning Project must be searched by guid: ". $type);
            }
        }
    }


    /**
     * @param mixed $user_guid_thing
     */
    public function setOwningUserGuid($user_guid_thing): void
    {
        $type = static::find_type_of_arg($user_guid_thing);
        if ($type === static::ARG_IS_HEX ) {
            $this->owner_user_guid = $user_guid_thing;
        }  else {
            throw new InvalidArgumentException("Owning User must be searched by guid ". $type);
        }
    }


    /**
     * @param mixed $tag_guid_thing
     */
    public function addTagGuid($tag_guid_thing): void
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
    public function addStandardAttributeName($name_thing): void
    {
        if (JsonHelper::isJson($name_thing)) {
            $try_me = JsonHelper::fromString($name_thing);
            if (is_array($try_me)) { $this->addStandardAttributeName($try_me); }
        } elseif (is_array($name_thing) && count($name_thing)) {
            foreach ($name_thing as $one_thing) {
                $this->addStandardAttributeName($one_thing);
            }
        } else {
            $type = static::find_type_of_arg($name_thing);
            if ($type === static::ARG_IS_NAME ) {
                $this->standard_attribute_names[] = $name_thing;
            } else {
                throw new InvalidArgumentException("Attribute Name must be searched by name: ". $type);
            }
        }
    }


}