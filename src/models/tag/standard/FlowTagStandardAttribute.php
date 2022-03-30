<?php

namespace app\models\tag\standard;

use app\hexlet\JsonHelper;
use app\models\base\FlowBase;
use app\models\tag\FlowTag;
use JsonSerializable;
use LogicException;
use PDO;
use RuntimeException;


class FlowTagStandardAttribute extends FlowBase implements JsonSerializable,IFlowTagStandardAttribute {


    public ?string $standard_name;
    public ?object $standard_value;
    public ?int $standard_updated_ts;
    public ?string $tag_guid;


    public function __construct($object=null) {
        $this->standard_name = null;
        $this->standard_value = null;
        $this->standard_updated_ts = null;

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }
    }

    public function get_standard_value(): object
    {
        if (!$this->standard_value) {
            throw new RuntimeException("Standard Value is not set");
        }
        return $this->standard_value;
    }

    public function get_standard_name(): string
    {
        if (!$this->standard_name) {
            throw new RuntimeException("Standard Name is not set");
        }
        return $this->standard_name;
    }

    public function get_last_updated_ts() : int {
        if (!$this->standard_updated_ts) {
            throw new RuntimeException("Standard Updated Timestamp is not set");
        }
        return $this->standard_updated_ts;
    }

    public function get_tag_guid() : string {
        if (!$this->tag_guid) {
            throw new RuntimeException("Standard Tag Guid is not set");
        }
        return $this->tag_guid;
    }

    public function get_standard_value_to_array() : array  {
        return JsonHelper::fromString(JsonHelper::toString($this->standard_value));
    }

    public function jsonSerialize(): array
    {

        return
            [
                'standard_name' => $this->standard_name,
                'standard_value' => $this->standard_value,
                'standard_updated_ts' => $this->standard_updated_ts,
                'tag_guid' => $this->tag_guid,
            ];
    }

    /**
     * gets hash with guid of tag as key, and array of standard attributes as value
     * (reads these from db)
     * @param FlowTag[] $flow_tags
     * @return array<string,IFlowTagStandardAttribute[]>
     */
    public static function read_standard_attributes(array $flow_tags): array
    {
        $params = new StandardAttributeSearchParams();

        foreach ($flow_tags as $tag) {
            $params->addTagGuid($tag->flow_tag_guid);
        }

        $ret = StandardAttributeSearch::search($params);
        return $ret;
    }

    /**
     * Writes standard attributes to db
     * @param FlowTag[] $flow_tags
     * @return void
     */
    public static function write_standard_attributes(array $flow_tags): int
    {
        $writer = new StandardAttributeWrite($flow_tags);
        return $writer->write();
    }


    #-------------------- new above


    public static function from_array(string $standard_type,array $what) : FlowTagStandardAttribute {
        switch ($standard_type) {
            case static::STD_ATTR_NAME_CSS: {
                $text =  JsonHelper::toString($what); //number ignored
                $number = null;
                $node = new FlowTagStandardAttribute($standard_type,$text,$number);
                return $node;
            }
            default: {
                throw new LogicException("[from_array] standard attribute not defined for this: ". $standard_type);
            }
        }
    }

    public function merge(FlowTagStandardAttribute $older) : FlowTagStandardAttribute {
        if ($older->standard_name !== $this->standard_name) {
            throw new LogicException("Standard attribute merge types needs to be the same");
        }
        $newer = new FlowTagStandardAttribute($this->standard_name,$this->standard_value,$this->number);
        switch ($this->standard_name) {
            case static::STD_ATTR_NAME_CSS: {
                $product_array = array_merge($older->get_standard_value_to_array(),$this->get_standard_value_to_array());
                return static::from_array($this->standard_name,$product_array);
            }
            default: {
                return $newer;
            }
        }
    }




    /**
     * Fills in standard tags recursively from base most parent, having each descendant able to overwrite
     * if standard attribute never defined, then that value is null
     * @param FlowTag|null $tag
     * @return array<string,FlowTagStandardAttribute>
     */
    public static function find_standard_attributes(?FlowTag $tag) :array {
        $ret = [];
        if (empty($tag)) {return $ret;}

        $ret = static::find_standard_attributes($tag->flow_tag_parent);

        foreach ($tag->attributes as $attribute) {
            if ($attribute->isStandardAttribute()) {
                $number_val = $attribute->getTagAttributeLong();
                $text_val = static::format($attribute->getStandardAttributeType(),
                                $attribute->getTagAttributeText(),$number_val);

                $node = new FlowTagStandardAttribute($attribute->getStandardAttributeType(),$text_val,$number_val);
                if (isset($ret[$attribute->getStandardAttributeType()])) {
                    $ret[$attribute->getStandardAttributeType()] = $node->merge($ret[$attribute->getStandardAttributeType()]);
                } else {
                    $ret[$attribute->getStandardAttributeType()] = $node;
                }
            }
        }


        return $ret;
    }

    /**
     * @param FlowTag|null $tag
     * @return array
     */
    public static function generate_css_from_attributes(?FlowTag $tag) :array {
        $attributes = static::find_standard_attributes($tag);
        if (isset($attributes[static::STD_ATTR_NAME_CSS])) {
            return $attributes[static::STD_ATTR_NAME_CSS]->get_standard_value_to_array();
        }
        $json_string = static::format(static::STD_ATTR_NAME_CSS,null);
        return JsonHelper::fromString($json_string);
    }

    public static function format( ?string $standard_type, ?string $text, ?int &$number = null ) : ?string {
            if (empty($standard_type)) {return $text;}

            switch ($standard_type) {
                case static::STD_ATTR_NAME_CSS: {
                    //css will be json object for text and null as the number
                    $number = null;
                    if (empty($text)) { return JsonHelper::toString((object)[]);}
                    $maybe_css_object = JsonHelper::fromString($text,false,false);
                    $clean_ret = [];
                    if (is_object($maybe_css_object)) {
                        foreach ($maybe_css_object as $css_name => $css_value) {
                            if (in_array($css_name,array_keys(static::STD_ATTR_TYPE_CSS['keys']))) {
                                $clean_ret[$css_name] = $css_value;
                            }
                        }
                    } else {
                        $maybe_css_rules = array_map(function($x) {return trim($x);},explode(';',$text));
                        foreach ($maybe_css_rules as $css_line) {
                            $maybe_css_parts = array_map(function($x) {return trim($x);},explode(':',$css_line));
                            if (in_array($maybe_css_parts[0],array_keys(static::STD_ATTR_TYPE_CSS['keys'])) && !empty($maybe_css_parts[1])) {
                                $clean_ret[$maybe_css_parts[0]] = $maybe_css_parts[1];
                            }
                        }
                        if (empty($clean_ret) ) {
                            JsonHelper::fromString($text,true) ; //simply-test
                        }
                    }
                    return JsonHelper::toString((object)$clean_ret);
                }
            }

        return $text;
    }

    public static function post_save_update_with_standard_attributes(FlowTag $tag ) : int {
        $tag->css = static::generate_css_from_attributes($tag);
        $css_json = JsonHelper::toString( $tag->css);
        $count = static::get_connection()->safeQuery(
            "UPDATE flow_things SET css_json =  CAST(? AS JSON) WHERE thing_guid = UNHEX(?);",
            [$css_json, $tag->flow_tag_guid],
            PDO::FETCH_BOTH,
            true
        );
        return $count;
    }









}