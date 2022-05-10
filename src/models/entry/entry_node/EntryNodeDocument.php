<?php

namespace app\models\entry\entry_node;


use app\helpers\Utilities;
use app\hexlet\JsonHelper;
use app\models\base\FlowBase;
use app\models\entry\IFlowEntry;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class EntryNodeDocument extends FlowBase implements  IFlowEntryNodeDocument{

    const B_ENABLE_LOGGING = false;

    protected IFlowEntry $entry;

    public function __construct(IFlowEntry $entry){
        $this->entry = $entry;
    }

    public function save(bool $b_do_transaction = false) {
        $found_nodes = $this->parse_root($this->entry->get_bb_code());
        foreach ($found_nodes as $node) {
            $node->save($b_do_transaction,true);
            if ($node->get_parent()) { break;}
        }
        $this->save_as_yaml($found_nodes);
    }

    /**
     * @param IFlowEntryNode[] $nodes
     * @return void
     */
    protected function save_as_yaml(array $nodes): void
    {
        $public_data = Utilities::convert_to_object($nodes);
        $stuff_yaml = Yaml::dump($public_data);

        $yaml_path = $this->entry->get_entry_folder(). DIRECTORY_SEPARATOR . IFlowEntryNodeDocument::ENTRY_NODE_FILE_NAME;
        $b_ok = file_put_contents($yaml_path,$stuff_yaml);
        if ($b_ok === false) {throw new RuntimeException("[save_as_yaml] Could not write to $yaml_path");}
    }


    /**
     * @param string $bb_code
     * @return IFlowEntryNode[]
     */
    protected  function parse_root(string $bb_code): array
    {
        $parser = JsonHelper::get_parsed_bb_code($bb_code);
        $found_nodes =  FlowEntryNode::parse_root($parser->getRoot());

        if (static::B_ENABLE_LOGGING) {
            static::get_logger()->info("found nodes",$found_nodes);
        }
        return $found_nodes;
    }

    /**
     *
     * @param IFlowEntry[]|string[] $entry if given, returns bb code for that entry can be filtered by node, tag and applied
     *                                  will return a string array of one element, unless filtered has more than one result
     *
     * @param IFlowEntryNode[]|string[] $node if given will return bb code for that node and children and can be filtered by tag and applied
     *                                  will return a string array of one element, unless filtered has more than one result
     *
     * @param FlowTag[]|string[] $tag can be used to filter the above bb code to the children nodes only having parents of type tag
     *                                  If the above does not have a type tag, then returns nothing
     *
     *                              If passed by itself, without entry or node, it will find all node across the project that link to it,
     *                              and return the bb code of the parents one each to an index in string array
     *
     * @param FlowAppliedTag[]|string[] $applied can be used to filter the above bb code to the children of the parent that has this applied
     *                                  If passed by itself, without entry or node, it will find the node in the project, return its html
     *                                  or nothing if missing
     * @return string[]
     *
     *
     * @throws InvalidArgumentException  Will throw if all params are null
     *
     * Its not an error to mix things that do not belong to each other, will simply get nothing back
     */
    public  function get_as_bb_code(
        array $entry = [],
        array $node = [],
        array $tag = [],
        array $applied = []
    ) : array
    {
        if (empty($entry) && empty($node) && empty($tag) && empty($applied)) {
            throw new InvalidArgumentException("[get_as_bb_code] need at last one param filled in");
        }

        /*
         *
         * This is a search function for the bb nodes: make the search params and class for the nodes, and pass in the above
         * Once get hits, then for each returned, call its bb code function and add to return array
         *  entry , node, tag, applied can be combined and mixed in the search function
         *      entry can be constrained by node, tag, applied
         *      node can be constrained by entry, tag, applied
         *      tag can be constrained by entry, node, applied
         *      applied can be constrained by entry, node, tag
         */
        $params = new EntryNodeSearchParams();
        $params->addNodeGuid($node);
        $params->addEntryGuid($entry);
        $params->addAppliedGuid($applied);
        $params->addTagGuid($tag);

        $found_nodes = EntryNodeSearch::search($params);
        $ret = [];
        foreach ($found_nodes as $node) {
            $ret[] = $node->get_as_bb_code();
        }

        return $ret;
    }

    /**
     * @param IFlowEntryNodeDocument $doc
     * @param FlowTag[] $from
     * @param FlowTag $here
     * @return string
     */
    public function insert_at(IFlowEntryNodeDocument $doc,array $from,FlowTag $here) : string {
        //todo implement the insert_at here
        return '';
    }
}