<?php

namespace app\models\entry\entry_node;

use app\helpers\Utilities;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use Exception;
use JsonSerializable;
use LogicException;
use PDO;
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

    protected ?int $flow_applied_tag_id;
    protected ?string $flow_applied_tag_guid;
    protected ?FlowAppliedTag $flow_applied_tag = null;

    protected ?FlowTag $flow_tag = null;
    protected ?string $flow_tag_guid = null;
    protected ?int $flow_tag_id = null;

    protected ?int $pass_through_value;

    public function get_pass_through_value(): ?int {return $this->pass_through_value;}


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

    public function get_flow_entry_id() : ?int {return $this->flow_entry_id ;}
    public function get_flow_entry_guid() : ?string {return $this->flow_entry_guid ;}


    public function get_flow_tag_id() : ?int {return $this->flow_tag_id ;}
    public function get_flow_tag_guid() : ?string {return $this->flow_tag_guid ;}
    public function get_tag() : ?FlowTag {return $this->flow_tag ;}

    public function get_applied_flow_tag_id() : ?int {return $this->flow_applied_tag_id ;}
    public function get_applied_flow_tag_guid() : ?string {return $this->flow_applied_tag_guid ;}
    public function get_applied() : ?FlowAppliedTag {return $this->flow_applied_tag ;}

    public function add_child(IFlowEntryNode $node): void {

        //see if already child here
        if ($node->get_node_guid()) {
            foreach ($this->children_nodes as $child) {
                if ($child->get_node_guid() === $node->get_node_guid()) {
                    return ; //already added, not an error to add it again
                }
            }
        }
        preg_match('/\[flow_tag\s+tag=(?P<guid>[\da-fA-F]+)\s*]/',
            $node->get_node_text()??'', $output_array);
        if ($output_array['guid']??null) {
            $tag_guid = $output_array['guid'];
            $this->set_tag_guid($tag_guid);
        }
        $this->children_nodes[] = $node;
    }

    public function set_entry_id(int $entry_id): void {
        $this->flow_entry_id = $entry_id;
    }

    public function set_pass_through_value(int $pass_through): void {
        $this->pass_through_value = $pass_through;
    }

    public function set_entry_guid(?string $entry_guid): void {
        $this->flow_entry_guid = $entry_guid;
    }

    public function __construct($object=null){
        $this->pass_through_value = null;
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
        if (empty($this->parent_guid) && $this->parent) {
            $this->parent_guid = $this->parent->node_guid;
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
            'node_guid' => $this->node_guid,
            'parent_guid' => $this->parent_guid,
            'flow_tag_guid' => $this->flow_tag_guid,
            'flow_applied_tag_guid' => $this->flow_applied_tag_guid,
            'bb_tag_name' => $this->bb_tag_name,
            'node_attributes' => $this->node_attributes,
            'node_words' => $this->node_words,
            'node_created_at_ts' => $this->node_created_at_ts,
            'node_updated_at_ts' => $this->node_updated_at_ts,
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

    public function set_parent(?IFlowEntryNode $parent): void {

        if ($this->parent_guid && ($this->parent_guid !== $parent?->get_node_guid())) {
            throw new LogicException("Passed in parent does not have same guid as parent guid already set");
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
            }

            if (!$this->parent_id && $this->parent_guid) {
                $this->parent_id = $db->cell(
                    "SELECT id  FROM flow_entry_nodes WHERE entry_node_guid = UNHEX(?)",
                    $this->parent_guid);
            }

            $entry_node_attributes_as_json = JsonHelper::toString($this->node_attributes);
            $save_info = [
                'flow_entry_id' => $this->flow_entry_id,
                'flow_entry_node_parent_id' => $this->parent_id,
                'bb_tag_name' => $this->bb_tag_name,
                'entry_node_words' => $this->node_words,
                'entry_node_attributes' => $entry_node_attributes_as_json,
            ];
            //todo new child nodes should not have id or guid yet ?
            if ($this->node_guid && $this->node_id) {

                $db->update('flow_entry_nodes',$save_info,[
                    'id' => $this->node_id
                ]);

            }
            elseif ($this->node_guid) {
                $insert_sql = "
                    INSERT INTO flow_entry_nodes
                        (flow_entry_id,flow_entry_node_parent_id, bb_tag_name, entry_node_words, entry_node_guid, entry_node_attributes)  
                    VALUES (?,?,?,?,UNHEX(?),?)
                    ON DUPLICATE KEY UPDATE flow_entry_id =   VALUES(flow_entry_id),
                                            flow_entry_node_parent_id =     VALUES(flow_entry_node_parent_id),
                                            bb_tag_name =     VALUES(bb_tag_name)   ,    
                                            entry_node_words =     VALUES(entry_node_words)   ,    
                                            entry_node_attributes =     VALUES(entry_node_attributes)     
                ";
                $insert_params = [
                    $this->flow_entry_id,
                    $this->parent_id,
                    $this->bb_tag_name,
                    $this->node_words,
                    $entry_node_attributes_as_json
                ];
                $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
                $this->node_id = $db->lastInsertId();
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

            if ($this->flow_tag_id) {
                //instantiate applied tag object and save it
                if (!$this->flow_applied_tag) {
                    $this->flow_applied_tag = new FlowAppliedTag();
                }
                $this->flow_applied_tag->flow_tag_id = $this->flow_tag_id;
                $this->flow_applied_tag->tagged_flow_entry_node_id = $this->node_id;
                $this->flow_applied_tag->flow_applied_tag_guid = $this->flow_applied_tag_guid;
                $this->flow_applied_tag->id = $this->flow_applied_tag_id;
                $this->flow_applied_tag->save();
            }

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


    public function get_as_bb_code() :string
    {

        if (!$this->bb_tag_name) {throw new RuntimeException("[]get_as_bb_code no bb code");}
        if ($this->node_words) {
            return $this->node_words;
        }
        $str = "[".$this->bb_tag_name;
        if (!empty($this->node_attributes)) {
            if (isset($this->node_attributes[$this->bb_tag_name])) {
                $str .= "=".$this->attribute[$this->tagName];
            }

            foreach ($this->node_attributes as $key => $value) {
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

    /**
     * @param bool $b_do_transaction
     * @return void
     * @throws Exception
     */
    public function delete_node(bool $b_do_transaction = false):void {
        $db = static::get_connection();
        try {

            if ($b_do_transaction && !$db->inTransaction()) {
                $db->beginTransaction();
            }
            $db->delete('flow_entry_nodes',['id'=>$this->get_node_id()]);

            if ($b_do_transaction && $db->inTransaction()) {$db->commit(); }
        } catch (Exception $e) {
            if ($b_do_transaction ) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
            static::get_logger()->alert("Entry Node model cannot save ",['exception'=>$e->getMessage()]);
            throw $e;
        }
    }

}