<?php

namespace app\models\entry\entry_node;

use app\helpers\Utilities;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use BlueM\Tree;
use Exception;
use JBBCode\DocumentElement;
use JBBCode\ElementNode;
use JBBCode\TextNode;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;

class FlowEntryNode extends FlowBase implements JsonSerializable,IFlowEntryNode {


    protected ?int $entry_node_id;
    protected ?int $flow_entry_id;
    protected ?string $flow_entry_guid;
    protected ?int $entry_node_created_at_ts;
    protected ?int $entry_node_updated_at_ts;
    protected ?string $entry_node_guid;
    protected ?string $bb_tag_name;
    protected ?string $entry_node_words;
    protected object $entry_node_attributes;

    /**
     * @var FlowEntryNode[] $children_nodes
     */
    protected array $children_nodes = [];

    protected ?int $parent_id;
    protected ?string $parent_entry_node_guid;
    protected ?IFlowEntryNode $parent = null;

    protected ?int $flow_applied_tag_id;
    protected ?string $flow_applied_tag_guid;
    protected ?FlowAppliedTag $flow_applied_tag = null;

    protected ?FlowTag $flow_tag = null;
    protected ?string $flow_tag_guid = null;
    protected ?int $flow_tag_id = null;

    private int $pass_through_value;


    public function get_node_id() : ?int {return $this->entry_node_id ;}
    public function get_node_guid() : ?string {return $this->entry_node_guid ;}

    public function get_node_bb_tag_name() : ?string {return $this->bb_tag_name ;}
    public function get_node_text() : ?string {return $this->entry_node_words ;}
    public function get_node_attributes() : object {return $this->entry_node_attributes ;}

    public function get_created_at_ts() : ?int {return $this->entry_node_created_at_ts ;}
    public function get_updated_at_ts() : ?int {return $this->entry_node_updated_at_ts ;}


    public function get_parent_guid() : ?string {return $this->parent_entry_node_guid ;}
    public function get_parent_id() : ?int {return $this->parent_id ;}
    public function get_parent() : ?IFlowEntryNode {return $this->parent ;}

    public function get_flow_entry_id() : ?int {return $this->flow_entry_id ;}
    public function get_flow_entry_guid() : ?string {return $this->flow_entry_guid ;}


    public function get_flow_tag_id() : ?int {return $this->flow_tag_id ;}
    public function get_flow_tag_guid() : ?string {return $this->flow_tag_guid ;}
    public function get_tag() : ?FlowTag {return $this->flow_tag ;}

    public function get_applied_flow_tag_id() : ?int {return $this->flow_applied_tag_id ;}
    public function get_applied_flow_tag_guid() : ?string {return $this->flow_applied_tag_guid ;}
    public function get_applied() : ?FlowAppliedTag {return $this->flow_applied_tag ;}

    public function __construct($object=null){
        $this->pass_through_value = 0;
        $this->entry_node_id = null;
        $this->flow_entry_id = null;
        $this->entry_node_created_at_ts = null;
        $this->entry_node_updated_at_ts = null;
        $this->entry_node_guid = null;
        $this->bb_tag_name = null;
        $this->entry_node_words = null;
        $this->flow_entry_guid = null;
        $this->parent_entry_node_guid = null;
        $this->parent_id = null;
        $this->parent = null;
        $this->entry_node_attributes = (object)[];
        $this->children_nodes = [];

        $this->flow_applied_tag_guid = null;
        $this->flow_applied_tag_id = null;
        $this->flow_applied_tag = null;

        $this->flow_tag = null;
        $this->flow_tag_id = null;
        $this->flow_tag_guid = null;

        if (empty($object)) {return null;}
        if (!is_object($object)) {
            $object = Utilities::convert_to_object($object);
        }

        foreach ($object as $key => $val) {
            if ($key === 'entry_node_attributes') {
               if (!is_object($object)) {
                   $this->entry_node_attributes = Utilities::convert_to_object($val);
                } else {
                   $this->entry_node_attributes = $val;
               }
            }
            elseif ($key === 'parent') {
                if ($val instanceof IFlowEntryNode) {
                    $this->parent = $val;
                } else {
                    $this->parent= new FlowEntryNode($val);
                }
            }
            elseif ($key === 'applied_tag') {
                if ($val instanceof FlowAppliedTag) {
                    $this->flow_applied_tag = $val;
                } else {
                    $this->flow_applied_tag= new FlowAppliedTag($val);
                }
            }
            elseif ($key === 'flow_tag') {
                if ($val instanceof FlowTag) {
                    $this->flow_tag = $val;
                } else {
                    $this->flow_tag= new FlowTag($val);
                }
            }
            elseif ($key === 'children_nodes') {
                if (is_string($val)) {
                    $nodes = Utilities::convert_to_object($val);
                }
                if (empty($nodes)) {continue;}
                foreach ($nodes as $index => $node_thing) {
                    WillFunctions::will_do_nothing($index);
                    if ($node_thing instanceof IFlowEntryNode) {
                        $this->children_nodes[] = $val;
                    } else {
                        $this->children_nodes[] = new FlowEntryNode($node_thing);
                    }
                }
                
            } else {
                if (property_exists($this,$key)) {
                    $careful_value = $val;
                    if ($val === '') {$careful_value = null;}
                    $this->$key = $careful_value;
                }
            }
        }
        if (empty($this->parent_entry_node_guid) && $this->parent) {
            $this->parent_entry_node_guid = $this->parent->entry_node_guid;
        }

        if ($this->bb_tag_name=== static::FLOW_TAG_BB_CODE_NAME) {
            $tag_guid = null;
            foreach ($this->attributes as $attribute) {
                if (WillFunctions::is_valid_guid_format($attribute)) { $tag_guid = $attribute; break;}
            }
            if ($tag_guid && $this->parent) {
                $this->parent->set_tag_guid($tag_guid);
            }
        }
    }
    
