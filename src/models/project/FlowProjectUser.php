<?php

namespace app\models\project;
use app\models\base\FlowBase;

use Exception;
use JsonSerializable;


class FlowProjectUser extends FlowBase implements JsonSerializable {
    public ?int $flow_project_user_id;
    public ?int $flow_project_user_created_at_ts;
    public ?string $flow_project_user_guid;

    public ?int $flow_project_id;
    public ?int $flow_user_id;
    public ?int $can_write;
    public ?int $can_read;
    public ?int $can_admin;
    public ?int $is_public;

    public ?string $flow_user_name;
    public ?string $flow_user_guid;
    public ?string $flow_project_guid;
    public ?string $flow_project_title;
    public ?string $flow_project_type;







    public function __construct($object=null){
        if (empty($object)) {
            $this->flow_project_user_id = null;
            $this->can_read = null;
            $this->can_write = null;
            $this->can_admin = null;
            $this->flow_user_id = null;
            $this->flow_project_user_guid = null;
            $this->flow_project_user_created_at_ts = null;
            $this->flow_user_name = null;
            $this->flow_user_guid = null;
            $this->flow_project_guid = null;
            $this->flow_project_title = null;
            $this->flow_project_type = null;
            $this->is_public = null;
            return;
        }
        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

    }

    /** @noinspection PhpUnused */
    public function can_edit() : bool {
        return $this->can_write || $this->can_admin;
    }


    /**
     * @throws Exception
     */
    public function save() {
        try {
            $db = static::get_connection();
            $db->insertOnDuplicateKeyUpdate('flow_project_users',
                [
                    'flow_project_id' => $this->flow_project_id,
                    'flow_user_id' => $this->flow_user_id,
                    'can_write' => $this->can_write,
                    'can_read' => $this->can_read,
                    'can_admin' => $this->can_admin,
                ],
                [
                    'can_write', 'can_read', 'can_admin'
                ]
            );


        } catch (Exception $e) {
            static::get_logger()->alert("FlowProjectUser model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }


    public function jsonSerialize(): array
    {

        return [
            "flow_project_user_guid" => $this->flow_project_user_guid,
            "flow_project_user_created_at_ts" => $this->flow_project_user_created_at_ts,
            "is_project_public" => $this->is_public,
            "flow_user_name" => $this->flow_user_name,
            "flow_user_guid" => $this->flow_user_guid,
            "flow_project_guid" => $this->flow_project_guid,
            "flow_project_title" => $this->flow_project_title,
            "flow_project_type" => $this->flow_project_type,
            "can_write" => (bool)$this->can_write,
            "can_read" => (bool)$this->can_read,
            "can_admin" => (bool)$this->can_admin,
        ];
    }


}