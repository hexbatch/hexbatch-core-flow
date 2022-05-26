<?php

namespace app\models\tag;

use app\models\project\IFlowProject;
use app\models\standard\IFlowTagStandardAttribute;
use Exception;

interface IFlowTag extends IFlowTagBasic
{


    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @param int|null $flow_tag_id
     */
    public function setId(?int $flow_tag_id): void;


    /**
     * @return int|null
     */
    public function getProjectId(): ?int;

    /**
     * @param int|null $flow_project_id
     */
    public function setProjectId(?int $flow_project_id): void;

    /**
     * @return IFlowProject|null
     */
    public function getProject(): ?IFlowProject;

    /**
     * @param IFlowProject|null $flow_project
     */
    public function setProject(?IFlowProject $flow_project): void;

    /**
     * @return int|null
     */
    public function getParentId(): ?int;

    /**
     * @param int|null $parent_tag_id
     */
    public function setParentId(?int $parent_tag_id): void;



    /**
     * @return string|null
     */
    public function getAdminGuid(): ?string;

    /**
     * @param string|null $flow_project_admin_user_guid
     */
    public function setAdminGuid(?string $flow_project_admin_user_guid): void;

    /**
     * @return IFlowTagAttribute[]
     */
    public function getAttributes(): array;


    /**
     * @return IFlowAppliedTag[]
     */
    public function getApplied(): array;



    /**
     * @param IFlowTagAttribute[] $attributes
     */
    public function setAttributes(array $attributes): void;

    public function addAttribute(IFlowTagAttribute $a) : IFlowTag;

    /** @param IFlowTagAttribute[] $a */
    public function addAttributes(array $a) : IFlowTag;

    /**
     * @return IFlowTag
     */
    public function getParent(): IFlowTag;

    /**
     * @param IFlowTag|null $flow_tag_parent
     */
    public function setParent(?IFlowTag $flow_tag_parent): void;

    /**
     * @return int[]
     */
    public function getChildrenList(): array;




    /**
     * @param IFlowAppliedTag[] $applied
     */
    public function setApplied(array $applied): void;

    /**
     * @return IFlowTagAttribute[]
     */
    public function getInheritedAttributes(): array;

    /**
     * @param IFlowTagAttribute[] $inherited_attributes
     */
    public function setInheritedAttributes(array $inherited_attributes): void;

    /**
     * @return IFlowTagStandardAttribute[]
     */
    public function getStandardAttributes(): array;

    public function hasStandardAttribute(string $name): ?IFlowTagStandardAttribute;

    /**
     * @param IFlowTagStandardAttribute[] $what
     * @return void
     */
    public function setStandardAttributes(array $what): void;

    public function get_or_create_attribute(string $attribute_name): IFlowTagAttribute;

    public function set_standard_by_raw(string $standard_name, object $standard_value);


    public function refresh_inherited_fields();

    /**
     * @param array<string,string> $guid_map_old_to_new
     * @param bool $b_do_transaction default false
     * @return IFlowTag
     * @throws Exception
     */
    public function clone_change_project(array $guid_map_old_to_new, bool $b_do_transaction = false): IFlowTag;

    /**
     * @param bool $b_get_applied
     * @return $this
     * @throws Exception
     */
    public function clone_refresh(bool $b_get_applied = true): IFlowTag;

    /**
     * @return IFlowTag
     * @throws Exception
     */
    public function clone_with_missing_data(): IFlowTag;

    /**
     * @param string|null $attribute_name
     * @param IFlowTagAttribute|null $attribute
     * @param bool $b_do_transaction
     * @return $this|IFlowTag
     * @throws Exception
     */
    public function save_tag_return_clones(?string $attribute_name, IFlowTagAttribute &$attribute = null,
                                           bool $b_do_transaction = false): IFlowTag;

    /**
     * @param bool $b_do_transaction
     * @param bool $b_save_children
     * @param array<string,string> $guid_map_old_to_new
     *      if not empty , then saves applied and attributes as new under the current project id
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false, bool $b_save_children = false, array $guid_map_old_to_new = []): void;

    public function delete_tag();

    /**
     * @param string[] $guid_list
     * @return IFlowAppliedTag[]
     */
    public function find_applied_by_guid_of_tagged(array $guid_list): array;

    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids(): array;

    /**
     * @@param array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids);

    public function delete_attribute_by_name(string $attribute_name): ?IFlowTagAttribute;
}