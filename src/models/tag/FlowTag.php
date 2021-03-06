<?php

namespace app\models\tag;


use app\helpers\Utilities;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\project\IFlowProject;
use app\models\standard\FlowTagStandardAttribute;
use app\models\standard\IFlowTagStandardAttribute;
use app\models\tag\brief\BriefFlowTag;
use Error;
use Exception;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;
use stdClass;


class FlowTag extends FlowBase implements JsonSerializable, IFlowTag
{


    const LENGTH_TAG_NAME = 40;

    protected ?int $flow_tag_id; //done
    protected ?int $flow_project_id; //done

    protected ?IFlowProject $flow_project; //done

    protected ?int $parent_tag_id;  //done
    protected ?int $tag_created_at_ts; //done
    protected ?int $tag_updated_at_ts; //done
    protected ?string $flow_tag_guid; //done
    protected ?string $flow_tag_name; //done


    protected ?string $flow_project_guid; //done
    protected ?string $flow_project_admin_user_guid;  //done
    protected ?string $parent_tag_guid; //done

    /**
     * @var IFlowTagAttribute[] $attributes
     */
    protected array $attributes; //done

    /**
     * @var FlowTag|null $flow_tag_parent
     */
    protected ?FlowTag $flow_tag_parent; //done

    /**
     * @var int[]
     */
    protected array $children_list; //done

    protected ?string $children_list_as_string; //done

    /**
     * @var IFlowAppliedTag[] $applied
     */
    protected array $applied = []; //done


    /**
     * @var IFlowTagAttribute[] $attributes
     */
    protected array $inherited_attributes = []; //done

