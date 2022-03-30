<?php

namespace app\models\standard;

use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\standard\converters\BaseConverter;
use app\models\tag\FlowTag;
use InvalidArgumentException;
use JsonSerializable;
use LogicException;
use PDO;


class StandardAttributeWrite extends FlowBase implements JsonSerializable {


    protected string $standard_attribute_name;

    protected int $tag_id;

    protected string $tag_guid;

    /**
     * @var RawAttributeData[] $raw_array
     */
    protected array $raw_array = [];

    protected object $standard_attribute_value ;


    public function __construct(string $standard_attribute_name,int $tag_id,string $tag_guid, array $raw_array)
    {
        $this->tag_guid = $tag_guid;
        $this->tag_id = $tag_id;
        $this->standard_attribute_name = $standard_attribute_name;
        $this->raw_array = $raw_array;
        $this->standard_attribute_value = $this->call_converter();

    }

    public function jsonSerialize() : array
    {
        return [
          'standard_name' => $this->standard_attribute_name,
          'standard_value' => $this->standard_attribute_value,
          'tag_guid' => $this->tag_guid,
          'tag_id' => $this->tag_id,
          'attribute_array' => $this->raw_array
        ];

    }

    protected function call_converter() : object {
        $callable = IFlowTagStandardAttribute::STANDARD_ATTRIBUTES[$this->standard_attribute_name]['converter']??[];
        if (empty($callable) || count($callable) !== 2) {
            throw new LogicException(
                "Converter for standard does not have good form or is missing: ". $this->standard_attribute_name);
        }
        $converter_class = $callable[0];
        /**
         * @var BaseConverter $converter
         */
        $converter = new $converter_class($this->raw_array);
        $mu = [$converter,$callable[1]];
        if (!is_callable($mu)) {
            throw new LogicException("Cannot call the folowing for converter: ". JsonHelper::toString($mu));
        }
        $ret =  $converter->convert();
        if (!(is_null($ret) || is_object($ret))) {
            throw new InvalidArgumentException("return of callable is not an object or null !" . JsonHelper::toString($ret));
        }
        return $ret;
    }

    protected function maybe_copy() : void {
        $copy_args_array = IFlowTagStandardAttribute::STANDARD_ATTRIBUTES[$this->standard_attribute_name]['copy']??[];
        if (empty($copy_args_array)) {return;}
        if (!isset($copy_args_array['type'])) {
            throw new LogicException("copy args has no type for standard ". $this->standard_attribute_name);
        }
        switch ($copy_args_array['type']) {
            case IFlowTagStandardAttribute::COPY_TYPE_DB_UPDATE_VALUE : {
                $expected_keys = ['table','id_column','id_value','target_column','target_cast'];
                foreach ($expected_keys as $need_key) {
                    if (!isset($copy_args_array[$need_key])) {
                        throw new LogicException(
                            "copy args array for ".IFlowTagStandardAttribute::COPY_TYPE_DB_UPDATE_VALUE.
                            "has no '$need_key' ". $this->standard_attribute_name);
                    }
                }


                $table = $copy_args_array['table'];
                $id_column = $copy_args_array['id_column'];
                $id_value_begin = $copy_args_array['id_value'] ;
                $target_cast = $copy_args_array['target_cast'] ;
                if (property_exists($this,$id_value_begin)) {
                    $id_value = $this->$id_value_begin;
                } else {
                    $id_value = $id_value_begin;
                }
                if (WillFunctions::is_valid_guid_format($id_value)) {
                    $sql_where_param = 'UNHEX(?)';
                } else {
                    $sql_where_param = '?';
                }
                $target_column = $copy_args_array['target_column'] ;
                $json = JsonHelper::toString($this->standard_attribute_value);
                $sql = /** @lang text */
                    "UPDATE $table SET $target_column =  CAST(? AS $target_cast) WHERE $id_column = $sql_where_param";
                $args = [$json,$id_value];
                $db = static::get_connection();
                $b_changed = $db->safeQuery($sql,$args,PDO::FETCH_BOTH,true);
                WillFunctions::will_do_nothing($b_changed);
                break;
            }
            default: { throw new LogicException("What copy type??! ".$copy_args_array['type']);}
        }
    }

    /**
     * Reads the attributes that matter for the tag guids from sql
     *      Some standards have modifiers for their attribute data, to massage them into an object
     * combines them into the aggregated standards
     * updates the table
     * does any post actions like writing to another column
     * @return int
     */
    public function write() : int  {

        $sql = "INSERT INTO flow_standard_attributes (flow_tag_id,standard_name, standard_json)
                VALUES(?,?,?)
                ON DUPLICATE KEY UPDATE 
                   standard_json = ? ";
        $db = static::get_connection();
        $json = JsonHelper::toString($this->standard_attribute_value);
        $args = [$this->tag_id,$this->standard_attribute_name,$json,$json];
        $ret = $db->safeQuery($sql,$args,PDO::FETCH_BOTH,true);
        $this->maybe_copy();
        return $ret;
    }



