<?php

namespace app\models\entry_node;

use app\helpers\Utilities;
use app\hexlet\BBHelper;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use app\models\tag\IFlowAppliedTag;
use Exception;
use JsonException;
use JsonSerializable;
use LogicException;
use PDO;
use RangeException;
use RuntimeException;


class FlowEntryNode extends FlowBase implements JsonSerializable,IFlowEntryNode {


    protected ?int $node_id;
    protected ?int $flow_entry_id;
    protected ?string $flow_entry_guid;
    protected ?int $node_created_at_ts;
    protected ?int $node_updated_at_ts;
    protected ?string $node_guid;
    protected ?string $bb_tag_name;
    protected ?string $node_words;
    protected object $node_attributes;

    /**
     * @var FlowEntryNode[] $children_nodes
     */
    protected array $children_nodes = [];

    protected ?int $parent_id;
    protected ?string $parent_guid;
    protected ?IFlowEntryNode $parent = null;

    protected ?int $child_position;
    protected ?int $lot_number;

    protected ?int $flow_applied_tag_id;
    protected ?string $flow_applied_tag_guid;
    protected ?IFlowAppliedTag $flow_applied_tag = null;

    protected ?FlowTag $flow_tag = null;
    protected ?string $flow_tag_guid = null;
    protected ?int $flow_tag_id = null;

    protected ?int $pass_through_int;
    protected ?float $pass_through_float;

    public function get_pass_through_int(): ?int {return $this->pass_through_int;}
    public function get_pass_through_float(): ?float {return $this->pass_through_float;}
    public function set_pass_through_float(?float $pass) :void  { $this->pass_through_float = $pass ;}


    public function get_node_id() : ?int {return $this->node_id ;}
    public function get_node_guid() : ?string {return $this->node_guid ;}

    public function get_node_bb_tag_name() : ?string {return $this->bb_tag_name ;}
    public function get_node_text() : ?string {return $this->node_words ;}
    public function get_node_attributes() : object {return $this->node_attributes ;}

    public function get_created_at_ts() : ?int {return $this->node_created_at_ts ;}
    public function get_updated_at_ts() : ?int {return $this->node_updated_at_ts ;}


    public function get_parent_guid() : ?string {return $this->parent_guid ;}
    public function get_parent_id() : ?int {return $this->parent_id ;}
    public function get_parent() : ?IFlowEntryNode {return $this->parent ;}

    public function get_child_position() : ?int {return $this->child_position ;}
    public function get_lot_number() : int {return $this->lot_number??1 ;}

    public function get_flow_entry_id() : ?int {return $this->flow_entry_id ;}
    public function get_flow_entry_guid() : ?string {return $this->flow_entry_guid ;}


    public function get_flow_tag_id() : ?int {return $this->flow_tag_id ;}
    public function get_flow_tag_guid() : ?string {return $this->flow_tag_guid ;}
    public function get_tag() : ?FlowTag {return $this->flow_tag ;}

    public function get_applied_flow_tag_id() : ?int {return $this->flow_applied_tag_id ;}
    public function get_applied_flow_tag_guid() : ?string {return $this->flow_applied_tag_guid ;}
    public function get_applied() : ?IFlowAppliedTag {return $this->flow_applied_tag ;}

    /**
     * @return IFlowEntryNode[]
     */
    public function get_children(): array {
        return $this->children_nodes;
    }

    public function is_text_node() : bool {
        return $this->get_node_bb_tag_name() === IFlowEntryNode::TEXT_BB_CODE_NAME;
    }

    public function empty_children() : void {
        $this->children_nodes = [];
    }

    public function add_child(IFlowEntryNode $node): void {

        //see if already child here
        if ($node->get_node_guid()) {
            foreach ($this->children_nodes as $child) {
                if ($child->get_node_guid() === $node->get_node_guid()) {
                    throw new LogicException(
                        sprintf(
                            "Child guid %s already added to %s",$node->get_node_guid(),$this->get_node_guid()));
                }
            }
        } else if ($node->get_pass_through_int()) {
            foreach ($this->children_nodes as $child) {
                if ($child->get_pass_through_int() === $node->get_pass_through_int()) {
                    throw new LogicException(
                        sprintf(
                            "Child passthrough %s already added to %s",$node->get_pass_through_int(),$this->get_pass_through_int()));
                }
            }
        }
        $node->set_child_position(count($this->children_nodes));
        $node->set_lot_number($this->get_lot_number());
        $this->children_nodes[] = $node;
    }


