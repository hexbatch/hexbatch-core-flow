<?php

namespace app\models\entry\entry_node;

use app\models\entry\IFlowEntry;
use app\models\tag\FlowTag;

interface IFlowEntryNodeDocument {

    const ENTRY_NODE_FILE_NAME = 'nodes.yaml';

    public function get_entry() : IFlowEntry;

    public function save(bool $b_do_transaction = false) : void;

    /**
     * @return IFlowEntryNode|null
     */
    public function get_top_node_of_entry() : ?IFlowEntryNode;


    public  function get_as_bb_code(
        array $entry = [],
        array $node = [],
        array $tag = [],
        array $applied = []
    ) : array;

    /**
     * @param IFlowEntryNodeDocument|null $target_doc
     * @param FlowTag[] $from_tags
     * @param FlowTag|null $target_tag
     * @return string|null
     */
    public function insert_at(array $from_tags, ?IFlowEntryNodeDocument $target_doc, ?FlowTag $target_tag) : ?string;
}