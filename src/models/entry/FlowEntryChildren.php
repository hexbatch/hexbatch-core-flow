<?php

namespace app\models\entry;


use app\hexlet\WillFunctions;
use app\models\entry\brief\IFlowEntryBrief;
use app\models\project\FlowProject;
use Exception;

/**
 * All the public resources and files an entry uses is in the project resources folder
 * so the html is simply a file in the top project directory
 */
abstract class FlowEntryChildren extends FlowEntryFiles  {


    /**
     * @var array<string,IFlowEntry> $child_entries
     */
    public array $child_entries;



    /**
     * @param array|object|FlowEntryBase|IFlowEntryBrief|null $object
     * @param FlowProject|null $project
     * @throws Exception
     */
    public function __construct($object,?FlowProject $project){
        parent::__construct($object,$project);

        $this->child_entries = [];

        if (empty($object)) {
            return;
        }

        if ($object instanceof IFlowEntry || $object instanceof IFlowEntryBrief) {


            foreach ($object->get_children() as $child) {
                $this->child_entries[] = static::create_entry($project,$child);
            }


        } else {


            if (is_object($object) && property_exists($object,'child_entries') && is_array($object->child_entries)) {
                $children_to_copy = $object->child_entries;
            } elseif (is_array($object) && array_key_exists('child_entries',$object) && is_array($object['child_entries'])) {
                $children_to_copy  = $object['child_entries'];
            } else {
                $children_to_copy = [];
            }
            if (count($children_to_copy)) {
                foreach ($children_to_copy as $att) {
                    $this->child_entries[] = static::create_entry($project,$att);
                }
            }

        }

    }


    /**
     * @return IFlowEntry[]
     */
    public function get_children(): array {return array_values($this->child_entries);}

    /**
     * @return string[]
     */
    public function get_children_guids(): array {

        $ret = [];
        array_walk($this->child_guids,

            function(IFlowEntry $x)  use(&$ret)
            {
                $ret[] = $x->get_guid();
            }
        );
        return $ret;
    }

    /**
     * @return int[]
     */
    public function get_children_ids() : array {
        $ret = [];
        array_walk($this->child_guids,

            function(IFlowEntry $x)  use(&$ret)
            {
                $ret[] = $x->get_id();
            }
        );
        return $ret;

    }

    public function add_child(IFlowEntry $what): void {
        if (!array_key_exists($what->get_guid(),$this->child_entries)) {
            $this->child_entries[$what->get_guid()] = $what;
        }
    }

    public function remove_child(IFlowEntry $what): void {
        if (array_key_exists($what->get_guid(),$this->child_entries)) {
            unset($this->child_entries[$what->get_guid()]);
        }
    }


    /**
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false, bool $b_save_children = false) :void {

        parent::save_entry($b_do_transaction,$b_save_children);

        $db = null;
        try {
           if ($b_save_children) {
               $db = static::get_connection();
               if ($b_do_transaction) {$db->beginTransaction(); }
               foreach ($this->child_entries as $child_guid => $child) {
                   WillFunctions::will_do_nothing($child_guid);
                   $child->save_entry();
               }
               if ($b_do_transaction) {$db->commit();}
           }


        } catch (Exception $e) {
            static::get_logger()->alert("Entry Child model cannot save ",['exception'=>$e]);
            if ($b_do_transaction && $db ) {$db->rollBack();}
            throw $e;
        }
    }


}