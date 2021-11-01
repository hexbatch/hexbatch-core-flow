<?php

namespace app\models\entry;


use app\models\project\FlowProject;
use Exception;

class FlowEntry extends FlowEntryFiles  {
    /**
     * @param FlowProject $project
     * @return FlowEntry
     * @throws Exception
     */
    public function clone_with_missing_data(FlowProject $project) : FlowEntry {
        $base = parent::clone_with_missing_data($project);
        $ret = new FlowEntry($base);
        return $ret;
    }

}