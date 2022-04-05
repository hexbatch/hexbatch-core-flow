<?php

namespace app\models\standard;

use JsonSerializable;
use RuntimeException;


class RawAttributeData implements JsonSerializable {
    protected ?string $attribute_name;
    protected ?string $text_val;
    protected ?int $long_val;
    protected ?int $attribute_id;
    protected ?string $attribute_guid;
    protected ?int $tag_id;
    protected ?string $tag_guid;
    protected ?string $parent_attribute_guid;
    protected ?int $parent_attribute_id;

    protected ?string $parent_tag_guid;
    protected ?int $parent_tag_id;


    public function __construct($object=null) {
        $this->text_val = null;
        $this->long_val = null;
        $this->attribute_guid = null;
        $this->parent_attribute_guid = null;
        $this->tag_guid = null;
        $this->attribute_name = null;
        $this->tag_id = null;
        $this->parent_attribute_id = null;
        $this->attribute_id = null;
        $this->parent_tag_id = null;
        $this->parent_tag_guid = null;



        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }
    }

    public function jsonSerialize() : array
    {
        return [
            'tag_id'=> $this->tag_id ,
            'tag_guid'=> $this->tag_guid ,
            'parent_tag_id'=> $this->parent_tag_id ,
            'parent_tag_guid'=> $this->parent_tag_guid ,
            'attribute_guid'=> $this->attribute_guid ,
            'parent_attribute_guid'=> $this->parent_attribute_guid ,
            'text_val'=> $this->text_val ,
            'long_val'=> $this->long_val,
            'attribute_name'=> $this->attribute_name ,
            'parent_attribute_id'=> $this->parent_attribute_id ,
            'attribute_id'=> $this->attribute_name ,


        ];
    }

    public function getAttributeGuid() : ?string {
        return $this->attribute_guid;
    }

    public function getAttributeID() : ?int {
        return $this->attribute_id;
    }

    public function getTagGuid() : string {
        if (!$this->tag_guid) {
            throw new RuntimeException("Tag Guid is not set");
        }
        return $this->tag_guid;
    }

    public function getTagID() : int {
        if (!$this->tag_id) {
            throw new RuntimeException("Tag ID is not set");
        }
        return $this->tag_id;
    }

    public function getParentTagGuid() : ?string {
        return $this->parent_tag_guid;
    }

    public function getParentTagID() : ?int {
        return $this->parent_tag_id;
    }

    public function getParentAttributeGuid() : ?string {
        return $this->parent_attribute_guid;
    }

    public function getParentAttributeID() : ?int {
        return $this->parent_attribute_id;
    }

    public function getLongVal() : ?int {
        return $this->long_val;
    }

    public function getTextVal() : ?string {
        return $this->text_val;
    }

    /**
     * @param string|null $text_val
     */
    public function setTextVal(?string $text_val): void
    {
        $this->text_val = $text_val;
    }

    /**
     * @param int|null $long_val
     */
    public function setLongVal(?int $long_val): void
    {
        $this->long_val = $long_val;
    }

    public function getAttributeName() : ?string {
        return $this->attribute_name;
    }


    public function setParentAttributeGuid(?string $what)  {
        $this->parent_attribute_guid = $what;
    }

    public function setParentAttributeID(?int $what)   {
        $this->parent_attribute_id = $what;
    }



}