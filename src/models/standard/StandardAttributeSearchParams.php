<?php

namespace app\models\standard;

use app\hexlet\JsonHelper;
use InvalidArgumentException;

class StandardAttributeSearchParams  extends RawAttributeSearchParams {

    protected ?string $owner_user_guid = null;

    public array $owning_project_guids = [];




    public function __construct()
    {
        parent::__construct();
        $this->owner_user_guid = null;
        $this->owning_project_guids = [];
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





}