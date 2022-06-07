<?php

namespace app\models\entry_node;


use app\helpers\Utilities;
use app\hexlet\BBHelper;
use app\models\entry\IFlowEntry;
use app\models\tag\FlowTag;
use app\models\tag\IFlowAppliedTag;
use BlueM\Tree;
use Exception;
use InvalidArgumentException;
use JBBCode\ElementNode;
use JBBCode\TextNode;
use JsonException;
use LogicException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class EntryNodeDocument extends EntryNodeContainer implements  IFlowEntryNodeDocument{

    const B_ENABLE_LOGGING = false;

    protected IFlowEntry $entry;

    public function get_entry() : IFlowEntry {return $this->entry;}

    public function __construct(IFlowEntry $entry){
        parent::__construct();
        $this->entry = $entry;
    }

    /**
     * @param bool $b_do_transaction
     * @return void
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false) : void{
        $remember_top_node = $this->get_top_node_of_entry()->copy();

        $new_lot_number = $remember_top_node->get_lot_number() + 1;
        $this->flat_contents = $this->parse_root($this->entry->get_bb_code(),$new_lot_number);

        if (empty($this->flat_contents)) {
           return;
        }

        $new_root = $this->flat_contents[0];
        $new_root->set_node_guid($remember_top_node?->get_node_guid());
        $new_root->set_entry_id($this->entry->get_id());
        $new_root->set_entry_guid($this->entry->get_guid());

        $new_root->match_text_nodes_with_existing($remember_top_node);

        $new_root->save($b_do_transaction,true);


        $this->save_as_yaml($this->flat_contents);

        //remove guids not used
        $remember_top_node?->prune_node(false,$new_root,IFlowEntryNode::PRUNE_KEEP_THESE);
    }

    /**
     * @return IFlowEntryNode|null
     * @throws Exception
     */
    public function get_top_node_of_entry() : ?IFlowEntryNode {
        $params = new EntryNodeSearchParams();
        $params->addEntryGuid($this->entry->get_guid());
        $params->setIsTopNode(true);
        $search = new EntryNodeSearch();
        $res = $search->search($params)->getTopContents();
        return $res[0]??null;
    }

    /**
     * @param IFlowEntryNode[] $nodes
     * @return void
     * @throws JsonException
     */
    protected function save_as_yaml(array $nodes): void
    {
        $public_data = Utilities::convert_to_object($nodes);
        foreach ($public_data as $public) {
            if (property_exists($public,'children_nodes')) {
                unset($public->children_nodes);
            }
        }
        $stuff_yaml = Yaml::dump(Utilities::deep_copy($public_data,true));

        $yaml_path = $this->entry->get_entry_folder() . DIRECTORY_SEPARATOR . IFlowEntryNodeDocument::ENTRY_NODE_FILE_NAME;
        $b_ok = file_put_contents($yaml_path,$stuff_yaml);
        if ($b_ok === false) {throw new RuntimeException("[save_as_yaml] Could not write to $yaml_path");}
    }


    /**
     * @param ElementNode|TextNode $node
     * @param IFlowEntryNode|null $parent
     * @param int $temp_counter
     * @param array<string,int> $guid_lib
     * @param int $lot_number
     * @return IFlowEntryNode[]
     * @throws JsonException
     */
    protected  function parse_root_inner(ElementNode|TextNode $node,?IFlowEntryNode $parent,
                                         int &$temp_counter,array &$guid_lib,int $lot_number) :array
    {

        /**
         * @var IFlowEntryNode $ret
         */
        $ret = [];

        $temp_counter++;
        switch (get_class($node)) {

            case "JBBCode\DocumentElement":
            case "JBBCode\ElementNode":
            {
                $attributes = $node->getAttribute()??[];
                if (!is_object($attributes)) {
                    $attributes = Utilities::convert_to_object($attributes);
                }
                $found_guid = $attributes?->guid??null;
                if ($found_guid && array_key_exists($found_guid,$guid_lib)) {
                    $found_guid = null; //if a repeat do not use again
                    unset($attributes->guid);
                }
                if ($found_guid) {
                    Utilities::valid_guid_format_or_null_or_throw($found_guid);
                    $guid_lib[$found_guid] = 1+ ($guid_lib[$found_guid]??0);
                }

                $entry_node =
                    new FlowEntryNode([
                        'node_guid' => $found_guid,
                        'bb_tag_name' => $node->getTagName(),
                        'parent' => null,
                        'node_attributes'=>$attributes,
                        'lot_number' => $lot_number
                    ]);
                $entry_node->set_pass_through_int($temp_counter++);
                $entry_node->set_parent($parent);
                $ret[] = $entry_node;

                $children_returns = [];
                foreach ($node->getChildren() as $child) {
                    $children_returns[] =
                        $this->parse_root_inner($child, $entry_node,$temp_counter,$guid_lib,$lot_number);
                }
                foreach ($children_returns as $chit) {
                    if (is_array($chit)) {
                        $ret = array_merge($ret, $chit);
                    } else {
                        if ($node->getTagName() !== 'flow_tag') {
                            $ret[] = $chit;
                        }

                    }
                }


                break;
            }
            case "JBBCode\TextNode":
            {
                $entry_node = new FlowEntryNode( [
                    'bb_tag_name' => 'text',
                    'node_words'=> $node->getAsText(),
                    'parent' => null,
                    'lot_number' => $lot_number
                ]);
                $entry_node->set_pass_through_int($temp_counter++);
                $entry_node->set_parent($parent);


                $ret[] = $entry_node;
                break;
            }
            default:
            {
                throw new LogicException("[parse_root_inner] Unexpected class " . get_class($node));
            }
        }
        return $ret;
    }

    /**
     * @param string $bb_code
     * @param int $lot_number
     * @return IFlowEntryNode[]
     * @throws JsonException
     */
    protected  function parse_root(string $bb_code,int $lot_number): array
    {
        $parser = BBHelper::get_parsed_bb_code($bb_code);
        $root =  $parser->getRoot();

        $temp_counter = 0;
        /**
         * @var array<string,int> $guid_lib
         */
        $guid_lib = [];
        $found_nodes =  $this->parse_root_inner($root,null,$temp_counter,$guid_lib,$lot_number);

        //do the tree
        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','entry_node'=>null];
        foreach ($found_nodes as $whrat) {
            $data[] = ['id' => $whrat->get_pass_through_int(), 'parent' => $whrat->get_parent()?->get_pass_through_int()??0,
                'title' => $whrat->get_node_text(),'entry_node'=>$whrat];
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

        if (static::B_ENABLE_LOGGING) {
            static::get_logger()->info("found nodes",$ret);
        }

        return $ret;




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
     * @param IFlowAppliedTag[]|string[] $applied can be used to filter the above bb code to the children of the parent that has this applied
     *                                  If passed by itself, without entry or node, it will find the node in the project, return its html
     *                                  or nothing if missing
     * @return string[]
     *
     *
     * @throws InvalidArgumentException|Exception  Will throw if all params are null
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

        $search = new EntryNodeSearch();
        $found_nodes = $search->search($params)->getTopContents();
        $ret = [];
        foreach ($found_nodes as $node) {
            $ret[] = $node->get_as_bb_code();
        }

        return $ret;
    }

    /**
     * @param FlowTag[] $from_tags
     * @param IFlowEntryNodeDocument|null $target_doc
     * @param FlowTag|null $target_tag
     * @return string|null
     * @throws Exception
     */
    public function insert_at(array $from_tags, ?IFlowEntryNodeDocument $target_doc = null, ?FlowTag $target_tag=null )
    : ?string
    {
        if (empty($from_tags)) {return null;}
        $params = new EntryNodeSearchParams();
        $params->addTagGuid($from_tags);
        $params->addEntryGuid($this->get_entry());
        $search = new EntryNodeSearch();
        $top_found_nodes =$search->search($params)->getTopContents();
        $found_bb_code = [];
        foreach ($top_found_nodes as $found_node) {
            $found_bb_code[] = $found_node->get_as_bb_code();
        }
        $source_bb_code = implode("",$found_bb_code);
        if (!$target_doc) {
            return $source_bb_code;
        }
        if (!$target_tag) {throw new InvalidArgumentException("Need a target tag with the target doc");}

        $params_target = new EntryNodeSearchParams();
        $params_target->addEntryGuid($target_doc->get_entry());

        $search_target = new EntryNodeSearch();
        $top_target_nodes =$search_target->search($params_target)->getTopContents();
        $target_bb_code =  $top_target_nodes[0]->get_as_bb_code();

        $params_target->addTagGuid($target_tag);
        $top_target_bb_codes =$search_target->search($params_target)->getFlatContents();

        $bb_out = null;
        foreach ($top_target_bb_codes as $bb_tag) {
            if ($bb_tag->get_node_bb_tag_name() === IFlowEntryNode::FLOW_TAG_BB_CODE_NAME) {
                $bb_code_of_tag = $bb_tag->get_as_bb_code();
                $bb_out = str_replace($bb_code_of_tag,$source_bb_code,$target_bb_code);
            }

        }
        return $bb_out;
    }
}