<?php

namespace app\models\tag;


use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\tag\brief\BriefFlowTag;
use BlueM\Tree;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;


class FlowTag extends FlowBase implements JsonSerializable {

    const DEFAULT_TAG_PAGE_SIZE = 25;
    const LENGTH_TAG_NAME = 40;

    public ?int $flow_tag_id;
    public ?int $flow_project_id;
    public ?int $parent_tag_id;
    public ?int $tag_created_at_ts;
    public ?int $tag_updated_at_ts;
    public ?string $flow_tag_guid;
    public ?string $flow_tag_name;


    public ?string $flow_project_guid;
    public ?string $flow_project_admin_user_guid;
    public ?string $parent_tag_guid;

    /**
     * @var FlowTagAttribute[] $attributes
     */
    public array $attributes;

    /**
     * @var FlowTag|null $flow_tag_parent
     */
    public ?FlowTag $flow_tag_parent;

    /**
     * @var int[]
     */
    public array $children_list;

    protected ?string $children_list_as_string;

    /**
     * @var FlowAppliedTag[] $applied
     */
    public array $applied = [];


    /**
     * @var FlowTagAttribute[] $attributes
     */
    public array $inherited_attributes = [];

    /**
     * @var array<string,string> $css
     */
    public array $css = [];


    public function jsonSerialize(): array
    {

        if ($this->get_brief_json_flag()) {

            $brief = new BriefFlowTag($this);
            return $brief->to_array();
        } else {
            $this->refresh_inherited_fields();
            $standard = FlowTagStandardAttribute::find_standard_attributes($this);


            return [
                "flow_tag_guid" => $this->flow_tag_guid,
                "parent_tag_guid" => $this->parent_tag_guid,
                "flow_project_guid" => $this->flow_project_guid,
                "flow_project_admin_user_guid" => $this->flow_project_admin_user_guid,
                "created_at_ts" => $this->tag_created_at_ts,
                "updated_at_ts" => $this->tag_updated_at_ts,
                "flow_tag_name" => $this->flow_tag_name,
                "attributes" => $this->inherited_attributes,
                "css" => $this->css,
                "standard_attributes" => $standard,
                "flow_tag_parent" => $this->flow_tag_parent,
                "applied" => $this->applied
            ];
        }


    }



    /**
     * Gets the attribute list merged with the parent's attribute, which may be altered by its parent
     * @param FlowTag|null $tag
     * @return FlowTagAttribute[]
     */
    public static function get_attribute_map(?FlowTag $tag) :array {
        $ret = [];
        if (empty($tag)) {return $ret;}

        $ret = static::get_attribute_map($tag->flow_tag_parent);


        foreach ($tag->attributes as $attribute) {
            if (array_key_exists($attribute->tag_attribute_name,$ret)) {
                $new_attribute = FlowTagAttribute::merge_attribute($attribute,$ret[$attribute->tag_attribute_name]);
            } else {
                $new_attribute = FlowTagAttribute::merge_attribute($attribute,null);
            }
            $ret[$attribute->tag_attribute_name] = $new_attribute;
        }

        foreach ($ret as $attribute) {
            $attribute->is_inherited = $attribute->flow_tag_guid !== $tag->flow_tag_guid;
        }
        return $ret;
    }


    public function __construct($object=null){
        $this->attributes = [];
        $this->css = [];
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

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if ($key === 'attributes') {continue;}
            if ($key === 'applied') {continue;}
            if ($key === 'css') {
                $this->css = JsonHelper::fromString(JsonHelper::toString($val));
            } else if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }



        if (is_object($object) && property_exists($object,'attributes') && is_array($object->attributes)) {
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


        if (is_object($object) && property_exists($object,'applied') && is_array($object->applied)) {
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


        if (is_object($object) && property_exists($object,'flow_tag_parent') && !empty($object->flow_tag_parent)) {
            $parent_to_copy = $object->flow_tag_parent;
        } elseif (is_array($object) && array_key_exists('flow_tag_parent',$object) && !empty($object['flow_tag_parent'])) {
            $parent_to_copy  = $object['flow_tag_parent'];
        } else {
            $parent_to_copy = null;
        }
        $this->flow_tag_parent = null;
        if ($parent_to_copy) {
            $this->flow_tag_parent = new FlowTag($parent_to_copy);
        }

        if ($this->children_list_as_string) {
            $this->children_list = explode(',',$this->children_list_as_string);
        }

        $this->refresh_inherited_fields();

    }

    public function refresh_inherited_fields() {
        $this->css = FlowTagStandardAttribute::generate_css_from_attributes($this);
        $this->inherited_attributes = static::get_attribute_map($this);
    }

    /**
     * @param int $project_id
     * @param string|null $new_parent_guid
     * @param bool $b_do_transaction default false
     * @return FlowTag
     * @throws Exception
     */
    public function clone_change_project(int $project_id,?string $new_parent_guid ,bool $b_do_transaction = false) : FlowTag {
        $me = new FlowTag($this); //new to db
        $me->flow_tag_id = null;
        $me->flow_tag_guid = null;
        $me->flow_project_id = $project_id;
        $me->flow_project_guid = null;
        $me->parent_tag_guid = $new_parent_guid;
        $me->parent_tag_id = null;
        //get all applied

        $me->save($b_do_transaction,true,$this->flow_project_id);
        return $me;
    }
    /**
     * @return $this
     * @throws Exception
     */
    public function clone_refresh(bool $b_get_applied=true) : FlowTag {
        if (empty($this->flow_tag_id) && empty($this->flow_tag_guid)) {
            $me = new FlowTag($this); //new to db
            return $me;
        }
        $search = new FlowTagSearchParams();
        if ($this->flow_tag_guid) {
            $search->tag_guids[] = $this->flow_tag_guid;
        } elseif ($this->flow_tag_id) {
            $search->tag_ids[] = $this->flow_tag_id;
        }
        $search->flag_get_applied = $b_get_applied;

        $me_array = static::get_tags($search);
        if (empty($me_array)) {
            throw new InvalidArgumentException("Tag is not found from guid of $this->flow_tag_guid or id of $this->flow_tag_id");
        }
        $me = $me_array[0];
        return $me;
    }

    /**
     * @return FlowTag
     * @throws Exception
     */
    public function clone_with_missing_data() : FlowTag {

        $me = $this->clone_refresh();
        if (empty($me->flow_tag_id) && empty($me->flow_tag_guid)) {
            return $me;
        }
        //clear out the settable ids in the $me, if not set in this
        //set new data for this, overwriting the old
        if ($me->parent_tag_id && !$this->parent_tag_guid) {
            $me->parent_tag_id = null;
            $me->parent_tag_guid = null;
        }

        if (!$this->parent_tag_id && $this->parent_tag_guid) {
            $me->parent_tag_id = null;
            $me->parent_tag_guid = $this->parent_tag_guid;
        }


        $me->flow_tag_name = $this->flow_tag_name;

        /**
         * @var array<string, FlowTagAttribute> $this_attribute_map
         */
        $this_attribute_map = [];

        foreach ($this->attributes as $attribute) {
            if ($attribute->flow_tag_attribute_guid) {
                $this_attribute_map[$attribute->flow_tag_attribute_guid] = $attribute;
            }
        }

        $me_attributes_filtered = [];
        //for each attribute in the $me, that is not in the $this, delete it
        foreach ($me->attributes as  $me_attribute) {
            if (array_key_exists($me_attribute->flow_tag_attribute_guid,$this_attribute_map)) {
                //clear out the settable ids in the $me::attribute, if not set in this::attribute
                //set new data for $me::attribute, overwriting the old

                /**
                 * @var FlowTagAttribute $this_attribute
                 */
                $this_attribute = $this_attribute_map[$me_attribute->flow_tag_attribute_guid];

                if ($me_attribute->points_to_entry_id && !$this_attribute->points_to_flow_entry_guid) {
                    $me_attribute->points_to_entry_id = null;
                }

                if ($me_attribute->points_to_user_id && !$this_attribute->points_to_flow_user_guid) {
                    $me_attribute->points_to_user_id = null;
                }

                if ($me_attribute->points_to_project_id && !$this_attribute->points_to_flow_project_guid) {
                    $me_attribute->points_to_project_id = null;
                }

                $me_attribute->points_to_flow_entry_guid = empty($this_attribute->points_to_flow_entry_guid) ? null:  $this_attribute->points_to_flow_entry_guid ;
                $me_attribute->points_to_flow_user_guid = empty($this_attribute->points_to_flow_user_guid)? null : $this_attribute->points_to_flow_user_guid;
                $me_attribute->points_to_flow_project_guid = empty($this_attribute->points_to_flow_project_guid)? null : $this_attribute->points_to_flow_project_guid ;

                $me_attribute->tag_attribute_name = $this_attribute->tag_attribute_name;

                if ( $this_attribute->tag_attribute_long !== '0' && empty($this_attribute->tag_attribute_long)) {
                    $this_attribute->tag_attribute_long =  null;
                } else {
                    $me_attribute->tag_attribute_long =  intval($this_attribute->tag_attribute_long);
                }

                $me_attribute->tag_attribute_text = empty($this_attribute->tag_attribute_text)? null : $this_attribute->tag_attribute_text;

                $me_attributes_filtered[] = $me_attribute;
                unset($this_attribute_map[$me_attribute->flow_tag_attribute_guid]);
            }
        }

        //add remaining new attributes that have guids
        foreach ($this_attribute_map as $this_attribute_guid => $this_attribute) {
            WillFunctions::will_do_nothing($this_attribute_guid);
            $me_attributes_filtered[] = $this_attribute;
        }

        //add in new attributes with no guid
        foreach ($this->attributes as $attribute) {
            if (!$attribute->flow_tag_attribute_guid) {
                $me_attributes_filtered[] = $attribute;
            }
        }

        $me->attributes = $me_attributes_filtered;
        return $me;
    }

    /**
     * @return FlowTag[]
     * @throws
     */
    public static function get_tags(FlowTagSearchParams $search,
                                    int     $page = 1,
                                    int     $page_size =  self::DEFAULT_TAG_PAGE_SIZE): array
    {

        $args = [];
        $where_project = 2;
        $where_name = 4;
        $where_tag_guid = 8;
        $where_tag_id = 16;
        $where_only_applied_to = 32;
        $where_not_applied_to = 64;


        if ($search->owning_project_guid) {
            $where_project = "driver_project.flow_project_guid = UNHEX(?)";
            $args[] = $search->owning_project_guid;
        }

        if ($search->tag_name_term) {
            $where_name = "driver_tag.flow_tag_name  LIKE ?";
            $args[] = '%'.$search->tag_name_term.'%';
        }

        if (count($search->tag_guids)) {
            $in_question_array = [];
            foreach ($search->tag_guids as $a_guid) {
                if ( ctype_xdigit($a_guid) ) {
                    $args[] = $a_guid;
                    $in_question_array[] = "UNHEX(?)";
                }
            }
            if (count($in_question_array)) {
                $comma_delimited_unhex_question = implode(",",$in_question_array);
                $where_tag_guid = "driver_tag.flow_tag_guid in ($comma_delimited_unhex_question)";
            }
        }



        if (count($search->tag_ids)) {
            $cast_ids = $search->tag_ids;
            array_walk($cast_ids, function(&$x)  use(&$args) {$args[] = intval($x);$x = "?";});
            $comma_delimited_ids = implode(",",$cast_ids);
            $where_tag_id = "driver_tag.id in ($comma_delimited_ids)";
        }

        $maybe_join_inner_driver_applied = '';
        if (count($search->only_applied_to_guids)) {
            $general_search = new GeneralSearchParams();
            $general_search->guids = $search->only_applied_to_guids;
            $gmatches = GeneralSearch::general_search($general_search,1,GeneralSearch::UNLIMITED_RESULTS_PER_PAGE);
            $applied_project_ids=[];
            $applied_user_ids = [];
            $applied_entry_ids = [];
            GeneralSearch::sort_ids_into_arrays($gmatches,$applied_project_ids,$applied_user_ids,$applied_entry_ids);
            $where_part_guid_match = [];
            if (count($applied_project_ids)) {
                $comma_list_applied_projects = implode(',',$applied_project_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_project_id in ($comma_list_applied_projects)";
            }
            if (count($applied_user_ids)) {
                $comma_list_applied_users = implode(',',$applied_user_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_user_id in ($comma_list_applied_users)";
            }
            if (count($applied_entry_ids)) {
                $comma_list_applied_projects = implode(',',$applied_entry_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_entry_id in ($comma_list_applied_projects)";
            }

            if (count($search->only_applied_to_guids) !== (count($applied_project_ids) + count($applied_user_ids) + count($applied_entry_ids))) {
                throw new InvalidArgumentException("One or more invalid guids in the only_applied_to_guids");
            }

            $where_not_applied_to = '(' . implode(' OR ',$where_part_guid_match) . ')';
            $maybe_join_inner_driver_applied = "INNER JOIN flow_applied_tags driver_applied on driver_tag.id = driver_applied.flow_tag_id";

        }


        $maybe_left_join_inner_driver_applied = '';
        if (count($search->not_applied_to_guids)) {
            $general_search = new GeneralSearchParams();
            $general_search->guids = $search->not_applied_to_guids;
            $gmatches = GeneralSearch::general_search($general_search,1,GeneralSearch::UNLIMITED_RESULTS_PER_PAGE);
            $applied_project_ids=[];
            $applied_user_ids = [];
            $applied_entry_ids = [];
            GeneralSearch::sort_ids_into_arrays($gmatches,$applied_project_ids,$applied_user_ids,$applied_entry_ids);
            $where_part_guid_match = [];
            if (count($applied_project_ids)) {
                $comma_list_applied_projects = implode(',',$applied_project_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_project_id NOT IN ($comma_list_applied_projects)";
            }
            if (count($applied_user_ids)) {
                $comma_list_applied_users = implode(',',$applied_user_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_user_id NOT IN ($comma_list_applied_users)";
            }
            if (count($applied_entry_ids)) {
                $comma_list_applied_projects = implode(',',$applied_entry_ids);
                $where_part_guid_match[] = "driver_applied.tagged_flow_entry_id NOT IN ($comma_list_applied_projects)";
            }

            if (count($search->not_applied_to_guids) !== (count($applied_project_ids) + count($applied_user_ids) + count($applied_entry_ids))) {
                throw new InvalidArgumentException("One or more invalid guids in the not_applied_to_guids");
            }
            $where_only_applied_to = '( driver_applied.tagged_flow_project_id IS NULL OR ' . implode(' OR ',$where_part_guid_match) . ')';
            $maybe_left_join_inner_driver_applied = "LEFT JOIN flow_applied_tags driver_applied on driver_tag.id = driver_applied.flow_tag_id";

        }

        $start_place = ($page - 1) * $page_size;


        $db = static::get_connection();

        $sql = "SELECT 
                    t.id                                    as flow_tag_id,
                    t.flow_project_id,
                    t.parent_tag_id,
                    driver.children_list                    as children_list_as_string,                  
                    t.created_at_ts                         as tag_created_at_ts,
                    UNIX_TIMESTAMP(t.updated_at)            as tag_updated_at_ts,
                    t.flow_tag_name,
                    HEX(t.flow_tag_guid)                    as flow_tag_guid,
                    HEX(parent_t.flow_tag_guid)             as parent_tag_guid,
                    HEX(project.flow_project_guid)          as flow_project_guid,
                    HEX(admin_user.flow_user_guid)          as flow_project_admin_user_guid,
       
                    attribute.id                            as flow_tag_attribute_id,
                    attribute.points_to_entry_id,
                    attribute.points_to_user_id,
                    attribute.points_to_project_id,
                    attribute.created_at_ts                 as attribute_created_at_ts,
                    UNIX_TIMESTAMP(attribute.updated_at)    as attribute_updated_at_ts,
                    HEX(attribute.flow_tag_attribute_guid)  as flow_tag_attribute_guid,
                    attribute.tag_attribute_name,
                    attribute.tag_attribute_long,
                    attribute.tag_attribute_text,

                    HEX(point_entry.flow_entry_guid)        as points_to_flow_entry_guid,
                    HEX(point_user.flow_user_guid)          as points_to_flow_user_guid,
                    HEX(point_project.flow_project_guid)    as points_to_flow_project_guid,
       
                    IF(
                        point_entry.flow_entry_title,
                            point_entry.flow_entry_title,
                            IF(point_user.flow_user_name,
                                point_user.flow_user_name,
                                point_project.flow_project_title
                                )
                        ) as points_to_title,
       
                    HEX(point_project_admin.flow_user_guid) as points_to_admin_guid,
                    point_project_admin.flow_user_name as points_to_admin_name
       
                FROM flow_tags t
                INNER JOIN  (
                    
                    
                    WITH RECURSIVE cte AS (
                        (
                            SELECT driver_tag.id as flow_tag_id , driver_tag.parent_tag_id, cast(null as SIGNED ) as child_tag_id
                            FROM flow_tags driver_tag
                            INNER JOIN flow_projects driver_project ON driver_project.id = driver_tag.flow_project_id
                            $maybe_join_inner_driver_applied
                            $maybe_left_join_inner_driver_applied
                            WHERE 1 
                                AND $where_project  
                                AND $where_name
                                AND $where_tag_guid  
                                AND $where_tag_id
                                AND $where_only_applied_to
                                AND $where_not_applied_to
                                LIMIT $start_place , $page_size
                        )
                        UNION
                        DISTINCT
                        (
                            SELECT parent_tag.id as flow_tag_id, parent_tag.parent_tag_id, c.flow_tag_id as child_tag_id
                            FROM cte c
                            INNER JOIN flow_tags parent_tag ON parent_tag.id = c.parent_tag_id
                        )
                    )
                    SELECT cte.flow_tag_id, cte.parent_tag_id, group_concat(cte.child_tag_id) as children_list
                    FROM cte
                    GROUP BY cte.flow_tag_id, cte.parent_tag_id
                    
                    
                )  as driver ON driver.flow_tag_id = t.id  
                LEFT JOIN flow_tags parent_t ON parent_t.id = t.parent_tag_id 
                INNER JOIN flow_projects project ON project.id = t.flow_project_id 
                INNER JOIN flow_users admin_user ON admin_user.id = project.admin_flow_user_id 
                LEFT JOIN flow_tag_attributes attribute on attribute.flow_tag_id = t.id
                LEFT JOIN flow_entries point_entry on attribute.points_to_entry_id = point_entry.id
                LEFT JOIN flow_users point_user on attribute.points_to_user_id = point_user.id 
                LEFT JOIN flow_projects point_project on attribute.points_to_project_id = point_project.id 
                LEFT JOIN flow_users point_project_admin on point_project_admin.id = point_project.admin_flow_user_id
                WHERE 1 
                ORDER BY flow_tag_id,flow_tag_attribute_id DESC ;
                ";

        try {

            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

            /**
             * @var FlowTag[] $ret
             */
            $ret = [];
            /**
             * @var array<string,FlowTag> $map_all_tags_by_id
             */
            $map_all_tags_by_id = [];
            $map_prefix = "tag-";
            /**
             * @var array<string, FlowTag> $rem_tags
             */
            $rem_tags = [];
            foreach ($res as $row) {
                $tag_node = new FlowTag($row);
                if (array_key_exists($tag_node->flow_tag_guid,$rem_tags)) {
                    $tag_node = $rem_tags[$tag_node->flow_tag_guid];
                } else {
                    $rem_tags[$tag_node->flow_tag_guid] = $tag_node;
                    $ret[] = $tag_node;

                    $map_all_tags_by_id[$map_prefix.$tag_node->flow_tag_id] = $tag_node;
                }

                $attribute_node = new FlowTagAttribute($row);
                if ($attribute_node->flow_tag_attribute_id) {
                    $tag_node->attributes[] = $attribute_node;
                }


            }

            //put parents in
            foreach ($map_all_tags_by_id as $prefix_id => $dat_tag_you_do ) {
                WillFunctions::will_do_nothing($prefix_id);
                if ($dat_tag_you_do->parent_tag_id) {
                    $what_prefix = $map_prefix. $dat_tag_you_do->parent_tag_id;
                    if (!array_key_exists($what_prefix,$map_all_tags_by_id)) {
                        throw new LogicException(sprintf("Could not find parent %s for %s ",$dat_tag_you_do->parent_tag_id,$dat_tag_you_do->flow_tag_id));
                    }
                    $proud_parent = $map_all_tags_by_id[$what_prefix];
                    if (empty($proud_parent)) {throw new LogicException("Parent is empty at index of $what_prefix");}
                    $dat_tag_you_do->flow_tag_parent = $proud_parent;
                }
            }

            /**
             * @var FlowTag[] $ret
             */
            $filtered = [];
            //filter out the ones that  are not top level searches, the rest will be in the parent list
            foreach ($ret as $item) {
                if ($search->owning_project_guid) {
                    if ($item->flow_project_guid !== $search->owning_project_guid ) {continue;}
                }

                if ($search->tag_name_term) {
                    if (false === stripos($item->flow_tag_name, $search->tag_name_term) ) {continue;}
                }

                if (count($search->tag_guids)) {
                    $found_guid = false;
                    for($i = 0; $i < count($search->tag_guids); $i++) {
                        if ($item->flow_tag_guid === $search->tag_guids[$i]) {$found_guid = true; break;}
                    }
                    if (!$found_guid) {continue;}
                }

                if (count($search->tag_ids)) {
                    $found_id = false;
                    for($i = 0; $i < count($search->tag_ids); $i++) {
                        if ($item->flow_tag_id === $search->tag_ids[$i]) {$found_id = true; break;}
                    }
                    if (!$found_id) {continue;}
                }

                $filtered[] = $item;
            }

            if ($search->flag_get_applied) {
                //add attached tags
                $tag_id_array = [];

                /**
                 * @var array<string,FlowTag> $match_map
                 */
                $match_map = [];
                foreach ($filtered as $tag_rehydrated) {
                    $tag_id_array[] = $tag_rehydrated->flow_tag_id;
                    $match_map[$tag_rehydrated->flow_tag_guid] = $tag_rehydrated;
                }

                $attached_map = FlowAppliedTag::get_applied_tags($tag_id_array);

                foreach ($attached_map as $tag_guid => $applied_array) {
                    if (array_key_exists($tag_guid,$match_map)) {
                        $match_map[$tag_guid]->applied = $applied_array;
                    }
                }
            }

            //filter some more if restricting to things that are applied
            if (count($search->only_applied_to_guids)) {
                $applied_filter = [];
                foreach ($filtered as $check_again_tag) {
                    $applied_that_includes_tagged = $check_again_tag->find_applied_by_guid_of_tagged($search->only_applied_to_guids);
                    if (empty($applied_that_includes_tagged)) {
                        continue;
                    }
                    $applied_filter[] = $check_again_tag;
                }
                $filtered = $applied_filter;
            }

            if (count($search->not_applied_to_guids)) {
                $applied_filter = [];
                foreach ($filtered as $check_again_tag) {
                    $applied_that_includes_tagged = $check_again_tag->find_applied_by_guid_of_tagged($search->not_applied_to_guids);
                    if (count($applied_that_includes_tagged)) {
                        continue;
                    }
                    $applied_filter[] = $check_again_tag;
                }
                $filtered = $applied_filter;
            }
            foreach ($filtered as $b_tag) {
                $b_tag->refresh_inherited_fields();
            }
            return  $filtered;
        } catch (Exception $e) {
            static::get_logger()->alert("FlowTag model cannot get_tags ",['exception'=>$e]);
            throw $e;
        }

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
     * @param FlowTagAttribute|null $attribute
     * @return $this|FlowTag
     * @throws Exception
     */
    public  function save_tag_return_clones(?string $attribute_name, FlowTagAttribute &$attribute = null): FlowTag
    {
        $this->save(true,true);
        $altered_tag = $this->clone_refresh();

        if ($attribute_name) {
            $attribute = null;
            foreach ($altered_tag->attributes as $look_at) {
                if ($look_at->tag_attribute_name === $attribute_name) {
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
     * @param int|null $n_old_project_id if not null, then saves applied and attributes as new under the current project id
     * @throws Exception
     */
    public function save(bool $b_do_transaction = false, bool $b_save_children = false,?int $n_old_project_id=null) :void {
        $db = null;

        try {
            if (empty($this->flow_tag_name)) {
                throw new InvalidArgumentException("Project Title cannot be empty");
            }


            $b_match = static::check_valid_name($this->flow_tag_name);
            if (!$b_match) {
                $max_len = static::LENGTH_TAG_NAME;
                throw new InvalidArgumentException(
                    "Tag name either empty OR invalid! ".
                    "First character cannot be a number. Name Cannot be greater than $max_len. ".
                    " Title cannot be a hex number greater than 25 and cannot be a decimal number");
            }

            $this->flow_tag_name = JsonHelper::to_utf8($this->flow_tag_name);


            $db = static::get_connection();



            if (!$this->flow_project_id && $this->flow_project_guid) {
                $this->flow_project_id = $db->cell(
                    "SELECT id  FROM flow_projects WHERE flow_project_guid = UNHEX(?)",
                    $this->flow_project_guid);
            }

            if(  !$this->flow_project_id) {
                throw new InvalidArgumentException("When saving a tag for the first time, need its project id or guid");
            }

            if (!$this->parent_tag_id && $this->parent_tag_guid) {
                $this->parent_tag_id = $db->cell(
                    "SELECT id  FROM flow_tags WHERE flow_tag_guid = UNHEX(?)",
                    $this->parent_tag_guid);
            }

            if (empty($this->parent_tag_id)) {$this->parent_tag_id = null;}


            $save_info = [
                'flow_project_id' => $this->flow_project_id,
                'parent_tag_id' => $this->parent_tag_id,
                'flow_tag_name' => $this->flow_tag_name
            ];


            if ($b_do_transaction) {$db->beginTransaction();}
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

                    $attribute->flow_tag_id = $this->flow_tag_id;
                    if ($n_old_project_id) {
                        $attribute->id = null;
                        $attribute->flow_tag_attribute_guid = null;
                        if ($attribute->points_to_project_id === $n_old_project_id) {
                            $attribute->points_to_project_id = $this->flow_project_id;
                            $attribute->points_to_flow_project_guid = null;
                        }
                    }
                    $attribute->save();
                }

                foreach ($this->applied as $app) {
                    $app->flow_tag_id = $this->flow_tag_id;
                    if ($n_old_project_id) {
                        $app->id = null;
                        $app->flow_applied_tag_guid = null;
                        if ($app->tagged_flow_project_id === $n_old_project_id) {
                            $app->tagged_flow_project_id = $this->flow_project_id;
                            $app->tagged_flow_project_guid = null;
                        }
                    }
                    $app->save();
                }
            }
            $this->update_flow_things_with_css();
            if ($b_do_transaction) {$db->commit(); }


        } catch (Exception $e) {
            if ($b_do_transaction && $db) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }
            static::get_logger()->alert("Tag model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }

    public function update_flow_things_with_css() : int {
        if (empty($this->css)) {return 0;}
        $css_json = JsonHelper::toString( $this->css);
        $count = static::get_connection()->safeQuery(
            "UPDATE flow_things SET css_json =  CAST(? AS JSON) WHERE thing_guid = UNHEX(?);",
            [$css_json, $this->flow_tag_guid],
            PDO::FETCH_BOTH,
            true
        );
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
     * @return FlowAppliedTag[]
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



    /**
     * sort parents before children
     * if there are tags with a parent set, but not in the array, then those are put at the end
     * @param FlowTag[] $tag_array_to_sort
     * @return FlowTag[]
     */
    public static function sort_tag_array_by_parent(array $tag_array_to_sort) : array {

        $data = [];
        $data[] = ['id' => 0, 'parent' => -1, 'title' => 'dummy_root','tag'=>null];
        foreach ($tag_array_to_sort as $tag) {
            $data[] = ['id' => $tag->flow_tag_id, 'parent' => $tag->parent_tag_id??0, 'title' => $tag->flow_tag_name,'tag'=>$tag];
        }
        $tree = new Tree(
            $data,
            ['rootId' => -1]
        );

        $sorted_nodes =  $tree->getNodes();
        $ret = [];
        foreach ($sorted_nodes as $node) {
            $what =  $node->tag??null;
            if ($what) {$ret[] = $what;}
        }
        return $ret;
    }

}