    public function to_array() : array  {
        return [
            'flow_entry_guid' => $this->flow_entry_guid,
            'entry_node_guid' => $this->entry_node_guid,
            'parent_entry_node_guid' => $this->parent_entry_node_guid,
            'flow_tag_guid' => $this->flow_tag_guid,
            'flow_applied_tag_guid' => $this->flow_applied_tag_guid,
            'bb_tag_name' => $this->bb_tag_name,
            'entry_node_attributes' => $this->entry_node_attributes,
            'entry_node_words' => $this->entry_node_words,
            'entry_node_created_at_ts' => $this->entry_node_created_at_ts,
            'entry_node_updated_at_ts' => $this->entry_node_updated_at_ts,
            'children_nodes' => $this->children_nodes,

        ];
    }

    public function jsonSerialize(): array
    {
        return $this->to_array();
    }

    public function set_tag_guid(?string $guid) :void {
        $this->flow_tag_guid = $guid;
    }

    /**
     * @param DocumentElement $root
     * @return IFlowEntryNode[]
     */
    public static function parse_root(DocumentElement $root): array
    {
        $temp_counter = 0;
        return static::parse_root_inner($root,null,$temp_counter);
    }

    /**
     * @param ElementNode|TextNode $node
     * @param IFlowEntryNode|null $parent
     * @param $temp_counter
     * @return IFlowEntryNode[]
     */
    protected static function parse_root_inner(ElementNode|TextNode $node,?IFlowEntryNode $parent,&$temp_counter) :array {

        /**
         * @var IFlowEntryNode $ret
         */
        $ret = [];
        try {

            $temp_counter++;
            switch (get_class($node)) {

                case "JBBCode\DocumentElement":
                case "JBBCode\ElementNode":
                {
                    $entry_node =
                        new FlowEntryNode([
                            'bb_tag_name' => $node->getTagName(),
                            'parent' => $parent,
                            'entry_node_attributes'=>$node->getAttribute() ?? []
                        ]);
                    $entry_node->pass_through_value = $temp_counter++;
                    if ($node->getTagName() !== 'flow_tag') {
                        $ret[] = $entry_node;
                    }

                    $children_returns = [];
                    foreach ($node->getChildren() as $child) {
                        $children_returns[] = static::parse_root_inner($child, $entry_node,$temp_counter);
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
                            'text',
                            'entry_node_words'=> $node->getAsText(),
                            'parent' => $parent,
                    ]);
                    $entry_node->pass_through_value = $temp_counter++;

                    preg_match('/\[flow_tag\s+tag=(?P<guid>[\da-fA-F]+)\s*]/',
                        $node->getAsText()??'', $output_array);
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
                    throw new LogicException("[parse_root_inner] Unexpected class " . get_class($node));
                }
            }
        } finally {
            //do the tree
            $data = [];
            $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','entry_node'=>null];
            foreach ($ret as $whrat) {
                $data[] = ['id' => $whrat->pass_through_value, 'parent' => $whrat->parent?->pass_through_value??0,
                    'title' => $whrat->entry_node_words,'entry_node'=>$whrat];
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
        }
        return $ret;
    }

