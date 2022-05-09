<?php

namespace app\models\entry\entry_node;

use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;

interface IFlowEntryNode {
    const FLOW_TAG_BB_CODE_NAME = 'flow_tag';

    public function get_node_id() : ?int;
    public function get_node_guid() : ?string;

    public function get_node_bb_tag_name() : ?string;
    public function get_node_text() : ?string;
    public function get_node_attributes() : object;

    public function get_created_at_ts() : ?int;
    public function get_updated_at_ts() : ?int;


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

}