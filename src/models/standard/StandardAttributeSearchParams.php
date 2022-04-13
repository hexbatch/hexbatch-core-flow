<?php

namespace app\models\standard;

use app\hexlet\JsonHelper;
use InvalidArgumentException;

class StandardAttributeSearchParams  extends RawAttributeSearchParams {

    protected ?string $owner_user_guid = null;
    protected ?string $owner_user_email = null;
    protected ?string $owner_user_name = null;

    public array $owning_project_guids = [];
    public array $owning_project_names = [];



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
     * @param mixed $project_guid_or_name
     */
    public function addOwningProject($project_guid_or_name): void
    {
        if (JsonHelper::isJson($project_guid_or_name)) {
            $try_me = JsonHelper::fromString($project_guid_or_name);
            if (is_array($try_me)) { $this->addOwningProject($try_me); }
        } elseif (is_array($project_guid_or_name) && count($project_guid_or_name)) {
            foreach ($project_guid_or_name as $one_thing) {
                $this->addOwningProject($one_thing);
            }
        } else {
            $type = static::find_type_of_arg($project_guid_or_name);
            if ($type === static::ARG_IS_HEX ) {
                $this->owning_project_guids[] = $project_guid_or_name;
            } elseif ($type === static::ARG_IS_NAME ) {
                $this->owner_user_name[] = $project_guid_or_name;
            } else {
                throw new InvalidArgumentException("Owning Project must be searched by guid: ". $type);
            }
        }
    }


    /**
     * @param mixed $user_guid_thing
     */
    public function setOwningUser($user_guid_thing): void
    {
        $type = static::find_type_of_arg($user_guid_thing);
        if ($type === static::ARG_IS_HEX ) {
            $this->owner_user_guid = $user_guid_thing;
        }
        else if ($type === static::ARG_IS_NAME ) {
            $this->owner_user_name = $user_guid_thing;
        }
        else if ($type === static::ARG_IS_EMAIL ) {
            $this->owner_user_email = $user_guid_thing;
        }
        else {
            throw new InvalidArgumentException("Owning User must be searched by guid ". $type);
        }
    }

    /**
     * @return string|null
     */
    public function getOwnerUserEmail(): ?string
    {
        return $this->owner_user_email;
    }

    /**
     * @return string|null
     */
    public function getOwnerUserName(): ?string
    {
        return $this->owner_user_name;
    }

    /**
     * @return array
     */
    public function getOwningProjectNames(): array
    {
        return $this->owning_project_names;
    }





}