<?php

namespace app\models\entry;

use app\models\entry\levels\FlowEntryFiles;
use app\models\project\exceptions\NothingToPushException;
use app\models\project\IFlowProject;
use Exception;


final class FlowEntry extends FlowEntryFiles  {



    /**
     * @param IFlowProject $project
     * @param object|array|IFlowEntry|IFlowEntryReadBasicProperties
     * @return IFlowEntry
     * @throws
     */
    public static function create_entry(IFlowProject $project, $object): IFlowEntry
    {
        return new FlowEntry($object,$project);
    }

    public function save_entry(bool $b_do_transaction = false, bool $b_save_children = false): void
    {
        $db = null;
        try {
            $old_guid = $this->get_guid();
            $db = FlowEntry::get_connection();
            if ($b_do_transaction && !$db->inTransaction()) {
                $db->beginTransaction();
            }
            parent::save_entry(false, $b_save_children);
            $this->on_after_save_entry();

            $title = $this->get_title();
            $action = "Updated";
            if (empty($old_guid)) {
                $action = "Created";
            }
            if ($b_do_transaction && $db->inTransaction()) {
                try {
                    $this->get_project()->commit_changes("$action Entry $title");
                } catch (NothingToPushException ) {
                    //ignore if no file changes
                }

            }

            if ($b_do_transaction && $db->inTransaction()) {
                $db->commit();
            }

        } catch (Exception $e) {
            if ($b_do_transaction && $db) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
            $this->get_project()?->reset_project_repo_files();
            throw $e;
        }

    }

    /**
     * called after the save is made
     */
    protected function on_after_save_entry() :void {
        parent::on_after_save_entry();
        $this->store(); //write it to archive
    }


}