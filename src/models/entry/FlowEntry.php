<?php

namespace app\models\entry;

use app\models\project\FlowProject;


final class FlowEntry extends FlowEntryMembers  {



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