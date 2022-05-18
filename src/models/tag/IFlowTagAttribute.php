<?php

namespace app\models\tag;

use Slim\Interfaces\RouteParserInterface;

interface IFlowTagAttribute {
    const LENGTH_ATTRIBUTE_NAME = 40;


    public function getId(): ?int;
    public function getGuid(): ?string;
    public function getTagGuid(): ?string;


    public function getLong(): ?int;
    public function getText(): ?string;
    public function getName(): ?string;

    public function getCreatedAtTs(): ?int;
    public function getUpdatedAtTs(): ?int;

    public function setTagId(?int $flow_tag_id): void;
    public function setId(?int $flow_tag_attribute_id): void;
    public function setGuid(?string $flow_tag_attribute_guid): void;
    public function setIsInherited(?bool $is_inherited): void;

    public function setLong(?int $tag_attribute_long): void;
    public function setText(?string $tag_attribute_text): void;
    public function setName(?string $tag_attribute_name): void;


    public function get_needed_guids_for_empty_ids() : array;

    /**
     * @@param  array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids);


    public function has_enough_data_set() :bool ;
    public function update_fields_with_public_data(IFlowTagAttribute $attribute) ;
    public function save() :void;
    public function delete_attribute();


//------------------------

    public function set_link_for_pointee(RouteParserInterface $routeParser);

    public function getPointsToFlowEntryGuid(): ?string;
    public function getPointsToFlowUserGuid(): ?string;
    public function getPointsToFlowProjectGuid(): ?string;
    public function getPointsToFlowTagGuid(): ?string;


    public function getPointsToEntryId(): ?int;
    public function getPointsToUserId(): ?int;
    public function getPointsToProjectId(): ?int;
    public function getPointsToTagId(): ?int;


    public function setPointsToFlowEntryGuid(?string $points_to_flow_entry_guid): void;
    public function setPointsToFlowUserGuid(?string $points_to_flow_user_guid): void;
    public function setPointsToFlowProjectGuid(?string $points_to_flow_project_guid): void;
    public function setPointsToFlowTagGuid(?string $points_to_flow_tag_guid): void;

    public function setPointsToEntryId(?int $points_to_entry_id): void;
    public function setPointsToUserId(?int $points_to_user_id): void;
    public function setPointsToProjectId(?int $points_to_project_id): void;
    public function setPointsToTagId(?int $points_to_tag_id): void;


}