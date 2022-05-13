<?php

namespace app\models\entry\entry_node;

use app\models\tag\FlowTag;

interface IFlowEntryNodeDocument {

    const ENTRY_NODE_FILE_NAME = 'nodes.yaml';

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
     * @param IFlowEntryNodeDocument $doc
     * @param FlowTag[] $from
     * @param FlowTag $here
     * @return string
     */
    public function insert_at(IFlowEntryNodeDocument $doc,array $from,FlowTag $here) : string;
}