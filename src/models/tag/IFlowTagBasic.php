<?php

namespace app\models\tag;

interface IFlowTagBasic {




    /**
     * @return int|null
     */
    public function getCreatedAtTs(): ?int;

    /**
     * @param int|null $tag_created_at_ts
     */
    public function setCreatedAtTs(?int $tag_created_at_ts): void;

    /**
     * @return string|null
     */
    public function getGuid(): ?string;

    /**
     * @param string|null $flow_tag_guid
     */
    public function setGuid(?string $flow_tag_guid): void;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string|null $flow_tag_name
     */
    public function setName(?string $flow_tag_name): void;

    /**
     * @return int|null
     */
    public function getUpdatedAtTs(): ?int;

    /**
     * @param int|null $tag_updated_at_ts
     */
    public function setUpdatedAtTs(?int $tag_updated_at_ts): void;


    /**
     * @return string|null
     */
    public function getParentGuid(): ?string;

    /**
     * @param string|null $parent_tag_guid
     */
    public function setParentGuid(?string $parent_tag_guid): void;


    /**
     * @return string|null
     */
    public function getProjectGuid(): ?string;

    /**
     * @param string|null $flow_project_guid
     */
    public function setProjectGuid(?string $flow_project_guid): void;

}