    /**
     * @param bool $b_do_transaction
     * @param bool $b_save_children
     * @return void
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false, bool $b_save_children = false) :void {
        $db = null;

        try {
            $db = static::get_connection();
            if ($b_do_transaction && !$db->inTransaction()) {$db->beginTransaction();}

            //guids to find :
            // flow_tag_guid flow_applied_tag_guid

            if (!$this->flow_entry_id && $this->flow_entry_guid) {
                $this->flow_entry_id = $db->cell(
                    "SELECT id  FROM flow_entries WHERE flow_entry_guid = UNHEX(?)",
                    $this->flow_entry_guid);
            }

            if (!$this->parent_id && $this->parent_entry_node_guid) {
                $this->parent_id = $db->cell(
                    "SELECT id  FROM flow_entry_nodes WHERE entry_node_guid = UNHEX(?)",
                    $this->parent_entry_node_guid);
            }

            $entry_node_attributes_as_json = JsonHelper::toString($this->entry_node_attributes);
            $save_info = [
                'flow_entry_parent_id' => $this->flow_entry_parent_id,
                'bb_tag_name' => $this->bb_tag_name,
                'entry_node_words' => $this->entry_node_words,
                'entry_node_attributes' => $entry_node_attributes_as_json,
            ];

            if ($this->entry_node_guid && $this->entry_node_id) {

                $db->update('flow_entry_nodes',$save_info,[
                    'id' => $this->entry_node_id
                ]);

            }
            elseif ($this->entry_node_guid) {
                $insert_sql = "
                    INSERT INTO flow_entry_nodes
                        (flow_entry_id,flow_entry_parent_id, bb_tag_name, entry_node_words, entry_node_guid, entry_node_attributes)  
                    VALUES (?,?,?,?,UNHEX(?),?)
                    ON DUPLICATE KEY UPDATE flow_entry_id =   VALUES(flow_entry_id),
                                            flow_entry_parent_id =     VALUES(flow_entry_parent_id),
                                            bb_tag_name =     VALUES(bb_tag_name)   ,    
                                            entry_node_words =     VALUES(entry_node_words)   ,    
                                            entry_node_attributes =     VALUES(entry_node_attributes)     
                ";
                $insert_params = [
                    $this->flow_entry_id,
                    $this->parent_id,
                    $this->bb_tag_name,
                    $this->entry_node_words,
                    $entry_node_attributes_as_json
                ];
                $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
                $this->entry_node_id = $db->lastInsertId();
            }
            else {
                $db->insert('flow_entry_nodes',$save_info);
                $this->entry_node_id = $db->lastInsertId();
            }

            if (!$this->entry_node_guid) {
                $this->entry_node_guid = $db->cell(
                    "SELECT HEX(entry_node_guid) as entry_node_guid FROM flow_entry_nodes WHERE id = ?",
                    $this->entry_node_id);

                if (!$this->entry_node_guid) {
                    throw new RuntimeException("Could not get entry node guid using id of ". $this->entry_node_id);
                }
            }

            if ($this->flow_tag_id) {
                //instantiate applied tag object and save it
                if (!$this->flow_applied_tag) {
                    $this->flow_applied_tag = new FlowAppliedTag();
                }
                $this->flow_applied_tag->flow_tag_id = $this->flow_tag_id;
                $this->flow_applied_tag->tagged_flow_entry_node_id = $this->entry_node_id;
                $this->flow_applied_tag->flow_applied_tag_guid = $this->flow_applied_tag_guid;
                $this->flow_applied_tag->id = $this->flow_applied_tag_id;
                $this->flow_applied_tag->save();
            }

            if ($b_save_children) {
                foreach ($this->children_nodes as $child) {
                    $child->parent_id = $this->entry_node_id;
                    $child->save(false,true);
                }
            }

            if ($b_do_transaction && $db->inTransaction()) {$db->commit(); }
        } catch (Exception $e) {
            if ($b_do_transaction && $db) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
            static::get_logger()->alert("Entry Node model cannot save ",['exception'=>$e->getMessage()]);
            throw $e;
        }
    }


    public function get_as_bb_code() :string
    {

        if (!$this->bb_tag_name) {throw new RuntimeException("[]get_as_bb_code no bb code");}
        if ($this->entry_node_words) {
            return $this->entry_node_words;
        }
        $str = "[".$this->bb_tag_name;
        if (!empty($this->entry_node_attributes)) {
            if (isset($this->entry_node_attributes[$this->bb_tag_name])) {
                $str .= "=".$this->attribute[$this->tagName];
            }

            foreach ($this->entry_node_attributes as $key => $value) {
                if ($key == $this->bb_tag_name) {
                    continue;
                } else {
                    $str .= " ".$key."=" . $value;
                }
            }
        }
        $str .= "]";
        foreach ($this->children_nodes as $child) {
            $str .= $child->get_as_bb_code();
        }
        $str .= "[/".$this->bb_tag_name."]";

        return $str;
    }

}