<?php

namespace app\models\entry;

use app\models\entry\archive\FlowEntryArchive;
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

    /**
     * called after the save is made
     */
    public function on_after_save_entry() :void {
        parent::on_after_save_entry();
        $this->store(); //write it to archive
    }


}