    /**
     * @var IFlowTagStandardAttribute[] $standard_attributes
     */
    protected array $standard_attributes = []; //done

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->flow_tag_id;
    }

    /**
     * @param int|null $flow_tag_id
     */
    public function setId(?int $flow_tag_id): void
    {
        $this->flow_tag_id = $flow_tag_id;
    }

    /**
     * @return int|null
     */
    public function getProjectId(): ?int
    {
        return $this->flow_project_id;
    }

    /**
     * @param int|null $flow_project_id
     */
    public function setProjectId(?int $flow_project_id): void
    {
        $this->flow_project_id = $flow_project_id;
    }

    /**
     * @return IFlowProject|null
     */
    public function getProject(): ?IFlowProject
    {
        return $this->flow_project;
    }

    /**
     * @param IFlowProject|null $flow_project
     */
    public function setProject(?IFlowProject $flow_project): void
    {
        $this->flow_project = $flow_project;
    }

    /**
     * @return int|null
     */
    public function getParentId(): ?int
    {
        return $this->parent_tag_id;
    }

    /**
     * @param int|null $parent_tag_id
     */
    public function setParentId(?int $parent_tag_id): void
    {
        $this->parent_tag_id = $parent_tag_id;
    }

    /**
     * @return int|null
     */
    public function getCreatedAtTs(): ?int
    {
        return $this->tag_created_at_ts;
    }

    /**
     * @param int|null $tag_created_at_ts
     */
    public function setCreatedAtTs(?int $tag_created_at_ts): void
    {
        $this->tag_created_at_ts = $tag_created_at_ts;
    }

    /**
     * @return int|null
     */
    public function getUpdatedAtTs(): ?int
    {
        return $this->tag_updated_at_ts;
    }

    /**
     * @param int|null $tag_updated_at_ts
     */
    public function setUpdatedAtTs(?int $tag_updated_at_ts): void
    {
        $this->tag_updated_at_ts = $tag_updated_at_ts;
    }

    /**
     * @return string|null
     */
    public function getGuid(): ?string
    {
        return $this->flow_tag_guid;
    }

    /**
     * @param string|null $flow_tag_guid
     */
    public function setGuid(?string $flow_tag_guid): void
    {
        Utilities::valid_guid_format_or_null_or_throw($flow_tag_guid);
        $this->flow_tag_guid = $flow_tag_guid;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->flow_tag_name;
    }

    /**
     * @param string|null $flow_tag_name
     */
    public function setName(?string $flow_tag_name): void
    {
        $this->flow_tag_name = $flow_tag_name;
    }

    /**
     * @return string|null
     */
    public function getProjectGuid(): ?string
    {
        return $this->flow_project_guid;
    }

    /**
     * @param string|null $flow_project_guid
     */
    public function setProjectGuid(?string $flow_project_guid): void
    {
        Utilities::valid_guid_format_or_null_or_throw($flow_project_guid);
        $this->flow_project_guid = $flow_project_guid;
    }

    /**
     * @return string|null
     */
    public function getAdminGuid(): ?string
    {
        return $this->flow_project_admin_user_guid;
    }

    /**
     * @param string|null $flow_project_admin_user_guid
     */
    public function setAdminGuid(?string $flow_project_admin_user_guid): void
    {
        Utilities::valid_guid_format_or_null_or_throw($flow_project_admin_user_guid);
        $this->flow_project_admin_user_guid = $flow_project_admin_user_guid;
    }

    /**
     * @return string|null
     */
    public function getParentGuid(): ?string
    {
        return $this->parent_tag_guid;
    }

    /**
     * @param string|null $parent_tag_guid
     */
    public function setParentGuid(?string $parent_tag_guid): void
    {
        Utilities::valid_guid_format_or_null_or_throw($parent_tag_guid);
        $this->parent_tag_guid = $parent_tag_guid;
    }

    /**
     * @return IFlowTagAttribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param IFlowTagAttribute[] $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = [];
        $this->addAttributes($attributes);
    }

    public function addAttribute(IFlowTagAttribute $a) : IFlowTag {
        foreach ($this->attributes as $att) {
            if (($att->getName() === $a->getName()) ||
                ($att->getGuid() === $a->getGuid())
            ) {
                return $this;
            }
        }
        $this->attributes[] = $a;
        return $this;
    }

    /** @param IFlowTagAttribute[] $a */
    public function addAttributes(array $a) : IFlowTag {
        foreach ($a as $att) {
            $this->addAttribute($att);
        }
        return $this;
    }

    /**
     * @return IFlowTag
     */
    public function getParent(): IFlowTag
    {
        return $this->flow_tag_parent;
    }

    /**
     * @param IFlowTag|null $flow_tag_parent
     */
    public function setParent(?IFlowTag $flow_tag_parent): void
    {
        $this->flow_tag_parent = $flow_tag_parent;
    }

    /**
     * @return int[]
     */
    public function getChildrenList(): array
    {
        return $this->children_list;
    }


    /**
     * @return IFlowAppliedTag[]
     */
    public function getApplied(): array
    {
        return $this->applied;
    }

    /**
     * @param IFlowAppliedTag[] $applied
     */
    public function setApplied(array $applied): void
    {
        $this->applied = $applied;
    }

    /**
     * @return IFlowTagAttribute[]
     */
    public function getInheritedAttributes(): array
    {
        return $this->inherited_attributes;
    }

    /**
     * @param IFlowTagAttribute[] $inherited_attributes
     */
    public function setInheritedAttributes(array $inherited_attributes): void
    {
        $this->inherited_attributes = $inherited_attributes;
    }




    /**
     * @return IFlowTagStandardAttribute[]
     */
    public function getStandardAttributes() : array  {
        return $this->standard_attributes;
    }

    public function hasStandardAttribute(string $name) : ?IFlowTagStandardAttribute  {
         foreach ($this->standard_attributes as $att) {
             if ($att->getStandardName()=== $name) {return $att;}
         }
         return null;
    }

    /**
     * @param IFlowTagStandardAttribute[] $what
     * @return void
     */
    public function setStandardAttributes(array $what) : void  {

        $processed = [];
        foreach ($what as $s) {
            $s->preProcessForGui();
            $processed[] = $s;
        }
        $this->standard_attributes = $processed;
    }

    public function get_or_create_attribute(string $attribute_name): IFlowTagAttribute {
        foreach ($this->attributes as $existing_attribute) {
            if ( $attribute_name === $existing_attribute->getName() ) {
                return $existing_attribute;
            }
        }

        $att = new FlowTagAttribute();
        $att->setName($attribute_name);
        $att->setTagId($this->flow_tag_id);
        $this->attributes[] = $att;
        return $att;
    }

    public function set_standard_by_raw(string $standard_name, object $standard_value) {
        $keys = FlowTagStandardAttribute::getStandardAttributeKeys($standard_name,false);
        $white_list = [];
        foreach ($keys as $key_name) {
            if (property_exists($standard_value,$key_name)) {
                $white_list[$key_name] = $standard_value->$key_name;
            } else {
                if (FlowTagStandardAttribute::does_key_have_truthful_attribute(
                            $standard_name,$key_name,IFlowTagStandardAttribute::OPTION_REQUIRED)
                ) {
                    throw new InvalidArgumentException("Missing required key $key_name for standard $standard_name");
                }
            }
        }


        foreach ($this->attributes as $existing_attribute) {
            if (isset($white_list[$existing_attribute->getName()])) {
                $existing_attribute->setText($white_list[$existing_attribute->getName()]);
                unset($white_list[$existing_attribute->getName()]);
            }

        }

        foreach ($white_list as $attribute_key_to_add => $attribute_text_val_to_add) {
            $att = new FlowTagAttribute();
            $att->setName($attribute_key_to_add);
            $att->setText($attribute_text_val_to_add);
            $att->setTagId($this->flow_tag_id);
            $this->attributes[] = $att;
        }
    }


    /**
     * @throws JsonException
     */
    public function jsonSerialize(): array
    {

        if ($this->get_brief_json_flag()) {

            $brief = new BriefFlowTag($this);
            return $brief->to_array();
        } else {
            $this->refresh_inherited_fields();

            $standard_attribute_map = [];
            foreach ($this->standard_attributes as $sa ) {
                $standard_attribute_map[$sa->getStandardName()] = $sa->getStandardValue();
            }

            return [
                "flow_tag_guid" => $this->flow_tag_guid,
                "parent_tag_guid" => $this->parent_tag_guid,
                "flow_project_guid" => $this->flow_project_guid,
                "flow_project_admin_user_guid" => $this->flow_project_admin_user_guid,
                "created_at_ts" => $this->tag_created_at_ts,
                "updated_at_ts" => $this->tag_updated_at_ts,
                "flow_tag_name" => $this->flow_tag_name,
                "attributes" => $this->inherited_attributes,
                "css" => $standard_attribute_map['css'] ?? (object)[],
                "standard_attributes" => $standard_attribute_map,
                "flow_tag_parent" => $this->flow_tag_parent,
                "applied" => $this->applied,
                'flow_project' => $this->flow_project
            ];
        }


    }



    /**
     * Gets the attribute list merged with the parent's attribute, which may be altered by its parent
     * @param FlowTag|null $tag
     * @return IFlowTagAttribute[]
     */
    public static function get_attribute_map(?FlowTag $tag) :array {
        $ret = [];
        if (empty($tag)) {return $ret;}

        $ret = static::get_attribute_map($tag->flow_tag_parent);


        foreach ($tag->attributes as $attribute) {
            if (array_key_exists($attribute->getName(),$ret)) {
                $new_attribute = FlowTagAttribute::merge_attribute($attribute,$ret[$attribute->getName()]);
            } else {
                $new_attribute = FlowTagAttribute::merge_attribute($attribute,null);
            }
            $ret[$attribute->getName()] = $new_attribute;
        }

        foreach ($ret as $attribute) {
            $attribute->setIsInherited($attribute->getTagGuid() !== $tag->flow_tag_guid);
        }
        return $ret;
    }


    /** @noinspection PhpConditionAlreadyCheckedInspection */ //due to editor bug
    /**
     * @throws JsonException
     */
    public function __construct($object=null){
        parent::__construct();
        $this->attributes = [];
        $this->standard_attributes = [];
        $this->applied = [];
        $this->children_list = [];
        $this->flow_tag_id = null ;
        $this->flow_project_id = null ;
        $this->parent_tag_id = null ;
        $this->tag_created_at_ts = null ;
        $this->tag_updated_at_ts = null ;
        $this->flow_tag_guid = null ;
        $this->flow_tag_name = null ;
        $this->flow_project_guid = null ;
        $this->flow_project_admin_user_guid = null ;
        $this->parent_tag_guid = null ;
        $this->flow_tag_parent = null;
        $this->children_list_as_string = null;
        $this->flow_project = null;

        if (empty($object)) {
            return;
        }

        if (!$object instanceof FlowTag && $object instanceof IFlowTagBasic) {

            $this->flow_tag_guid = $object->getGuid();
            $this->flow_tag_name = $object->getName();
            $this->tag_created_at_ts = $object->getCreatedAtTs();
            $this->tag_updated_at_ts = $object->getUpdatedAtTs();
            $this->parent_tag_guid = $object->getParentGuid();
            $this->flow_project_guid = $object->getProjectGuid();
        } else {
            foreach ($object as $key => $val) {
                if ($key === 'attributes') {continue;}
                if ($key === 'applied') {continue;}
                if ($key === 'standard_attributes') {continue; }

                if (property_exists($this,$key)) {
                    try {
                        $this->$key = $val;
                    } catch (Error $help) {
                        static::get_logger()->info("key $key",['message'=>$help->getMessage(),'value'=>$val]);
                        throw $help;
                    }

                }
            }
        }





        if ( ($object instanceof stdClass) || ($object instanceof IFlowTag) ) {

            if ($object instanceof IFlowTag) {
                $found_standard = $object->getStandardAttributes();
            } else {
                $found_standard = $object->standard_attributes??[];
            }


            if ( $found_standard instanceof IFlowTagStandardAttribute) {
                $this->standard_attributes[] = $found_standard;
            } elseif (is_array($found_standard)) {
                $this->standard_attributes = $found_standard;
            }
            elseif (is_object($found_standard)) {
                foreach ($found_standard as $standard_name => $standard_value) {
                    $args_for_standard = [
                        'standard_name' =>   $standard_name,
                        'standard_value' =>   $standard_value
                    ];
                    $param_node = new FlowTagStandardAttribute( Utilities::convert_to_object($args_for_standard));
                    $this->standard_attributes[] = $param_node;
                }
            } else {
                if (!empty($found_standard)) {
                    throw new InvalidArgumentException(
                        "[FlowTag Constructor] standard_attributes is not array, object or empty: "
                        . print_r($found_standard, true)
                    );
                }
            }
        }

        if ($object instanceof BriefFlowTag ) {
            $attributes_to_copy = $object->getAttributes();
        } else  if (is_object($object) && property_exists($object,'attributes') && is_array($object->attributes)) {
            $attributes_to_copy = $object->attributes;
        } elseif (is_array($object) && array_key_exists('attributes',$object) && is_array($object['attributes'])) {
            $attributes_to_copy  = $object['attributes'];
        } else {
            $attributes_to_copy = [];
        }
        $this->attributes = [];
        if (count($attributes_to_copy)) {
            foreach ($attributes_to_copy as $att) {
                $this->attributes[] = new FlowTagAttribute($att);
            }
        }



        if ($object instanceof BriefFlowTag ) {
            $applied_to_copy = $object->getApplied();
        } elseif (is_object($object) && property_exists($object,'applied') && is_array($object->applied)) {
            $applied_to_copy = $object->applied;
        } elseif (is_array($object) && array_key_exists('attributes',$object) && is_array($object['attributes'])) {
            $applied_to_copy  = $object['applied'];
        } else {
            $applied_to_copy = [];
        }
        $this->applied = [];
        if (count($applied_to_copy)) {
            foreach ($applied_to_copy as $app) {
                $this->applied[] = new FlowAppliedTag($app);
            }
        }

        $this->flow_tag_parent = null;
        if (is_object($object) && property_exists($object,'flow_tag_parent') && !empty($object->flow_tag_parent)) {
            $parent_to_copy = $object->flow_tag_parent;
        } elseif (is_array($object) && array_key_exists('flow_tag_parent',$object) && !empty($object['flow_tag_parent'])) {
            $parent_to_copy  = $object['flow_tag_parent'];
        } else {
            $parent_to_copy = null;
        }
        if ($parent_to_copy) {
            $this->flow_tag_parent = new FlowTag($parent_to_copy);
        }

        if ($this->children_list_as_string) {
            $this->children_list = array_map(function($x){return (int)$x;},explode(',',$this->children_list_as_string));
        }

        $this->refresh_inherited_fields();

    }

    public function refresh_inherited_fields() {
        $this->inherited_attributes = static::get_attribute_map($this);
    }

    /**
     * @param array<string,string> $guid_map_old_to_new
     * @param bool $b_do_transaction default false
     * @return IFlowTag
     * @throws Exception
     */
    public function clone_change_project(array $guid_map_old_to_new ,bool $b_do_transaction = false) : IFlowTag
    {
        $me = new FlowTag($this); //new to db
        $me->flow_tag_id = null;
        $me->flow_tag_guid = null;
        $me->flow_project_id = null;
        $me->flow_project_guid = $guid_map_old_to_new[$this->flow_project_guid]??null;
        if (!$me->flow_project_guid) {throw new InvalidArgumentException("[clone_change_project] Project guid was not supplied");}
        $me->setParentGuid($guid_map_old_to_new[$this->getParentGuid()]??null);
        $me->parent_tag_id = null;

        $me->save($b_do_transaction,true,$guid_map_old_to_new);
        return $me;
    }

    /**
     * @param bool $b_get_applied
     * @return IFlowTag
     * @throws Exception
     */
    public function clone_refresh(bool $b_get_applied=true) : IFlowTag
    {
        if (empty($this->flow_tag_id) && empty($this->flow_tag_guid)) {
            $me = new FlowTag($this); //new to db
            return $me;
        }
        $search = new FlowTagSearchParams();
        if ($this->flow_tag_guid) {
            $search->addGuidsOrNames($this->flow_tag_guid);
        } elseif ($this->flow_tag_id) {
            $search->tag_ids[] = $this->flow_tag_id;
        }
        $search->flag_get_applied = $b_get_applied;
        $tag_search = new FlowTagSearch();
        $me_array = $tag_search->get_tags($search)->get_found_tags();
        if (empty($me_array)) {
            throw new InvalidArgumentException("Tag is not found from guid of $this->flow_tag_guid or id of $this->flow_tag_id");
        }
        $me = $me_array[0];
        return $me;
    }

    /**
     * @return IFlowTag
     * @throws Exception
     */
    public function clone_with_missing_data() : IFlowTag
    {

        $me = $this->clone_refresh();
        if (empty($me->flow_tag_id) && empty($me->flow_tag_guid)) {
            return $me;
        }
        //clear out the settable ids in the $me, if not set in this
        //set new data for this, overwriting the old
        if ($me->getParentGuid() && !$this->parent_tag_guid) {
            $me->parent_tag_id = null;
            $me->parent_tag_guid = null;
        }

        if (!$this->parent_tag_id && $this->parent_tag_guid) {
            $me->parent_tag_id = null;
            $me->parent_tag_guid = $this->parent_tag_guid;
        }


        $me->flow_tag_name = $this->flow_tag_name;

        /**
         * @var array<string, IFlowTagAttribute> $this_attribute_map
         */
        $this_attribute_map = [];

        foreach ($this->attributes as $attribute) {
            if ($attribute->getGuid()) {
                $this_attribute_map[$attribute->getGuid()] = $attribute;
            }
        }

        $me_attributes_filtered = [];
        //for each attribute in the $me, that is not in the $this, delete it
        foreach ($me->attributes as  $me_attribute) {
            if (array_key_exists($me_attribute->getGuid(),$this_attribute_map)) {
                //clear out the settable ids in the $me::attribute, if not set in this::attribute
                //set new data for $me::attribute, overwriting the old

                /**
                 * @var IFlowTagAttribute $this_attribute
                 */
                $this_attribute = $this_attribute_map[$me_attribute->getGuid()];

                if ($me_attribute->getPointsToEntryId() && !$this_attribute->getPointsToFlowEntryGuid()) {
                    $me_attribute->setPointsToEntryId(null);
                }

                if ($me_attribute->getPointsToUserId() && !$this_attribute->getPointsToFlowUserGuid()) {
                    $me_attribute->setPointsToUserId(null);
                }

                if ($me_attribute->getPointsToProjectId() && !$this_attribute->getPointsToFlowProjectGuid()) {
                    $me_attribute->setPointsToProjectId(null);
                }

                if ($me_attribute->getPointsToTagId() && !$this_attribute->getPointsToFlowTagGuid()) {
                    $me_attribute->setPointsToTagId(null);
                }

                $me_attribute->setPointsToFlowEntryGuid(
                    empty($this_attribute->getPointsToFlowEntryGuid())
                        ? null:  $this_attribute->getPointsToFlowEntryGuid()
                ) ;
                $me_attribute->soints_to_flow_user_guid(
                    empty($this_attribute->getPointsToFlowUserGuid())?
                        null : $this_attribute->getPointsToFlowUserGuid()
                );
                $me_attribute->setPointsToFlowProjectGuid(
                    empty($this_attribute->getPointsToFlowProjectGuid())?
                        null : $this_attribute->getPointsToFlowProjectGuid() );

                $me_attribute->setPointsToFlowTagGuid(
                    empty($this_attribute->getPointsToFlowTagGuid())?
                        null : $this_attribute->getPointsToFlowTagGuid() );

                $me_attribute->setName($this_attribute->getName());

                if ( $this_attribute->getLong() !== '0' && empty($this_attribute->getLong())) {
                    $this_attribute->setLong(null) ;
                } else {
                    $me_attribute->setLong(intval($this_attribute->getLong()));
                }

                $me_attribute->setText(
                    empty($this_attribute->getText())?
                                                null :
                                                $this_attribute->getText()
                );

                $me_attributes_filtered[] = $me_attribute;
                unset($this_attribute_map[$me_attribute->getGuid()]);
            }
        }

        //add remaining new attributes that have guids
        foreach ($this_attribute_map as $this_attribute_guid => $this_attribute) {
            WillFunctions::will_do_nothing($this_attribute_guid);
            $me_attributes_filtered[] = $this_attribute;
        }

        //add in new attributes with no guid
        foreach ($this->attributes as $attribute) {
            if (!$attribute->getGuid()) {
                $me_attributes_filtered[] = $attribute;
            }
        }

        $me->attributes = $me_attributes_filtered;
        return $me;
    }



    public static function check_valid_name($words) : bool  {
        $b_min_ok =  static::minimum_check_valid_name($words,static::LENGTH_TAG_NAME);
        if (!$b_min_ok) {return false;}
        //no special punctuation
        if (preg_match('/[\'"<>`]/', $words, $output_array)) {
            WillFunctions::will_do_nothing($output_array);
            return false;
        }
        return true;
    }

    /**
     * @param string|null $attribute_name
     * @param IFlowTagAttribute|null $attribute
     * @param bool $b_do_transaction
     * @return IFlowTag
     * @throws Exception
     */
    public  function save_tag_return_clones(?string $attribute_name, IFlowTagAttribute &$attribute = null,
                                            bool $b_do_transaction=false): IFlowTag
    {
        $this->save($b_do_transaction,true);
        $altered_tag = $this->clone_refresh();

        if ($attribute_name) {
            $attribute = null;
            foreach ($altered_tag->attributes as $look_at) {
                if ($look_at->getName() === $attribute_name) {
                    $attribute = $look_at;
                    break;
                }
            }

            if (!$attribute) {
                throw new LogicException("Cannot find the attribute $attribute_name after saving it");
            }
        }

        return $altered_tag;
    }

    /**
     * @param bool $b_do_transaction
     * @param bool $b_save_children
     * @param array<string,string> $guid_map_old_to_new
     *      if not empty , then saves applied and attributes as new under the current project id
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false, bool $b_save_children = false,array $guid_map_old_to_new = []) :void {
        $db = null;

        try {
            if (empty($this->flow_tag_name)) {
                throw new InvalidArgumentException("Tag Title cannot be empty");
            }


            $b_match = static::check_valid_name($this->flow_tag_name);
            if (!$b_match) {
                $max_len = static::LENGTH_TAG_NAME;
                throw new InvalidArgumentException(
                    "Tag name either empty OR invalid! ".
                    "First character cannot be a number. Name Cannot be greater than $max_len. ".
                    " Title cannot be a hex number greater than 25 and cannot be a decimal number");
            }

            $this->flow_tag_name = Utilities::to_utf8($this->flow_tag_name);


            $db = static::get_connection();



            if (!$this->flow_project_id && $this->flow_project_guid) {
                $this->flow_project_id = $db->cell(
                    "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                    $this->flow_project_guid);
                $this->flow_project_id = Utilities::if_empty_null($this->flow_project_id);
            }

            if(  !$this->flow_project_id) {
                throw new InvalidArgumentException("When saving a tag for the first time, need its project id or guid");
            }

            if (!$this->parent_tag_id && $this->parent_tag_guid) {
                $this->parent_tag_id = $db->cell(
                    "SELECT id  FROM flow_tags WHERE flow_tag_guid = UNHEX(?)",
                    $this->parent_tag_guid);
                $this->parent_tag_id = Utilities::if_empty_null($this->parent_tag_id);
            }

            if (empty($this->parent_tag_id)) {$this->parent_tag_id = null;}


            $save_info = [
                'flow_project_id' => $this->flow_project_id,
                'parent_tag_id' => $this->parent_tag_id,
                'flow_tag_name' => $this->flow_tag_name
            ];


            if ($b_do_transaction && !$db->inTransaction()) {$db->beginTransaction();}
            if ($this->flow_tag_guid && $this->flow_tag_id) {

                $db->update('flow_tags',$save_info,[
                    'id' => $this->flow_tag_id
                ]);

            }
            elseif ($this->flow_tag_guid) {
                $insert_sql = "
                    INSERT INTO flow_tags(flow_project_id, parent_tag_id, created_at_ts, flow_tag_guid, flow_tag_name)  
                    VALUES (?,?,?,UNHEX(?),?)
                    ON DUPLICATE KEY UPDATE flow_project_id =   VALUES(flow_project_id),
                                            parent_tag_id =     VALUES(parent_tag_id),
                                            flow_tag_name =     VALUES(flow_tag_name)       
                ";
                $insert_params = [
                    $this->flow_project_id,
                    $this->parent_tag_id,
                    $this->tag_created_at_ts,
                    $this->flow_tag_guid,
                    $this->flow_tag_name
                ];
                $db->safeQuery($insert_sql, $insert_params, PDO::FETCH_BOTH, true);
                $this->flow_tag_id = $db->lastInsertId();
            }
            else {
                $db->insert('flow_tags',$save_info);
                $this->flow_tag_id = $db->lastInsertId();
            }

            if (!$this->flow_tag_guid) {
                $this->flow_tag_guid = $db->cell(
                    "SELECT HEX(flow_tag_guid) as flow_tag_guid FROM flow_tags WHERE id = ?",
                    $this->flow_tag_id);

                if (!$this->flow_tag_guid) {
                    throw new RuntimeException("Could not get tag guid using id of ". $this->flow_tag_id);
                }
            }

            if ($b_save_children) {
                foreach ($this->attributes as $attribute) {

                    $attribute->setTagId($this->flow_tag_id) ;
                    if (count($guid_map_old_to_new)) {
                        $attribute->setId(null) ;
                        $attribute->setGuid(null) ;

                        if (isset($guid_map_old_to_new[$attribute->getPointsToFlowProjectGuid()])) {
                            $attribute->setPointsToFlowProjectGuid($guid_map_old_to_new[$attribute->getPointsToFlowProjectGuid()]);
                            $attribute->setPointsToProjectId(null) ;
                        }

                        if (isset($guid_map_old_to_new[$attribute->getPointsToFlowTagGuid()])) {
                            $attribute->setPointsToFlowTagGuid($guid_map_old_to_new[$attribute->getPointsToFlowTagGuid()]);
                            $attribute->setPointsToTagId(null) ;
                        }

                        if (isset($guid_map_old_to_new[$attribute->getPointsToFlowEntryGuid()])) {
                            $attribute->setPointsToFlowEntryGuid( $guid_map_old_to_new[$attribute->getPointsToFlowEntryGuid()]);
                            $attribute->setPointsToEntryId(null);
                        }

                        if (isset($guid_map_old_to_new[$attribute->getPointsToFlowUserGuid()])) {
                            $attribute->setPointsToFlowUserGuid( $guid_map_old_to_new[$attribute->getPointsToFlowUserGuid()]);
                            $attribute->setPointsToUserId(null);
                        }

                    }
                    $attribute->save();
                }

                foreach ($this->applied as $app) {
                    $app->setParentTagId($this->flow_tag_id);
                    if (count($guid_map_old_to_new)) {
                        $app->setId(null);
                        $app->setGuid(null);
                        if (isset($guid_map_old_to_new[$app->getXProjectGuid()])) {
                            $app->setXProjectGuid($guid_map_old_to_new[$app->getXProjectGuid()]);
                            $app->setXProjectId(null);
                        }

                        if (isset($guid_map_old_to_new[$app->getXEntryGuid()])) {
                            $app->setXEntryGuid($guid_map_old_to_new[$app->getXEntryGuid()]);
                            $app->setXEntryGuid(null);
                        }

                        if (isset($guid_map_old_to_new[$app->getXUserGuid()])) {
                            $app->setXUserGuid($guid_map_old_to_new[$app->getXUserGuid()]);
                            $app->setTaggedFlowUserId(null);
                        }
                    }
                    $app->save();
                }
            }
            $this->update_standard_attibutes();
            if ($b_do_transaction && $db->inTransaction()) {$db->commit(); }


        } catch (Exception $e) {
            if ($b_do_transaction && $db) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
            static::get_logger()->alert("Tag model cannot save ",['exception'=>$e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Standard attributes can change some non tag db fields, for parent and children. Can also change some tag php members
     * @return int
     * @throws Exception
     */
    protected function update_standard_attibutes() : int {

        FlowTagStandardAttribute::write_standard_attributes([$this]);
        $resolved_attributes = FlowTagStandardAttribute::read_standard_attributes_of_tags([$this]);
        $count = 0;
        foreach (($resolved_attributes[$this->flow_tag_guid] ?? []) as $standard_attribute) {
            if (property_exists($this,$standard_attribute->getStandardName())) {
                $key = $standard_attribute->getStandardName();
                $this->$key = $standard_attribute->getStandardValue();
                $count++;
            }
        }

        return $count;
    }

    public function delete_tag() {
        if (count($this->children_list)) {
            throw new InvalidArgumentException("Cannot delete tag, it has children");
        }
        $db = static::get_connection();
        if ($this->flow_tag_id) {
            $db->delete('flow_tags',['id'=>$this->flow_tag_id]);
        } else if($this->flow_tag_guid) {
            $sql = "DELETE FROM flow_tags WHERE flow_tag_guid = UNHEX(?)";
            $params = [$this->flow_tag_guid];
            $db->safeQuery($sql, $params, PDO::FETCH_BOTH, true);
        } else {
            throw new LogicException("Cannot delete flow_tags without an id or guid");
        }

    }

    /**
     * @param string[] $guid_list
     * @return IFlowAppliedTag[]
     */
    public function find_applied_by_guid_of_tagged(array $guid_list) : array {
        $ret = [];
        foreach ($this->applied as $app) {
            if ($app->has_at_least_one_of_these_tagged_guid($guid_list)) {
                $ret[]= $app;
            }
        }
        return $ret;
    }

    /**
     * @return string[]
     */
    public function get_needed_guids_for_empty_ids() : array
    {
        $ret = [];
        if (empty($this->flow_project_id) && $this->flow_project_guid) { $ret[] = $this->flow_project_guid;}
        if (empty($this->parent_tag_id) && $this->parent_tag_guid) { $ret[] = $this->parent_tag_guid;}


        return $ret;
    }

    /**
     * @@param  array<string,int> $guid_map_to_ids
     */
    public function fill_ids_from_guids(array $guid_map_to_ids)
    {

        if (empty($this->flow_project_id) && $this->flow_project_guid) {
            $this->flow_project_id = $guid_map_to_ids[$this->flow_project_guid] ?? null;}
        if (empty($this->parent_tag_id) && $this->parent_tag_guid) {
            $this->parent_tag_id = $guid_map_to_ids[$this->parent_tag_guid] ?? null;}

    }

    public function delete_attribute_by_name(string $attribute_name) : ?IFlowTagAttribute {
        //find attribute and its index

        $attribute_index = null;

        /**
         * @var IFlowTagAttribute|null $found_attribute
         */
        $found_attribute = null;

        foreach ($this->attributes as $index_of => $attribute) {
            if ($attribute->getName() === $attribute_name) {
                $found_attribute = $attribute;
                $attribute_index = $index_of;
                break;
            }
        }

        if ($found_attribute && !is_null($attribute_index)) {
            $found_attribute->delete_attribute();
            array_splice($this->attributes, $attribute_index, 1);
        }

        return $found_attribute;
    }



}