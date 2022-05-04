<?php

namespace app\models\entry\public_json;

use app\models\entry\archive\IFlowEntryArchive;
use app\models\entry\IFlowEntry;
use BlueM\Tree;

use JetBrains\PhpStorm\ArrayShape;
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

    #[ArrayShape(["flow_entry_guid" => "null|string", "flow_entry_parent_guid" => "null|string",
                    "flow_project_guid" => "null|string", "entry_created_at_ts" => "int|null",
                    "entry_updated_at_ts" => "int|null", "flow_entry_title" => "null|string",
                    "flow_entry_blurb" => "null|string", "flow_entry_body_bb_code" => "null|string"])]
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

    #[ArrayShape(["flow_entry_guid" => "\null|string", "flow_entry_parent_guid" => "\null|string", "flow_project_guid" => "\null|string", "entry_created_at_ts" => "\int|null", "entry_updated_at_ts" => "\int|null", "flow_entry_title" => "\null|string", "flow_entry_blurb" => "\null|string", "flow_entry_body_bb_code" => "\null|string"])]
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

        /**
         * @var array<string,int> $hash_fake_ids
         */
        $hash_fake_ids = [];
        $count_id = 10;
        foreach ($entry_array_to_sort as $thing) {
            $hash_fake_ids[$thing->get_entry()->get_guid()] = $count_id++;
        }

        foreach ($entry_array_to_sort as $entry) {
            if ($entry instanceof IFlowEntryArchive) {

                if(!$entry->get_entry()->get_parent_guid()) { $parent_id = 0;}
                elseif (isset($hash_fake_ids[$entry->get_entry()->get_parent_guid()])) {
                    $parent_id = $hash_fake_ids[$entry->get_entry()->get_parent_guid()];
                }
                else {$parent_id = 0;  }

                $data[] = [
                    'id' => $hash_fake_ids[$entry->get_entry()->get_guid()],
                    'parent' => $parent_id ,
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