<?php

namespace app\models\entry\entry_node;

use app\models\base\FlowBase;
use BlueM\Tree;

class EntryNodeContainer extends FlowBase{
    /**
     * @var IFlowEntryNode[] $flat_contents
     */
    protected array $flat_contents;


    /**
     * @return IFlowEntryNode[]
     */
    public function getFlatContents(): array
    {
        return $this->flat_contents;
    }

    /**
     * @return IFlowEntryNode[]
     */
    public function getTopContents(): array
    {
        $ret = [];
        foreach ($this->flat_contents as $node) {
            if (($node->get_parent_guid() || $node->get_parent())) {
                continue;
            }
            $ret[] = $node;
        }
        return $ret;
    }

    /**
     * Assumes parent id set
     * @return IFlowEntryNode[]
     */
    protected function sort_nodes_by_parent_id() : array  {


        //do the tree
        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','entry_node'=>null];
        foreach ($this->getFlatContents() as $whrat) {
            $data[] = ['id' => $whrat->get_node_id(), 'parent' => $whrat->get_parent_id()??0,
                'title' => $whrat->get_node_guid(),'entry_node'=>$whrat];
        }

        $tree = new Tree(
            $data,
            ['rootId' => -1]
        );

        $sorted_nodes =  $tree->getNodes();
        $ret = [];
        foreach ($sorted_nodes as $node) {
            $what =  $node->entry_node??null;
            if ($what) {$ret[] = $what;}
        }
        return $ret;
    }


}