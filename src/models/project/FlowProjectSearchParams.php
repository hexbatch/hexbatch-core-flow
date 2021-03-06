<?php
namespace app\models\project;

use app\hexlet\JsonHelper;
use app\models\base\SearchParamBase;
use InvalidArgumentException;
use JsonException;

class FlowProjectSearchParams extends SearchParamBase {

    const DEFAULT_PAGE_SIZE = 30;


    /**
     * @var string[] $project_title_guid_or_id_list
     */
    protected array $project_title_guid_or_id_list = [];

    protected ?string $owner_user_name_or_guid_or_id = null;
    protected string $type_owner = self::ARG_IS_INVALID;

    protected ?string $flow_project_type;
    protected ?string $flow_project_special_flag;


    protected ?bool $can_read = null;
    protected ?bool $can_write = null;
    protected ?bool $can_admin = null;
    protected ?string $permission_user_name_or_guid_or_id = null;
    protected string $type_permission_user = self::ARG_IS_INVALID;


    function __construct(){
        parent::__construct();
        $this->flow_project_special_flag = null;
        $this->flow_project_type = false;
        $this->owner_user_name_or_guid_or_id = null;
        $this->project_title_guid_or_id_list = [];

    }

    /**
     * @return string[]
     */
    public function getProjectTitleGuidOrIdList(): array
    {
        return $this->project_title_guid_or_id_list;
    }


    /**
     * @param mixed $project_title_guid_or_id
     * @throws JsonException
     */
    public function addProjectTitleGuidOrId(mixed $project_title_guid_or_id): void
    {
        if (JsonHelper::isJson($project_title_guid_or_id)) {
            $try_me = JsonHelper::fromString($project_title_guid_or_id);
            if (is_array($try_me)) { $this->addProjectTitleGuidOrId($try_me); }
        } elseif (is_array($project_title_guid_or_id) && count($project_title_guid_or_id)) {
            foreach ($project_title_guid_or_id as $one_thing) {
                $this->addProjectTitleGuidOrId($one_thing);
            }
        } else {
            $type = static::find_type_of_arg($project_title_guid_or_id);
            if ($type === static::ARG_IS_NAME || $type === static::ARG_IS_HEX || $type === static::ARG_IS_INT) {
                $this->project_title_guid_or_id_list[] = $project_title_guid_or_id;
            } elseif ($type === static::ARG_IS_EMAIL) {
                throw new InvalidArgumentException("Project cannot be searched by email");
            } else {
                throw new InvalidArgumentException("Project name or guid supplied is empty or wrong data format");
            }
        }
    }

    public function getOwnerUserNameOrGuidOrid() : ?string
    {
        return $this->owner_user_name_or_guid_or_id;
    }

    /**
     * @param int|string|null $owner_user_name_or_guid_or_id
     */
    public function setOwnerUserNameOrGuidOrId(int|string|null $owner_user_name_or_guid_or_id): void
    {
        if (empty($owner_user_name_or_guid_or_id)) {
            $this->owner_user_name_or_guid_or_id = null;
            $this->type_owner = static::ARG_IS_INVALID;
            return;
        }
        $this->type_owner = static::find_type_of_arg($owner_user_name_or_guid_or_id);
        if ( $this->type_owner === static::ARG_IS_INVALID) {
            throw new InvalidArgumentException("Project search arg for owner is invalid");
        }
        $this->owner_user_name_or_guid_or_id = strval($owner_user_name_or_guid_or_id);
    }



    /**
     * @return false|string|null
     */
    public function getFlowProjectType(): false|string|null
    {
        return $this->flow_project_type;
    }

    /**
     * @param false|string|null $flow_project_type
     */
    public function setFlowProjectType(false|string|null $flow_project_type): void
    {
        $this->flow_project_type = $flow_project_type;
    }

    /**
     * @return string|null
     */
    public function getFlowProjectSpecialFlag(): ?string
    {
        return $this->flow_project_special_flag;
    }

    /**
     * @param string|null $flow_project_special_flag
     */
    public function setFlowProjectSpecialFlag(?string $flow_project_special_flag): void
    {
        $this->flow_project_special_flag = $flow_project_special_flag;
    }




    /**
     * @return bool|null
     */
    public function getCanRead(): ?bool
    {
        return $this->can_read;
    }

    /**
     * @param bool|null $can_read
     */
    public function setCanRead(?bool $can_read): void
    {
        $this->can_read = $can_read;
    }

    /**
     * @return bool|null
     */
    public function getCanWrite(): ?bool
    {
        return $this->can_write;
    }

    /**
     * @param bool|null $can_write
     */
    public function setCanWrite(?bool $can_write): void
    {
        $this->can_write = $can_write;
    }

    /**
     * @return bool|null
     */
    public function getCanAdmin(): ?bool
    {
        return $this->can_admin;
    }

    /**
     * @param bool|null $can_admin
     */
    public function setCanAdmin(?bool $can_admin): void
    {
        $this->can_admin = $can_admin;
    }

    /**
     * @return string|null
     */
    public function getPermissionUserNameOrGuidOrId(): ?string
    {
        return $this->permission_user_name_or_guid_or_id;
    }

    /**
     * @param string|null $permission_user_name_or_guid_or_id
     */
    public function setPermissionUserNameOrGuidOrId(?string $permission_user_name_or_guid_or_id): void
    {
        if (empty($permission_user_name_or_guid_or_id)) {
            $this->permission_user_name_or_guid_or_id = null;
            $this->type_permission_user = static::ARG_IS_INVALID;
            return;
        }
        $this->type_permission_user = static::find_type_of_arg($permission_user_name_or_guid_or_id);
        if ( $this->type_permission_user === static::ARG_IS_INVALID) {
            throw new InvalidArgumentException("Project search arg for permission user is invalid");
        }
        $this->permission_user_name_or_guid_or_id = strval($permission_user_name_or_guid_or_id);
    }

    /**
     * @return string
     */
    public function getTypeOwner(): string
    {
        return $this->type_owner;
    }

    /**
     * @return string
     */
    public function getTypePermissionUser(): string
    {
        return $this->type_permission_user;
    }



}