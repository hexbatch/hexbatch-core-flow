<?php

namespace app\models\entry\entry_node;


use app\models\base\FlowBase;
use app\models\entry\IFlowEntry;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use InvalidArgumentException;
use JBBCode\DocumentElement;

class EntryNodeDocument extends FlowBase {

    const B_ENABLE_LOGGING = false;

    /**
     * @param DocumentElement $root
     * @return IFlowEntryNode[]
     */
    public static function parse_root(DocumentElement $root): array
    {

        $found_nodes =  FlowEntryNode::parse_root($root);

        if (static::B_ENABLE_LOGGING) {
            static::get_logger()->info("found nodes",$found_nodes);
        }
        return $found_nodes;
    }

    /**
     *
     * @param IFlowEntry|null $entry if given, returns bb code for that entry can be filtered by node, tag and applied
     *                                  will return a string array of one element, unless filtered has more than one result
     *
     * @param IFlowEntryNode|null $node if given will return bb code for that node and children and can be filtered by tag and applied
     *                                  will return a string array of one element, unless filtered has more than one result
     *
     * @param FlowTag|null $tag can be used to filter the above bb code to the children nodes only having parents of type tag
     *                                  If the above does not have a type tag, then returns nothing
     *
     *                              If passed by itself, without entry or node, it will find all node across the project that link to it,
     *                              and return the bb code of the parents one each to an index in string array
     *
     * @param FlowAppliedTag|null $applied can be used to filter the above bb code to the children of the parent that has this applied
     *                                  If passed by itself, without entry or node, it will find the node in the project, return its html
     *                                  or nothing if missing
     * @return string[]
     *
     *
     * @throws InvalidArgumentException  Will throw if all params are null
     *
     * Its not an error to mix things that do not belong to each other, will simply get nothing back
     */
    public static function get_as_bb_code(
        ?IFlowEntry $entry = null,
        ?IFlowEntryNode $node = null,
        ?FlowTag $tag = null,
        ?FlowAppliedTag $applied = null,
    ) : array
    {
        if (empty($entry) && empty($node) && empty($tag) && empty($applied)) {
            throw new InvalidArgumentException("[get_as_bb_code] need at last one param filled in");
        }

        /*
         * TODO
         * This is a search function for the bb nodes: make the search params and class for the nodes, and pass in the above
         * Once get hits, then for each returned, call its bb code function and add to return array
         *  entry , node, tag, applied can be combined and mixed in the search function
         *      entry can be constrained by node, tag, applied
         *      node can be constrained by entry, tag, applied
         *      tag can be constrained by entry, node, applied
         *      applied can be constrained by entry, node, tag
         */

        return [];
    }
}