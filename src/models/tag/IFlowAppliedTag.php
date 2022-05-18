<?php

namespace app\models\tag;

use Exception;
use Slim\Interfaces\RouteParserInterface;

interface IFlowAppliedTag
{

    public function getId(): ?int;
    public function getGuid(): ?string;
    public function getParentTagId(): ?int;
    public function getParentTagGuid(): ?string;

    public function getCreatedAtTs(): ?int;


    public function setId(?int $id): void;
    public function setGuid(?string $flow_applied_tag_guid): void;
    public function setParentTagId(?int $flow_tag_id): void;
    public function setParentTagGuid(?string $flow_tag_guid): void;
    public function setCreatedAtTs(?int $created_at_ts): void;




    public function getXPointerId(): ?int;
    public function getXNodeId(): ?int;
    public function getXEntryId(): ?int;
    public function getXUserId(): ?int;
    public function getXProjectId(): ?int;
    



    public function getXEntryGuid(): ?string;
    public function getPointerGuid(): ?string;
    public function getXNodeGuid(): ?string;
    public function getXUserGuid(): ?string;
    public function getXProjectGuid(): ?string;



    public function setXNodeId(?int $tagged_flow_entry_node_id): void;
    public function setXEntryId(?int $tagged_flow_entry_id): void;
    public function setXUserId(?int $tagged_flow_user_id): void;
    public function setXProjectId(?int $tagged_flow_project_id): void;
    public function setXPointerId(?int $tagged_pointer_id): void;


    public function setXEntryGuid(?string $tagged_flow_entry_guid): void;
    public function setXNodeGuid(?string $tagged_flow_entry_node_guid): void;
    public function setXPointerGuid(?string $tagged_pointer_guid): void;
    public function setXUserGuid(?string $tagged_flow_user_guid): void;
    public function setXProjectGuid(?string $tagged_flow_project_guid): void;


    public function getZTaggedUrl(): ?string;
    public function getZTaggedTitle(): ?string;
    public function getZOwnerUserGuid(): ?string;
    public function getZOwnerUserName(): ?string;
    public function getZOwnerProjectGuid(): ?string;






    public function set_link_for_tagged(RouteParserInterface $routeParser);

    /**
     * @throws Exception
     */
    public function save(): void;

    public function delete_applied() : void;

    /**
     * @param string[] $guid_list
     * @return bool
     */
    public function has_at_least_one_of_these_tagged_guid(array $guid_list): bool;

    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids(): array;

    /**
     * @@param array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids);
}