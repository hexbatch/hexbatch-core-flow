<?php

namespace app\models\entry;



use app\models\entry\brief\BriefFlowEntry;
use app\models\entry\brief\IFlowEntryBrief;
use app\models\project\FlowProject;


class FlowEntry extends FlowEntryMembers  {


    public function to_i_flow_entry_brief() : IFlowEntryBrief {
        return BriefFlowEntry::create_entry_brief($this);
    }

    /**
     * @param FlowProject $project
     * @param object|array|IFlowEntry
     * @return IFlowEntry
     * @throws
     */
    public static function create_entry(FlowProject $project, $object): IFlowEntry
    {
        return new FlowEntry($object,$project);
    }
}