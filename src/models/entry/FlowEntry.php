<?php

namespace app\models\entry;

use app\models\project\exceptions\NothingToPushException;
use app\models\project\IFlowProject;
use Exception;


final class FlowEntry extends FlowEntryMembers  {



    /**
     * @param IFlowProject $project
     * @param object|array|IFlowEntry
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
            $old_id = $this->get_id();
            $db = FlowEntry::get_connection();
            if ($b_do_transaction) {
                $db->beginTransaction();
            }
            parent::save_entry(false, $b_save_children);
            $this->on_after_save_entry();
            if ($b_do_transaction) {
                $db->commit();
            }
            $title = $this->get_title();
            $action = "Updated";
            if (empty($old_id)) {
                $action = "Created";
            }
            if ($b_do_transaction) {
                try {
                    $this->get_project()->commit_changes("$action Entry $title");
                } catch (NothingToPushException $no_push) {
                    //ignore if no file changes
                }

            }

        } catch (Exception $e) {
            if ($b_do_transaction && $db) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
            if ($this->get_project()) {
                $this->get_project()->reset_project_repo_files();
            }
            throw $e;
        }

    }

    /**
     * called after the save is made
     */
    public function on_after_save_entry() :void {
        parent::on_after_save_entry();
        $this->store(); //write it to archive
    }


}