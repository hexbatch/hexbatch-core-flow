<?php

namespace app\models\entry\public_json;

use app\models\entry\IFlowEntry;

interface IFlowEntryJson {

    public function get_i_entry() : IFlowEntry;

    public function to_array() : array;

    /**
     * sort parents before children
     * if there are entries with a parent set, but not in the array, then those are put at the end
     * @param IFlowEntry[] $entry_array_to_sort
     * @return IFlowEntry[]
     */
    public static function sort_array_by_parent(array $entry_array_to_sort) : array;

    public static function create_flow_entry_json(IFlowEntry $entry) : IFlowEntryJson;
}