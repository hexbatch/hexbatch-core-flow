<?php

namespace app\models\tag;

use JsonSerializable;

class FlowTagStandardAttribute implements JsonSerializable {

    const STD_ATTR_TYPE_CSS = 'css';


    const STD_ATTR_COLOR = 'color';
    const STD_ATTR_BACKGROUND_COLOR = 'background-color';

    const STANDARD_ATTRIBUTES = [
        self::STD_ATTR_BACKGROUND_COLOR,
        self::STD_ATTR_COLOR,
    ];

    const CSS_ATTRIBUTES = [
        self::STD_ATTR_BACKGROUND_COLOR,
        self::STD_ATTR_COLOR,
    ];

    public string $name;
    public ?string $text;

    public function __construct(string $name, ?string $text) {
        $this->name = $name;
        $this->text = $text;
    }

    public static function is_standard_attribute(FlowTagAttribute $attribute): bool
    {
        if (in_array($attribute->tag_attribute_name,static::STANDARD_ATTRIBUTES)) {
            return true;
        }
        return false;
    }

    public static function get_standard_attribute_type(FlowTagAttribute $attribute): ?string
    {
        if (in_array($attribute->tag_attribute_name,static::CSS_ATTRIBUTES)) {
            return static::STD_ATTR_TYPE_CSS;
        }
        return null;
    }

    /**
     * Fills in standard tags recursively from base most parent, having each descendant able to overwrite
     * if standard attribute never defined, then that value is null
     * @param FlowTag|null $tag
     * @param bool $b_add_empty default true
     * @return FlowTagStandardAttribute[]
     */
    public static function find_standard_attributes(?FlowTag $tag, bool $b_add_empty = true) :array {
        $ret = [];
        if (empty($tag)) {return $ret;}

        $ret = static::find_standard_attributes($tag->flow_tag_parent,$b_add_empty);

        foreach ($tag->attributes as $attribute) {
            if ($attribute->is_standard_attribute) {
                if ($attribute->tag_attribute_text) {
                    $value = $attribute->tag_attribute_text;
                } elseif ($attribute->tag_attribute_long) {
                    $value = $attribute->tag_attribute_long;
                } elseif ($attribute->points_to_flow_user_guid) {
                    $value = $attribute->points_to_flow_user_guid;
                } elseif ($attribute->points_to_flow_project_guid) {
                    $value = $attribute->points_to_flow_project_guid;
                } elseif ($attribute->points_to_flow_entry_guid) {
                    $value = $attribute->points_to_flow_entry_guid;
                } else {
                    continue;
                }

                $at_node = new FlowTagStandardAttribute($attribute->tag_attribute_name,$value);
                $ret[$attribute->tag_attribute_name] = $at_node->text;
            }
        }

        if ($b_add_empty) {
            foreach (static::STANDARD_ATTRIBUTES as $std) {
                if (!array_key_exists($std,$ret)) {
                    $ret[$std] = null;
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
        $ret = [];
        foreach ($attributes as $attribute_name => $attribute_text) {
            if (in_array($attribute_name,static::CSS_ATTRIBUTES)) {
                $ret[$attribute_name] = $attribute_text;
            }
        }
        return $ret;

    }


    public function jsonSerialize(): array
    {
        return [
            "name" => $this->name,
            "text" => $this->text
        ];
    }
}