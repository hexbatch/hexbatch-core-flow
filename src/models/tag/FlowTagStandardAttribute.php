<?php

namespace app\models\tag;

use app\hexlet\JsonHelper;
use app\models\base\FlowBase;
use JsonSerializable;
use LogicException;
use PDO;

class FlowTagStandardAttribute extends FlowBase implements JsonSerializable {

    const STD_ATTR_TYPE_CSS = 'css';
    const STD_ATTR_GIT_KEY = 'git_key';
    const STD_ATTR_GIT_URL = 'git_url';
    const STD_ATTR_GIT_BRANCH = 'git_branch';

    const PROPERTY_ALWAYS_ADD_BLANK_IF_EMPTY = 'always-add-blank-if-empty';
    const PROPERTY_DO_NOT_SAVE_TO_FILE = 'do-not-save-to-file';


    const STANDARD_ATTRIBUTES = [
        self::STD_ATTR_TYPE_CSS => [self::PROPERTY_ALWAYS_ADD_BLANK_IF_EMPTY],
        self::STD_ATTR_GIT_KEY => [self::PROPERTY_DO_NOT_SAVE_TO_FILE],
        self::STD_ATTR_GIT_URL => [],
        self::STD_ATTR_GIT_BRANCH => [],
    ];

    const CSS_KEY_COLOR = 'color';
    const CSS_KEY_BACKGROUND_COLOR = 'background-color';

    const CSS_KEYS = [
        self::CSS_KEY_BACKGROUND_COLOR,
        self::CSS_KEY_COLOR,
    ];

    public string $standard_type;
    public ?string $text;
    public ?string $number;

    /**
     * @param string $standard_type
     * @param string|null $text
     * @param int|null $number
     */
    public function __construct(string $standard_type, ?string $text,?int $number) {
        $this->standard_type = $standard_type;
        $this->text = $text;
        $this->number = $number;
    }

    public static function from_array(string $standard_type,array $what) : FlowTagStandardAttribute {
        switch ($standard_type) {
            case static::STD_ATTR_TYPE_CSS: {
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
        if ($older->standard_type !== $this->standard_type) {
            throw new LogicException("Standard attribute merge types needs to be the same");
        }
        $newer = new FlowTagStandardAttribute($this->standard_type,$this->text,$this->number);
        switch ($this->standard_type) {
            case static::STD_ATTR_TYPE_CSS: {
                $product_array = array_merge($older->to_array(),$this->to_array());
                return static::from_array($this->standard_type,$product_array);
            }
            default: {
                return $newer;
            }
        }
    }

    public function to_array() {
        switch ($this->standard_type) {
            case static::STD_ATTR_TYPE_CSS: {
                return JsonHelper::fromString($this->text); //number ignored
            }
            default: {
                throw new LogicException("[to_array] standard attribute not defined for this: ". $this->standard_type);
            }
        }
    }

    public static function is_standard_attribute(FlowTagAttribute $attribute): bool
    {
        if (empty($attribute->getTagAttributeName())) {return false;}

        if (in_array($attribute->getTagAttributeName(),array_keys(static::STANDARD_ATTRIBUTES))) {
            return true;
        }
        return false;
    }

    public static function get_standard_attribute_type(FlowTagAttribute $attribute): ?string
    {
        if (empty($attribute->getTagAttributeName())) {
            return null;
        }
        if (in_array($attribute->getTagAttributeName(),array_keys(static::STANDARD_ATTRIBUTES))) {
            return $attribute->getTagAttributeName();
        }
        return null;
    }

    /**
     * Fills in standard tags recursively from base most parent, having each descendant able to overwrite
     * if standard attribute never defined, then that value is null
     * @param FlowTag|null $tag
     * @param bool $b_add_empty default true
     * @return array<string,FlowTagStandardAttribute>
     */
    public static function find_standard_attributes(?FlowTag $tag, bool $b_add_empty = true) :array {
        $ret = [];
        if (empty($tag)) {return $ret;}

        $ret = static::find_standard_attributes($tag->flow_tag_parent,$b_add_empty);

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

        if ($b_add_empty) {
            foreach (static::STANDARD_ATTRIBUTES as $std => $properites) {
                if (in_array(static::PROPERTY_ALWAYS_ADD_BLANK_IF_EMPTY, $properites)) {

                    if (!array_key_exists($std, $ret)) {
                        $ret[$std] = null;
                    }
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
        $attributes = static::find_standard_attributes($tag,false);
        if (isset($attributes[static::STD_ATTR_TYPE_CSS])) {
            return $attributes[static::STD_ATTR_TYPE_CSS]->to_array();
        }
        $json_string = static::format(static::STD_ATTR_TYPE_CSS,null);
        return JsonHelper::fromString($json_string);
    }

    public static function format( ?string $standard_type, ?string $text, ?int &$number = null ) : ?string {
            if (empty($standard_type)) {return $text;}

            switch ($standard_type) {
                case static::STD_ATTR_TYPE_CSS: {
                    //css will be json object for text and null as the number
                    $number = null;
                    if (empty($text)) { return JsonHelper::toString((object)[]);}
                    $maybe_css_object = JsonHelper::fromString($text,false,false);
                    $clean_ret = [];
                    if (is_object($maybe_css_object)) {
                        foreach ($maybe_css_object as $css_name => $css_value) {
                            if (in_array($css_name,static::CSS_KEYS)) {
                                $clean_ret[$css_name] = $css_value;
                            }
                        }
                    } else {
                        $maybe_css_rules = array_map(function($x) {return trim($x);},explode(';',$text));
                        foreach ($maybe_css_rules as $css_line) {
                            $maybe_css_parts = array_map(function($x) {return trim($x);},explode(':',$css_line));
                            if (in_array($maybe_css_parts[0],static::CSS_KEYS) && !empty($maybe_css_parts[1])) {
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


    public function jsonSerialize(): array
    {
        return $this->to_array();
    }
}