    /**
     * @param FlowTag[] $flow_tags
     * @return FlowTagStandardAttribute[]
     */
    public static function createWriters(array $flow_tags) : array {
        $params = new RawAttributeSearchParams();
        foreach ($flow_tags as $tag) {
            $params->addTagID($tag->flow_tag_id);
        }

        $attributes = RawAttributeSearch::search($params);

        //ordered by tag parents first, no particular order for attributes that are flat
        $raw_by_attribute_names_by_tag_guid = [];
        /**
         * @var array<string,RawAttributeData> $raw_map_by_tag_guid
         */
        $raw_map_by_tag_guid = [];

        /**
         * @var array<string,string> $attribute_map_by_attr_guid
         */
        $attribute_map_by_attr_guid = [];

        /**
         * @var array<string,string> $attribute_map_by_attr_guid
         */
        $tag_guid_map_to_parent_tag_guid = [];

        /**
         * @var array<string,int> $tag_guid_map_to_tag_id
         */
        $tag_guid_map_to_tag_id = [];

        foreach ($attributes as $att) {
            $attribute_map_by_attr_guid[$att->getAttributeGuid()] = $att;
            if (!isset($raw_map_by_tag_guid[$att->getTagGuid()])) {
                $raw_map_by_tag_guid[$att->getTagGuid()] = [];
            }
            $raw_map_by_tag_guid[$att->getTagGuid()][] = $att;
            $tag_guid_map_to_parent_tag_guid[$att->getTagGuid()] = $att->getParentTagGuid();
            $tag_guid_map_to_tag_id[$att->getTagGuid()] = $att->getTagID();
        }


        foreach ($raw_map_by_tag_guid as $tag_guid => $raw_attribute_array) {
            if (!isset($raw_by_attribute_names_by_tag_guid[$tag_guid])) {
                $raw_by_attribute_names_by_tag_guid[$tag_guid] = [];
            }

            /**
             * @var RawAttributeData $raw
             */
            foreach ($raw_attribute_array as $raw) {

                $parent_attribute_array = [];
                $parent_attribute_guid = $raw->getParentAttributeGuid();
                while($parent_attribute_guid) {
                    $parent_attribute = $attribute_map_by_attr_guid[$parent_attribute_guid];
                    $parent_attribute_array[] = $parent_attribute;
                    $parent_attribute_guid = $parent_attribute->getParentAttributeGuid();
                }
                $reversed_parent_attributes = array_reverse($parent_attribute_array);
                $reversed_parent_attributes[] = $raw;
                $raw_by_attribute_names_by_tag_guid[$tag_guid][$raw->getAttributeName()] = $reversed_parent_attributes;
            }
        }




        //go through each ran-by and loop though its ancestors adding name if missing
        foreach ($raw_by_attribute_names_by_tag_guid as $tag_guid => &$raw_ancestor_list_by_attribute_names) {
            $tag_parent_guid = $tag_guid_map_to_parent_tag_guid[$tag_guid];
            while($tag_parent_guid) {
                $their_attributes = $raw_by_attribute_names_by_tag_guid[$tag_parent_guid];
                foreach ($their_attributes as $their_attribute_name => $their_raw_array) {
                    if (!isset($raw_ancestor_list_by_attribute_names[$their_attribute_name])) {
                        $raw_ancestor_list_by_attribute_names[$their_attribute_name] = $their_raw_array;
                    }
                }

                $tag_parent_guid = $tag_guid_map_to_parent_tag_guid[$tag_parent_guid];
            }
        }
        unset($raw_ancestor_list_by_attribute_names);

        $ret = [];

        $tag_has_property_names = [];
        foreach ($raw_by_attribute_names_by_tag_guid as $tag_guid => $raw_array) {
            $tag_id = $tag_guid_map_to_tag_id[$tag_guid];
            $writers = static::getWritersFromBunch($tag_id,$tag_guid,$raw_array);
            foreach ($writers as $hand_cramp) {
                $hand_cramp->write();
                if (!isset($tag_has_property_names[$hand_cramp->tag_guid])) {
                    $tag_has_property_names[$hand_cramp->tag_guid] = ['tag_id'=>$hand_cramp->tag_id,'standard'=>[]];
                }
                $tag_has_property_names[$hand_cramp->tag_guid]['standard'][] = $hand_cramp->standard_attribute_name;
                $ret[] = $hand_cramp;
            }
        }
        static::trim_absent_names($tag_has_property_names);
        return $ret;

    }