    public function set_pass_through_int(int $pass_through): void {
        $this->pass_through_int = $pass_through;
    }

    public function set_child_position(int $child_pos): void {
        if ($child_pos < 0) { throw new RangeException("Children can only be numbered from >=0 ");}
        $this->child_position = $child_pos;
    }

    public function set_lot_number(int $lot): void {
        if ($lot < 0) { throw new RangeException("Lot can only be numbered from >=0 ");}
        $this->lot_number = $lot;
    }

    public function set_entry_id(int $entry_id): void {
        $this->flow_entry_id = $entry_id;
        foreach ($this->children_nodes as $child) { $child->set_entry_id($entry_id);}
    }


    public function set_entry_guid(?string $entry_guid): void {
        Utilities::valid_guid_format_or_null_or_throw($entry_guid);
        $this->flow_entry_guid = $entry_guid;
        foreach ($this->children_nodes as $child) { $child->set_entry_guid($entry_guid);}
    }

    public function set_node_guid(?string $node_guid): void {
        Utilities::valid_guid_format_or_null_or_throw($node_guid);
        $this->node_guid = $node_guid;
        foreach ($this->children_nodes as $child) {
            $child->parent_guid = $node_guid;
        }
    }

    /**
     * @throws JsonException
     */
    public function __construct($object=null){
        parent::__construct();
        $this->pass_through_int = null;
        $this->node_id = null;
        $this->flow_entry_id = null;
        $this->node_created_at_ts = null;
        $this->node_updated_at_ts = null;
        $this->node_guid = null;
        $this->bb_tag_name = null;
        $this->node_words = null;
        $this->flow_entry_guid = null;
        $this->parent_guid = null;
        $this->parent_id = null;
        $this->parent = null;
        $this->child_position = null;
        $this->lot_number = null;
        $this->node_attributes = (object)[];
        $this->children_nodes = [];

        $this->flow_applied_tag_guid = null;
        $this->flow_applied_tag_id = null;
        $this->flow_applied_tag = null;

        $this->flow_tag = null;
        $this->flow_tag_id = null;
        $this->flow_tag_guid = null;

        if (empty($object)) {return null;}
        if (!is_object($object)) {
            $rem_parent = null;
            if (is_array($object) && array_key_exists('parent',$object) && $object['parent'] instanceof IFlowEntryNode) {
                $rem_parent = $object['parent'];
                unset($object['parent']);
            }
            $object = Utilities::convert_to_object($object);
            if ($rem_parent) {
                $object->parent = $rem_parent;
            }
        }

        foreach ($object as $key => $val) {
            if ($key === 'node_attributes') {
               if (!is_object($val)) {
                   $this->node_attributes = Utilities::convert_to_object($val);
                } else {
                   $this->node_attributes = $val;
               }
            }
            elseif ($key === 'parent') {
                if ($val instanceof IFlowEntryNode) {
                    $this->parent = $val;
                } else {
                    if (!empty($val)) {
                        $this->parent= new FlowEntryNode($val);
                    }
                }
            }
            elseif ($key === 'applied_tag') {
                if ($val instanceof IFlowAppliedTag) {
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
        if (empty($this->parent_guid) && $this->parent) {
            $this->parent_guid = $this->parent->node_guid;
        }


        if ($this->bb_tag_name=== static::FLOW_TAG_BB_CODE_NAME) {
            $flow_tag_guid_attribute = IFlowEntryNode::FLOW_TAG_BB_ATTR_GUID_NAME;
            $tag_guid = Utilities::valid_guid_format_or_null_or_throw($this->get_node_attributes()->$flow_tag_guid_attribute??null);
            if (!$tag_guid) {
                //allow for future hacks and fun
                preg_match('/\[flow_tag\s+tag=(?P<guid>[\da-fA-F]+)\s*]/',
                    $this->get_node_text()??'', $output_array);
                if ($output_array['guid']??null) {
                    $tag_guid = $output_array['guid'];
                    if (!trim($tag_guid)) {$tag_guid = null;}
                    $tag_guid = Utilities::valid_guid_format_or_null_or_throw($tag_guid);
                }
            }
            if ($tag_guid ) {
                $this->set_tag_guid($tag_guid);
            }
        }
        Utilities::valid_guid_format_or_null_or_throw($this->node_guid);
        if ($this->node_guid) {
            $this->node_attributes->guid = $this->node_guid;
        }
        if ($this->bb_tag_name === IFlowEntryNode::FLOW_TAG_BB_CODE_NAME) {
            if (property_exists($this->node_attributes,IFlowEntryNode::FLOW_TAG_BB_CODE_NAME)) {
                $bb_code_tag_property = IFlowEntryNode::FLOW_TAG_BB_CODE_NAME;
                unset($this->node_attributes->$bb_code_tag_property);
            }
        }

    }
    
    public function to_array(bool $b_children=true) : array  {

        $children_nodes = [];
        if ($b_children) {
            foreach ($this->children_nodes as $child) {
                $children_nodes[] = $child->to_array($b_children);
            }
        }

        return [
            'flow_entry_guid' => $this->flow_entry_guid,
            'node_guid' => $this->node_guid,
            'parent_guid' => $this->parent_guid,
            'child_position' => $this->child_position,
            'flow_tag_guid' => $this->flow_tag_guid,
            'flow_applied_tag_guid' => $this->flow_applied_tag_guid,
            'bb_tag_name' => $this->bb_tag_name,
            'node_attributes' => $this->node_attributes,
            'node_words' => $this->node_words,
            'node_created_at_ts' => $this->node_created_at_ts,
            'children_nodes' => $children_nodes,

        ];
    }

    public function jsonSerialize(): array
    {
        return $this->to_array();
    }

    public function set_tag_guid(?string $guid) :void {
        Utilities::valid_guid_format_or_null_or_throw($guid);
        if (!$this->flow_applied_tag || ($this->flow_tag_guid !== $guid)) {
            $this->flow_applied_tag = new FlowAppliedTag();
        }
        $this->flow_tag_guid = $guid;
        $this->flow_applied_tag->setParentTagGuid($guid);
        $this->flow_applied_tag->setParentTagId($this->flow_tag_id);
        $this->flow_applied_tag->setXNodeGuid($this->node_guid);
        $this->flow_applied_tag->setXNodeId($this->node_id);
        $this->flow_applied_tag->setGuid($this->flow_applied_tag_guid);
        $this->flow_applied_tag->setID($this->flow_applied_tag_id);
    }

    public function set_parent(?IFlowEntryNode $parent): void {

        if ($this->parent) {
            throw new LogicException("Setting parent without clearing the previously set one");
        }
        $this->parent = $parent;
        $this->parent_guid = $parent?->get_node_guid();
        $this->parent?->add_child($this);
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
                $this->flow_entry_id = Utilities::if_empty_null($this->flow_entry_id);
            }

            if (!$this->parent_id && $this->parent_guid) {
                $this->parent_id = $db->cell(
                    "SELECT id  FROM flow_entry_nodes WHERE entry_node_guid = UNHEX(?)",
                    $this->parent_guid);
                $this->parent_id = Utilities::if_empty_null($this->parent_id);
            }

            if ($this->node_guid) {
                $this->node_attributes->guid = $this->node_guid;
            }
            $entry_node_attributes_as_json = JsonHelper::toString($this->node_attributes);

            $save_info = [
                'flow_entry_id' => $this->flow_entry_id,
                'flow_entry_node_parent_id' => $this->parent_id,
                'child_position' => $this->child_position,
                'lot_number' => $this->lot_number,
                'bb_tag_name' => $this->bb_tag_name,
                'entry_node_words' => $this->node_words,
                'entry_node_attributes' => $entry_node_attributes_as_json,
            ];

            if ($this->node_guid && $this->node_id) {

                $db->update('flow_entry_nodes',$save_info,[
                    'id' => $this->node_id
                ]);

            }
            elseif ($this->node_guid) {
                $insert_sql = "
                    INSERT INTO flow_entry_nodes
                        (flow_entry_id,flow_entry_node_parent_id,child_position,lot_number, bb_tag_name,
                         entry_node_words, entry_node_guid, entry_node_attributes)  
                    VALUES (?,?,?,?,?,?,UNHEX(?),?)
                    ON DUPLICATE KEY UPDATE flow_entry_id =   VALUES(flow_entry_id),
                                            flow_entry_node_parent_id =     VALUES(flow_entry_node_parent_id),
                                            child_position =     VALUES(child_position),
                                            lot_number =     VALUES(lot_number),
                                            bb_tag_name =     VALUES(bb_tag_name)   ,    
                                            entry_node_words =     VALUES(entry_node_words)   ,    
                                            entry_node_attributes =     VALUES(entry_node_attributes)     
                ";
                $insert_params = [
                    $this->flow_entry_id,
                    $this->parent_id,
                    $this->child_position,
                    $this->lot_number,
                    $this->bb_tag_name,
                    $this->node_words,
                    $this->node_guid,
                    $entry_node_attributes_as_json
                ];
                $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
                $maybe_new_id = (int)$db->lastInsertId();
                if ($maybe_new_id && ($maybe_new_id !== $this->node_id)) {
                    $this->node_id = $maybe_new_id;
                } else {
                    if (!$this->node_id) {
                        //find id via guid
                        $find_id_sql = "SELECT e.id as node_id FROM flow_entry_nodes e WHERE entry_node_guid = UNHEX(?)";
                        $id_info = $db->safeQuery($find_id_sql,[$this->node_guid],PDO::FETCH_OBJ);
                        if (empty($id_info)) {
                            throw new RuntimeException("Could not get node id from guid of " . $this->node_guid);
                        }
                        $this->node_id = (int)$id_info[0]->node_id??null;
                        if (empty($this->node_id)) {
                            throw new RuntimeException("Could not get node id (b) from guid of " . $this->node_guid);
                        }
                    }
                }
            }
            else {
                $db->insert('flow_entry_nodes',$save_info);
                $this->node_id = $db->lastInsertId();
            }

            //get timestamps
            $timestamps = $db->safeQuery("SELECT 
                                                            UNIX_TIMESTAMP(n.entry_node_created_at) as entry_node_created_at_ts,
                                                            UNIX_TIMESTAMP(n.entry_node_updated_at) as entry_node_updated_at_ts        
                                                    FROM flow_entry_nodes n WHERE id = ?",
                [$this->node_id],PDO::FETCH_OBJ);
            if (empty($timestamps)) {
                throw new RuntimeException("Could not get timestamps from node using id of ".$this->node_id);
            }
            $this->node_created_at_ts = $timestamps[0]->entry_node_created_at_ts;
            $this->node_updated_at_ts = $timestamps[0]->entry_node_updated_at_ts;


            if (!$this->node_guid) {
                $this->node_guid = $db->cell(
                    "SELECT HEX(entry_node_guid) as entry_node_guid FROM flow_entry_nodes WHERE id = ?",
                    $this->node_id);

                if (!$this->node_guid) {
                    throw new RuntimeException("Could not get entry node guid using id of ". $this->node_id);
                }

            }
            $this->node_attributes->guid = $this->node_guid;


            $this->set_up_applied_tag($db);

            if ($b_save_children) {
                foreach ($this->children_nodes as $child) {
                    $child->parent_id = $this->node_id;
                    $child->parent_guid = $this->node_guid;
                    $child->flow_entry_id = $this->flow_entry_id;
                    $child->flow_entry_guid = $this->flow_entry_guid;

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

    /**
     * @param $db
     * @return void
     * @throws Exception
     */
    protected function set_up_applied_tag($db): void
    {
        if ($this->flow_tag_guid) {

            //see if already a connection made here

            if (!$this->flow_tag_id ) {
                $this->flow_tag_id = $db->cell(
                    "SELECT id  FROM flow_tags WHERE flow_tag_guid = UNHEX(?)",
                    $this->flow_tag_guid);

                $this->flow_tag_id = Utilities::if_empty_null($this->flow_tag_id);
            }

            if (!$this->flow_applied_tag_guid) {
                $this->flow_applied_tag_guid = $db->cell(
                    "SELECT HEX(flow_applied_tag_guid)  
                                    FROM flow_applied_tags 
                                    WHERE flow_tag_id = ? AND tagged_flow_entry_node_id = ? ",
                    $this->flow_tag_id,$this->node_id);

                $this->flow_applied_tag_guid = Utilities::if_empty_null($this->flow_applied_tag_guid);
            }

            $this->set_tag_guid($this->flow_tag_guid);

            if (!$this->flow_applied_tag->getGuid()) {
                $this->flow_applied_tag->save();
            }

            $this->flow_applied_tag_guid = $this->flow_applied_tag->getGuid();
            $this->flow_applied_tag_id = $this->flow_applied_tag->getID();
        }
    }


    public function get_as_bb_code() :string {
        $bb_code = $this->_get_as_bb_code();
        $unsafe = BBHelper::undo_safe_parse_for_bb_code($bb_code);
        return $unsafe;
    }

    protected function _get_as_bb_code() :string
    {

        if (!$this->bb_tag_name) {throw new RuntimeException("[get_as_bb_code] no bb code name");}
        if ($this->bb_tag_name === IFlowEntryNode::TEXT_BB_CODE_NAME) { //only text will have words, but check in case a regular node has it
            return $this->node_words??'';
        }
        if ($this->bb_tag_name === IFlowEntryNode::DOCUMENT_BB_CODE_NAME ) {
            $str = '';
        } else {
            $str = "[".$this->bb_tag_name;
        }

        if ($this->bb_tag_name !== IFlowEntryNode::DOCUMENT_BB_CODE_NAME && !empty($this->node_attributes)) {
            $attributes = $this->node_attributes;
            if (property_exists($attributes,$this->bb_tag_name)) {
                $bb_tag_name = $this->bb_tag_name;
                $str .= "=".$attributes->$bb_tag_name;
            }

            foreach ($attributes as $key => $value) {
                if ($key == $this->bb_tag_name) {
                    continue;
                } else {
                    $str .= " ".$key."=" . $value;
                }
            }
        }
        if ($this->bb_tag_name !== IFlowEntryNode::DOCUMENT_BB_CODE_NAME ) {
            $str .= "]";
        }

        foreach ($this->children_nodes as $child) {
            $str .= $child->_get_as_bb_code();
        }
        if (!in_array(strtolower($this->bb_tag_name), array_map('strtolower', IFlowEntryNode::NON_CLOSING_BB_TAGS)) ) {
            $str .= "[/".$this->bb_tag_name."]";
        }


        return $str;
    }

    /**
     * @param bool $b_do_transaction
     * @param array<string,IFlowEntryNode> $filter
     * @param string|null $logic
     * @return void
     * @throws Exception
     */
    protected function _prune_node(bool $b_do_transaction, array $filter, ?string $logic):void {
        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) {
                $db->beginTransaction();
            }
            foreach ($this->children_nodes as $child) {
                $child->_prune_node(false,$filter,$logic);
            }
            $b_delete_me = false;
            if (empty($filter)) { $b_delete_me = true;}
            else {
                switch($logic) {
                    case IFlowEntryNode::PRUNE_DELETE_ONLY_THESE: {
                        if (array_key_exists($this->get_node_guid(),$filter)) {$b_delete_me = true;}
                        break;
                    }
                    case IFlowEntryNode::PRUNE_KEEP_THESE: {
                        if (!array_key_exists($this->get_node_guid(),$filter)) {$b_delete_me = true;}
                        break;
                    }
                    default: {
                        throw new LogicException(sprintf("[prune_node] wrong logic used: %s",$logic));
                    }
                }
            }


            if ($b_delete_me) {
                if (!$this->get_node_guid()) {
                    throw new LogicException("Cannot prune node as it has no guid set");
                }
                $sql_for_delete = "DELETE FROM flow_entry_nodes WHERE entry_node_guid = UNHEX(?)";
                $num_affected = $db->safeQuery($sql_for_delete,[$this->get_node_guid()],PDO::FETCH_OBJ,true);
                if (!$num_affected) {
                    throw new LogicException(sprintf("Could not delete node guid %s",$this->get_node_guid()));
                }
            }

            if ($b_do_transaction && $db->inTransaction()) {$db->commit(); }
        } catch (Exception $e) {
            if ($b_do_transaction ) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
            static::get_logger()->alert("[internal_prune_node] cannot delete ",['exception'=>$e->getMessage()]);
            throw $e;
        }
    }

    /**
     *
     * @param bool $b_do_transaction
     * @param IFlowEntryNode|null $filter
     * @param string|null $logic
     * @return void
     * @throws Exception
     */
    public function prune_node(bool $b_do_transaction,?IFlowEntryNode $filter = null, ?string $logic = null):void {
       $map = [];
       if ($filter) {$map = $filter->get_guid_map();}
       $this->_prune_node($b_do_transaction,$map,$logic);

    }

    /**
     * @param array<string,IFlowEntryNode> $guid_map out ref $guid_map
     * @return void
     */
    protected function _map_guids(array &$guid_map) : void {
        if (!$this->get_node_guid()) {throw new LogicException("Calling node guid map before guid created");}
        if (array_key_exists($this->get_node_guid(),$guid_map)) {
            throw new LogicException(sprintf("Duplicate guid in  node guid map: %s",$this->get_node_guid()));
        }
        $guid_map[$this->get_node_guid()] = $this;
        foreach ($this->children_nodes as $child) {
            $child->_map_guids($guid_map);
        }
    }
    /**
     * gets a map of all the guids used with the guid as the key, and the id as the value
     * @return array<string,IFlowEntryNode>
     */
    public function get_guid_map(): array {
        $guid_map = [];
        $this->_map_guids($guid_map);
        return $guid_map;
    }

    /**
     * @param bool $erase_guid_and_parent_guid
     * @return IFlowEntryNode
     * @throws JsonException
     */
    public function copy(bool $erase_guid_and_parent_guid = false ) : IFlowEntryNode {

        $me_info = $this->to_array(false);
        if ($erase_guid_and_parent_guid) {

            unset($me_info['node_guid']);
            unset($me_info['parent_guid']);
        }
        $me = new FlowEntryNode($me_info);
        foreach ($this->children_nodes as $child) {
            $me->add_child($child->copy($erase_guid_and_parent_guid));
        }
        return $me;
    }


    /**
     * Part of the save process on a new tree that has text nodes which have no guids (text nodes do not save meta during edits)
     * Walks through the tree, and finds matching elements that already have a guid and has been found in the older version)
     *  for each element, find any text nodes right under it, and compare for likeness
     *  first go in same order of the slots, and find exact matches, and give the guid,
     *  then for any left over, find out of order direct matches and give the guid,
     *  finally, for any left over in the older ones, take each and compare to the left over new ones, and for any that have words of 50% match,
     *  assign them in order of greatest matching
     * @param IFlowEntryNode $older
     * @return void
     */
    public function match_text_nodes_with_existing(IFlowEntryNode $older) : void {
        $older_map = $older->get_guid_map();
        foreach ($older_map as $key => $val) {
            WillFunctions::will_do_nothing($key);
            $val->set_pass_through_int(0);
        }
        $this->_match_text_nodes_with_existing($older_map);
    }

    /**
     * @param array<string,IFlowEntryNode> $older_map
     * @return void
     */
    protected function _match_text_nodes_with_existing(array &$older_map) : void {
        /**
         * @var IFlowEntryNode[] $children_of_text
         */
        $children_of_text = [];

        foreach ($this->children_nodes as $child) {
            if ($child->is_text_node()) {
                $child->set_pass_through_float(0);
                $children_of_text[] = $child;
            }
            else {
                $child->_match_text_nodes_with_existing($older_map);
            }
        }
        unset($child);
        if (!array_key_exists($this->get_node_guid()??'no-guid',$older_map)) {
            return;
        }
        $older_match = $older_map[$this->get_node_guid()];
        if (empty($older_match->get_children())) {return;} //nothing to compare

        /**
         * @var array<string,IFlowEntryNode> $guid_words
         */
        $guid_words = [];
        foreach ($older_match->get_children() as $older_child) {
            if (!$older_child->is_text_node()) {continue;}
            if ($older_child->get_pass_through_int()) {continue;}
            if (empty($older_child->get_node_guid())) {continue;}
            $older_child->set_pass_through_float(0);
            $guid_words[$older_child->get_node_guid()] = $older_child;
        }
        unset($older_child);

        //now have zero or more strings of older words, pick the best for each child


        foreach ($children_of_text as $text_me) {
            $loop_index = 0;
            foreach ($guid_words as $guid => $other_text_node) {
                if ($other_text_node->get_pass_through_int() === 1) { continue; }
                if ($other_text_node->get_child_position() === $text_me->get_child_position()) {
                    $text_me->set_node_guid($other_text_node->get_node_guid());
                    $other_text_node->set_pass_through_int(1); //match found so mark it
                    unset($guid_words[$guid]);
                    unset($children_of_text[$loop_index]);
                    break;
                }
                $loop_index++;
            }
        }
        unset($other_text_node); unset($guid); unset($loop_index);
        $children_of_text = array_values($children_of_text);

        if (empty($children_of_text) || empty($guid_words) ) {return;} //either we have no more children or nothing else to get

        //all the same slots were now assigned that guid
        for( $i = 0; $i < count($children_of_text) ; $i++) {
            $text_me = $children_of_text[$i];
            /**
             * @var array<string,string> $words
             */
            $words = [];
            foreach ($guid_words as $guid => $other_text_node) {
                if ($other_text_node->get_pass_through_int()) {continue;}
                $words[$guid] = $other_text_node->get_node_text()??'';
            }
            unset($guid); unset($other_text_node);
            if (empty($words)) {break;}
            $closest_guid = Utilities::get_utilities()->return_most_matching_key($text_me->get_node_text()??'',$words);
            $guid_words[$closest_guid]->set_pass_through_int(1); //assigned!!
            $text_me->set_node_guid($closest_guid);

        }
        //all matches done

    }

}