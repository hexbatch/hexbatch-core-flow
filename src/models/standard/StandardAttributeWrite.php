<?php

namespace app\models\standard;

use app\hexlet\JsonHelper;
use app\models\base\FlowBase;
use app\models\tag\FlowTag;
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
        $callable = IFlowTagStandardAttribute::STANDARD_ATTRIBUTES[$this->standard_attribute_name]['converter'];
        return call_user_func($callable,$this->raw_array);
    }

    protected function maybe_copy() : void {
        $copy_args_array = IFlowTagStandardAttribute::STANDARD_ATTRIBUTES[$this->standard_attribute_name]['copy']??[];
        if (empty($copy_args_array)) {return;}
        if (!isset($copy_args_array['type'])) {
            throw new LogicException("copy args has no type for standard ". $this->standard_attribute_name);
        }
        switch ($copy_args_array['type']) {
            case IFlowTagStandardAttribute::COPY_TYPE_DB_UPDATE_VALUE : {
                $expected_keys = ['table','id_column','id_value','target_column'];
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
                if (property_exists($this,$id_value_begin)) {
                    $id_value = $this->$id_value_begin;
                } else {
                    $id_value = $id_value_begin;
                }
                $target_column = $copy_args_array['target_column'] ;
                $json = JsonHelper::toString($this->standard_attribute_value);
                $sql = /** @lang text */
                    "UPDATE $table SET $target_column = ? WHERE $id_column = ?";
                $args = [$json,$id_value];
                $db = static::get_connection();
                $db->safeQuery($sql,$args,PDO::FETCH_BOTH,true);
                break;
            }
            default: { throw new LogicException("What copy type??!");}
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

        $sql = "INSERT INTO flow_tag_standard_attributes (flow_tag_id,tag_attribute_name, tag_attribute_json)
                VALUES(?,?,?)
                ON DUPLICATE KEY UPDATE 
                   tag_attribute_json = ? ";
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
            $params->addTagID($tag->id);
        }

        $attributes = RawAttributeSearch::search($params);
        $attribute_map_by_tag = [];
        foreach ($attributes as $raw_attribute) {
            if (!$attribute_map_by_tag[$raw_attribute->getTagGuid()]??null) {
                $attribute_map_by_tag[$raw_attribute->getTagGuid()] = [];
            }
            $attribute_map_by_tag[$raw_attribute->getTagGuid()][] = [];
            if (!$attribute_map_by_tag[$raw_attribute->getTagGuid()][$raw_attribute->get_attribute_name()]??null) {
                $attribute_map_by_tag[$raw_attribute->getTagGuid()][$raw_attribute->get_attribute_name()][] = $raw_attribute;
            }
        }

        $ret = [];

        $tag_has_property_names = [];
        foreach ($attribute_map_by_tag as $tag_guid => $array_of_attribute_arrays) {
            $writers = static::getWritersFromBunch($tag_guid,$array_of_attribute_arrays);
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
                $where_not = "a.tag_id = ? AND a.tag_attribute_name not in ($comma_delimited_question) ";
            } else {
                continue;
            }
            $sql = "DELETE FROM flow_tag_standard_attributes WHERE 1 AND $where_not";
            $array[$tag_guid]['number_deleted'] = $db->safeQuery($sql,$args,PDO::FETCH_BOTH,true);
        }
    }

    /**
     * @param string $tag_guid
     * @param array<string,RawAttributeData[]> $array_of_attribute_arrays
     * @return StandardAttributeWrite[]
     */
    protected static function getWritersFromBunch(string $tag_guid, array $array_of_attribute_arrays) :array  {

        if (empty($array_of_attribute_arrays)) {return [];}
        $ret = [];
        $first_key = array_key_first($array_of_attribute_arrays);
        $tag_id = $array_of_attribute_arrays[$first_key]->getTagID();

        foreach (IFlowTagStandardAttribute::STANDARD_ATTRIBUTES as $standard_name => $dets) {

            $raw_attributes = [];

            foreach ($dets['keys'] as $key_name => $key_thing) {

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
                            //if $attribute_array does not have this key, then cannot do this
                            if (!isset($array_of_attribute_arrays[$key_name])) {continue 3;}
                            break;
                        }
                    }
                    if ($b_handled) {continue 2;}

                    $raw_attributes = array_merge($raw_attributes,$array_of_attribute_arrays[$key_name]??[]);

                    if ($key_rule === IFlowTagStandardAttribute::OPTION_DEFAULT) {

                        //if missing, then create from function, make raw attribute and add to $attribute_array)
                        // if no creation function leave raw empty of text and long
                        if (isset($array_of_attribute_arrays[$key_name]) && count($array_of_attribute_arrays[$key_name])) {
                            foreach ($array_of_attribute_arrays[$key_name] as $raw) {
                                if ($raw->getTextVal() || $raw->getLongVal()) {break;}
                            }
                            $constant_value = call_user_func($key_rule_value);
                            $array_of_attribute_arrays[$key_name][0]->setTextVal($constant_value);
                        } else {
                            $constant_value = call_user_func($key_rule_value);
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
            //if got here can create the writer
            $ret[] = new StandardAttributeWrite($standard_name,$tag_id,$tag_guid,$raw_attributes);

        }
        return $ret;
    }





}