    protected static function trim_absent_names(array &$array) : void {
        $db = static::get_connection();
        foreach ($array as $tag_guid => $info) {
            $args = [$info['tag_id']];
            $names = $info['standard'];
            if (empty($names)) {continue;}
            $in_question_array = [];
            foreach ($names as $a_name) {
                $args[] = $a_name;
                $in_question_array[] = "?";
            }
            if (count($in_question_array)) {
                $comma_delimited_question = implode(",",$in_question_array);
                $where_not = "a.flow_tag_id = ? AND a.standard_name not in ($comma_delimited_question) ";
            } else {
                continue;
            }
            $sql = "DELETE FROM flow_standard_attributes a WHERE 1 AND $where_not";
            $array[$tag_guid]['number_deleted'] = $db->safeQuery($sql,$args,PDO::FETCH_BOTH,true);
        }
    }

    /**
     * @param int $tag_id
     * @param string $tag_guid
     * @param array<string,RawAttributeData[]> $array_of_attribute_arrays
     * @return StandardAttributeWrite[]
     */
    protected static function getWritersFromBunch(int $tag_id, string $tag_guid, array $array_of_attribute_arrays) :array  {

        if (empty($array_of_attribute_arrays)) {return [];}
        $ret = [];

        foreach (IFlowTagStandardAttribute::STANDARD_ATTRIBUTES as $standard_name => $dets) {

            /**
             * @var RawAttributeData[] $raw_attributes
             */
            $raw_attributes = [];

            foreach ($dets['keys'] as $key_name => $key_thing) {

                if (empty($key_thing)) {
                    $key_thing[IFlowTagStandardAttribute::OPTION_NORMAL] = true;
                }
                foreach ($key_thing as $key_rule => $key_rule_value) {

                    $b_handled = false;
                    switch ($key_rule) {
                        case IFlowTagStandardAttribute::OPTION_VOLATILE: {
                            if (!$key_rule_value) {break;}
                            //assign the very earliest raw attribute only (prune out the rest from $attribute_array)
                            if (isset($array_of_attribute_arrays[$key_name]) && count($array_of_attribute_arrays[$key_name])) {
                                $raw_attributes[] = $array_of_attribute_arrays[$key_name][0];
                            }
                            $b_handled = true;
                            break;
                        }

                        case IFlowTagStandardAttribute::OPTION_REQUIRED: {
                            if (!$key_rule_value) {break;}
                            //if $attribute_array does not have this key, maybe in new attributes?
                            $b_found_in_new = false;
                            foreach ($raw_attributes as $maybe_raw) {
                                if ($maybe_raw->getAttributeName() === $key_name) {
                                    $b_found_in_new = true;
                                    break;
                                }
                            }
                            //can only do it if this required has name in given raw or added raw
                            if (!$b_found_in_new && !isset($array_of_attribute_arrays[$key_name])) {continue 4;}
                            break;
                        }
                    }
                    if ($b_handled) {continue 2;}

                    $found_attributes = $array_of_attribute_arrays[$key_name]??[];
                    if (count($found_attributes) > 0) {
                        $raw_attributes = array_merge($raw_attributes,$found_attributes);
                        continue 2;
                    }


                    if ($key_rule === IFlowTagStandardAttribute::OPTION_DEFAULT) {

                        if (is_callable($key_rule_value)) {
                            $constant_value = call_user_func($key_rule_value);
                        } else {
                            if (is_string($key_rule_value)) {
                                $constant_value = $key_rule_value;
                            } else {
                                $constant_value = JsonHelper::toString($key_rule_value);
                            }

                        }
                        //if missing, then create from function, make raw attribute and add to $attribute_array)
                        // if no creation function leave raw empty of text and long
                        if (isset($array_of_attribute_arrays[$key_name]) && count($array_of_attribute_arrays[$key_name])) {
                            foreach ($array_of_attribute_arrays[$key_name] as $raw) {
                                if ($raw->getTextVal() || $raw->getLongVal()) {break;}
                            }
                            $array_of_attribute_arrays[$key_name][0]->setTextVal($constant_value);
                        } else {

                            $raw_attributes[] = new RawAttributeData([
                                'tag_id'=> $tag_id,
                                'tag_guid'=> $tag_guid ,
                                'text_val'=> $constant_value ,
                                'attribute_name'=> $key_name ,
                            ]);
                        }
                    }
                }

            } //end for each key in a standard attribute

            if (count($raw_attributes)) {
                //if got here can create the writer
                $ret[] = new StandardAttributeWrite($standard_name,$tag_id,$tag_guid,$raw_attributes);
            }


        }
        return $ret;
    }





}