<?php

namespace app\models\entry\archive;


use app\models\entry\FlowEntry;


abstract class FlowEntryArchiveChildren extends FlowEntryArchiveFiles {



    public function to_array() : array {

        $array = parent::to_array();
        $array['child_entries'] = [];
        foreach ($this->entry->get_children() as $child) {
            $array['child_entries'][] = new static($child);
        }

        return $array;

    }

    /**
     * @param array $put_issues_here OUTREF
     * @return int returns 1 or 0
     * @throws
     */
    public function has_minimal_information(array &$put_issues_here = []) : int {

        $us = parent::has_minimal_information($put_issues_here);

        $b_bad_children = false;

        foreach ($this->entry->get_children() as $child) {
            $what =  FlowEntryArchive::create_archive($child)->has_minimal_information($put_issues_here);
            if (!$what) {$b_bad_children = true;}
            $us &= $what;
        }

        $name = $this->entry->get_title()??'{unnamed}';
        $guid = $this->entry->get_guid()??'{no-guid}';

        if ($b_bad_children) {
            $put_issues_here[] = "Entry $name of guid $guid children missing data ";
        }

        return intval($us);
    }


    /**
     * Writes the entry, and its children , to the archive
     * @throws
     */
    public function write_archive() : void {

        //todo write yaml to store a list of child guids

        parent::write_archive();

        //then write out each child
        foreach ($this->entry->get_children() as $child) {
            $archive = static::get_archive_from_cache($child->get_guid());
            if (!$archive) {
                $archive = FlowEntryArchive::create_archive($child);
                static::set_archive_to_cache($archive);
                $archive->write_archive();
            }

        }
    }


    /**
     * sets any data found in archive into this, over-writing data in entry object
     * @throws
     */
    public function read_archive() : void  {
        parent::read_archive();
        //clear out any children
        foreach ($this->entry->get_children() as $child) {
            $this->entry->remove_child($child);
        }

        //todo read yaml (created above) to get a list of child guids
        $child_guids = [];
        foreach ($child_guids as $child_guid) {
            $archive = static::get_archive_from_cache($child_guid);
            if (!$archive) {
                $node = FlowEntry::create_entry($this->get_entry()->get_project(),null);
                $node->set_guid($child_guid);
                $archive = FlowEntryArchive::create_archive($node);
                static::set_archive_to_cache($archive);
                $archive->read_archive();
            }
        }
    }


}