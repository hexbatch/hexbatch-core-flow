<?php
namespace app\models\entry\entry_node;

use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use BlueM\Tree;
use JBBCode\ElementNode;
use JBBCode\TextNode;
use LogicException;

class EntryNode extends FlowBase {
    var ?int $id;
    var ?string $dom_name;
    var ?string $value;
    var ?string $tag_guid;
    var ?string $entry_node_guid;
    var ?EntryNode $parent;
    var array $attributes = [];

    static int $temp_counter = 1;

    /**
     * @param string|null $dom_name
     * @param string|null $value
     * @param EntryNode|null $parent
     * @param array $attributes
     */
    public function __construct(?string $dom_name, ?string $value, ?EntryNode $parent ,array $attributes=[])
    {
        $this->id = static::$temp_counter;
        $this->dom_name = $dom_name;
        $this->value = $value;
        $this->attributes = $attributes;
        $this->entry_node_guid = $this->dom_name. "-".static::$temp_counter;
        $this->parent = $parent;

        static::$temp_counter++;

        if ($this->dom_name==='flow_tag') {
            $tag_guid = null;
            foreach ($this->attributes as $attribute) {
                if (WillFunctions::is_valid_guid_format($attribute)) { $tag_guid = $attribute; break;}
            }
            if ($tag_guid && $this->parent) {
                $this->parent->set_tag_guid($tag_guid);
            }
        }
    }

    public function set_tag_guid(string $guid) { $this->tag_guid = $guid;}


    public static function parse_root(ElementNode|TextNode $node,?EntryNode $parent) :array {

        $b_do_logging = !$parent;
        $ret = [];
        try {


            switch (get_class($node)) {

                case "JBBCode\DocumentElement":
                case "JBBCode\ElementNode":
                {
                    $entry_node =
                        new EntryNode($node->getTagName(), null, $parent, $node->getAttribute() ?? []);
                    if ($node->getTagName() !== 'flow_tag') {
                        $ret[] = $entry_node;
                    }

                    $children_returns = [];
                    foreach ($node->getChildren() as $child) {
                        $children_returns[] = static::parse_root($child, $entry_node);
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
                    $entry_node = new EntryNode('text', $node->getAsText(), $parent);
                    preg_match('/\[flow_tag\s+tag=(?P<guid>[\da-fA-F]+)\s*]/', $node->getAsText()??'', $output_array);
                    if ($output_array['guid']??null) {
                        $tag_guid = $output_array['guid'];
                        $parent->set_tag_guid($tag_guid);
                        return []; //do not add to nodes in this special case
                    }
                    $ret[] = $entry_node;
                    break;
                }
                default:
                {
                    throw new LogicException("[parse_root] Unexpected class " . get_class($node));
                }
            }
        } finally {
            if ($b_do_logging) {
                //do the tree
                $data = [];
                $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','entry_node'=>null];
                foreach ($ret as $whrat) {
                    $data[] = ['id' => $whrat->id, 'parent' => $whrat->parent?->id??0,
                        'title' => $whrat->entry_node_guid,'entry_node'=>$whrat];
                }

                $tree = new Tree(
                    $data,
                    ['rootId' => -1]
                );

                $sorted_nodes =  $tree->getNodes();
                $new_ret = [];
                foreach ($sorted_nodes as $node) {
                    $what =  $node->entry_node??null;
                    if ($what) {$new_ret[] = $what;}
                }
                $ret = $new_ret;
                static::get_logger()->info("found nodes",$ret);
            }
        }
        return $ret;
    }
}