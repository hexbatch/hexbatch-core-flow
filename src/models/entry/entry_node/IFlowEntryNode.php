<?php

namespace app\models\entry\entry_node;

use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;

interface IFlowEntryNode {
    const FLOW_TAG_BB_CODE_NAME = 'flow_tag';
    const DOCUMENT_BB_CODE_NAME = 'Document';

    public function get_node_id() : ?int;
    public function get_node_guid() : ?string;

    public function get_node_bb_tag_name() : ?string;
    public function get_node_text() : ?string;
    public function get_node_attributes() : object;

    public function get_created_at_ts() : ?int;
    public function get_updated_at_ts() : ?int;

    public function get_pass_through_value(): ?int;


    public function get_parent_guid() : ?string;
    public function get_parent_id() : ?int;
    public function get_parent() : ?IFlowEntryNode;

    public function get_flow_entry_id() : ?int;
    public function get_flow_entry_guid() : ?string;


    public function get_flow_tag_id() : ?int;
    public function get_flow_tag_guid() : ?string;
    public function get_tag() : ?FlowTag;

    public function get_applied_flow_tag_id() : ?int;
    public function get_applied_flow_tag_guid() : ?string;
    public function get_applied() : ?FlowAppliedTag;

    public function save(bool $b_do_transaction = false, bool $b_save_children = false) :void;
    public function get_as_bb_code() :string ;

    public function add_child(IFlowEntryNode $node): void;
    public function set_entry_id(int $entry_id): void;
    public function set_parent(?IFlowEntryNode $parent): void;
    public function set_entry_guid(?string $entry_guid): void;

    public function set_pass_through_value(int $pass_through): void;

    public function delete_node():void;

}