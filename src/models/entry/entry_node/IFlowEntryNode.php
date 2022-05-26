<?php

namespace app\models\entry\entry_node;

use app\models\tag\FlowTag;
use app\models\tag\IFlowAppliedTag;

interface IFlowEntryNode {
    const FLOW_TAG_BB_CODE_NAME = 'flow_tag';
    const FLOW_TAG_BB_ATTR_GUID_NAME = 'tag';
    const DOCUMENT_BB_CODE_NAME = 'Document';
    const TEXT_BB_CODE_NAME = 'text';

    const NON_CLOSING_BB_TAGS = [
        'hr',
        self::DOCUMENT_BB_CODE_NAME,
        self::FLOW_TAG_BB_CODE_NAME
    ];

    public function get_node_id() : ?int;
    public function get_node_guid() : ?string;

    public function get_node_bb_tag_name() : ?string;
    public function get_node_text() : ?string;
    public function get_node_attributes() : object;

    public function get_created_at_ts() : ?int;
    public function get_updated_at_ts() : ?int;

    public function get_pass_through_int(): ?int;
    public function get_pass_through_float(): ?float ;



    public function get_parent_guid() : ?string;
    public function get_parent_id() : ?int;
    public function get_parent() : ?IFlowEntryNode;

    public function get_child_position() : ?int;
    public function get_lot_number() : int;

    public function get_flow_entry_id() : ?int;
    public function get_flow_entry_guid() : ?string;


    public function get_flow_tag_id() : ?int;
    public function get_flow_tag_guid() : ?string;
    public function get_tag() : ?FlowTag;

    public function get_applied_flow_tag_id() : ?int;
    public function get_applied_flow_tag_guid() : ?string;
    public function get_applied() : ?IFlowAppliedTag;

    public function save(bool $b_do_transaction = false, bool $b_save_children = false) :void;
    public function get_as_bb_code() :string ;

    public function is_text_node() : bool;
    /**
     * gets a map of all the guids used with the guid as the key, and the id as the value
     * @return array<string,IFlowEntryNode>
     */
    public function get_guid_map(): array;

    /**
     * @return IFlowEntryNode[]
     */
    public function get_children() : array;

    public function add_child(IFlowEntryNode $node): void;
    public function set_entry_id(int $entry_id): void; //sets for all children too
    public function set_parent(?IFlowEntryNode $parent): void;
    public function set_entry_guid(?string $entry_guid): void; //sets for all parents too
    public function set_node_guid(?string $node_guid): void;

    public function set_pass_through_int(int $pass_through): void;
    public function set_pass_through_float(?float $pass) : void;
    public function set_child_position(int $child_pos): void;
    public function set_lot_number(int $lot): void;

    const PRUNE_KEEP_THESE = 'keep-these';
    const PRUNE_DELETE_ONLY_THESE = 'delete-only-these';

    /**
     * Allows to selectively delete self and children, or delete all
     * @param bool $b_do_transaction
     * @param IFlowEntryNode|null $filter  if null then all including called will be deleted
     * @param string|null $logic    must not be null if filter is set
     * @return void
     */
    public function prune_node(bool $b_do_transaction, ?IFlowEntryNode $filter = null, ?string $logic = null ):void;


    /**
     * Part of the save process on a new tree that has text nodes which have no guids (text nodes do not save meta during edits)
     * Walks through the tree, and finds matching elements that already have a guid and has been found in the older version)
     *  for each element, find any text nodes right under it, and compare for likeness
     *  first go in same order of the slots, and find exact matches, and give the guid,
     *  then for any left over, find out of order direct matches and give the guid,
     *  finally, for any left over in the older ones, take each and compare to the left over new ones, and for any that have words of 50% match,
     *  assign them in order of greatest matching
     * @param IFlowEntryNode $older
     * @return void
     *
     */
    public function match_text_nodes_with_existing(IFlowEntryNode $older) : void;

    /**
     * Makes new objects tree deep
     * @param bool $erase_guid_and_parent_guid
     * @return IFlowEntryNode
     */
    public function copy(bool $erase_guid_and_parent_guid = false ) : IFlowEntryNode;
    public function empty_children() : void;

    public function to_array(bool $b_children=true) : array;


}