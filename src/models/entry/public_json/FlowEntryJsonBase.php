<?php

namespace app\models\entry\public_json;

use app\models\entry\archive\IFlowEntryArchive;
use app\models\entry\IFlowEntry;
use BlueM\Tree;
use JsonSerializable;
use LogicException;

class FlowEntryJsonBase implements IFlowEntryJson,JsonSerializable {

    protected IFlowEntry $entry;

    public function __construct(IFlowEntry $entry) {
        $this->entry = $entry;
    }

    public function get_i_entry() : IFlowEntry {
        return $this->entry;
    }

    public function to_array() : array {
        return [
            "flow_entry_guid" => $this->entry->get_guid(),
            "flow_entry_parent_guid" => $this->entry->get_parent_guid(),
            "flow_project_guid" => $this->entry->get_project_guid(),
            "entry_created_at_ts" => $this->entry->get_created_at_ts(),
            "entry_updated_at_ts" => $this->entry->get_updated_at_ts(),
            "flow_entry_title" => $this->entry->get_title(),
            "flow_entry_blurb" => $this->entry->get_blurb(),
            "flow_entry_body_bb_code" => $this->entry->get_bb_code(),
        ];
    }

    public function jsonSerialize() : array
    {
        return $this->to_array();
    }

    /**
     * sort parents before children
     * if there are entries with a parent set, but not in the array, then those are put at the end
     * @param IFlowEntry[]|IFlowEntryArchive[] $entry_array_to_sort
     * @return IFlowEntry[]
     */
    public static function sort_array_by_parent(array $entry_array_to_sort): array
    {
        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','tag'=>null];
        foreach ($entry_array_to_sort as $entry) {
            if ($entry instanceof IFlowEntryArchive) {

                $data[] = [
                    'id' => $entry->get_entry()->get_id(),
                    'parent' => $entry->get_entry()->get_parent_id()??0,
                    'title' => $entry->get_entry()->get_title(),
                    'entry'=>$entry
                ];

            } else if ($entry instanceof IFlowEntry) {
                $data[] = [
                    'id' => $entry->get_id(),
                    'parent' => $entry->get_parent_id()??0,
                    'title' => $entry->get_title(),
                    'entry'=>$entry
                ];
            } else {
                throw new LogicException("[FlowEntryJsonBase::sort_array_by_parent] Unknown interface ");
            }

        }
        $tree = new Tree(
            $data,
            ['rootId' => -1]
        );

        $sorted_nodes =  $tree->getNodes();
        $ret = [];
        foreach ($sorted_nodes as $node) {
            $what =  $node->entry??null;
            if ($what) {$ret[] = $what;}
        }
        return $ret;
    }

    public static function create_flow_entry_json(IFlowEntry $entry): IFlowEntryJson
    {
        return new static($entry);
    }
}