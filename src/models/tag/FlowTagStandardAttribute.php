<?php

namespace app\models\tag;

use JsonSerializable;

class FlowTagStandardAttribute implements JsonSerializable {

    const STD_ATTR_COLOR = 'color';
    const STD_ATTR_BACKGROUND_COLOR = 'background_color';

    const STANDARD_ATTRIBUTES = [
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

    /**
     * Fills in standard tags recursively from base most parent, having each descendant able to overwrite
     * if standard attribute never defined, then that value is null
     * @param FlowTag|null $tag
     * @return FlowTagStandardAttribute[]
     */
    public static function find_standard_attributes(?FlowTag $tag) :array {
        $ret = [];
        if (empty($tag)) {return $ret;}

        $ret = static::find_standard_attributes($tag->flow_tag_parent);

        foreach ($tag->attributes as $attribute) {
            if ($attribute->is_standard_attribute) {
                if ($attribute->tag_attribute_long) {
                    $value = $attribute->tag_attribute_long;
                } elseif ($attribute->tag_attribute_text) {
                    $value = $attribute->tag_attribute_text;
                } elseif ($attribute->points_to_flow_user_guid) {
                    $value = $attribute->points_to_flow_user_guid;
                } elseif ($attribute->points_to_flow_project_guid) {
                    $value = $attribute->points_to_flow_project_guid;
                } elseif ($attribute->points_to_flow_entry_guid) {
                    $value = $attribute->points_to_flow_entry_guid;
                } else {
                    continue;
                }

                $ret[$attribute->tag_attribute_name] = new FlowTagStandardAttribute($attribute->tag_attribute_name,$value);
            }
        }

        foreach (static::STANDARD_ATTRIBUTES as $std) {
            if (!array_key_exists($std,$ret)) {
                $ret[$std] = null;
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