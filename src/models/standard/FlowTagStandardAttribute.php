<?php

namespace app\models\standard;

use app\hexlet\JsonHelper;
use app\models\base\FlowBase;
use app\models\tag\FlowTag;
use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;


class FlowTagStandardAttribute extends FlowBase implements JsonSerializable,IFlowTagStandardAttribute {


    public ?string $standard_name;
    public ?object $standard_value;
    public ?int $standard_updated_ts;
    public ?string $tag_guid;
    public ?string $standard_guid;
    public ?int $tag_id;
    public ?int $standard_id;


    public function __construct($object=null) {
        $this->standard_name = null;
        $this->standard_value = null;
        $this->standard_updated_ts = null;
        $this->tag_guid = null;
        $this->tag_id = null;
        $this->standard_guid = null;
        $this->standard_id = null;

        if (empty($object)) {
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }
    }

    public function getStandardValue(): object
    {
        if (!$this->standard_value) {
            throw new RuntimeException("Standard Value is not set");
        }
        return $this->standard_value;
    }

    public function getStandardName(): string
    {
        if (!$this->standard_name) {
            throw new RuntimeException("Standard Name is not set");
        }
        return $this->standard_name;
    }

    public function getLastUpdatedTs() : int {
        if (!$this->standard_updated_ts) {
            throw new RuntimeException("Standard Updated Timestamp is not set");
        }
        return $this->standard_updated_ts;
    }

    public function getTagGuid() : string {
        if (!$this->tag_guid) {
            throw new RuntimeException("Standard Tag Guid is not set");
        }
        return $this->tag_guid;
    }

    public function getTagId() : int {
        if (is_null($this->tag_id)) {
            throw new RuntimeException("Standard Tag Id is not set");
        }
        return $this->tag_id;
    }

    public function getStandardGuid() : string  {
        if (is_null($this->standard_guid)) {
            throw new RuntimeException("Standard GUID is not set");
        }
        return $this->standard_guid;
    }

    public function getStandardId() : int  {
        if (is_null($this->standard_id)) {
            throw new RuntimeException("Standard ID is not set");
        }
        return $this->standard_id;
    }

    public function getStandardValueToArray() : array  {
        return JsonHelper::fromString(JsonHelper::toString($this->standard_value));
    }

    public function jsonSerialize(): array
    {

        $output_value = clone $this->standard_value;
        $keys = static::getStandardAttributeKeys($this->standard_name);
        foreach ($keys as $key) {
            if (property_exists($output_value,$key)) {
                if (self::STANDARD_ATTRIBUTES[$this->standard_name][$key][static::OPTION_NO_SERIALIZATION]??null) {
                    unset($output_value->$key);
                }
            }
        }
        return
            [
                'standard_name' => $this->standard_name,
                'standard_value' => $output_value,
                'standard_updated_ts' => $this->standard_updated_ts,
                'tag_guid' => $this->tag_guid,
                'tag_id' => $this->tag_id,
                'standard_guid' => $this->standard_guid,
                'standard_id' => $this->standard_id,
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
     * @return StandardAttributeWrite[]
     */
    public static function write_standard_attributes(array $flow_tags): array
    {
        $writers = StandardAttributeWrite::createWriters($flow_tags);
        return $writers;
    }


    public static function getStandardAttributeKeys(string $name, bool $b_ignore_non_enumerated = true): array
    {
        if (!self::STANDARD_ATTRIBUTES[$name]??null) {
            throw new InvalidArgumentException("[get_standard_attribute_keys] name not found in standard meta: $name");
        }
        $key_array =  self::STANDARD_ATTRIBUTES[$name]['keys'];
        $ret = [];
        foreach ($key_array as $key_name => $dets) {
            if (in_array(static::OPTION_NO_ENUMERATION,$dets)) {
                if ($b_ignore_non_enumerated) {
                    continue;
                }
            }
            $ret[] = $key_name;
        }
        return $ret;
    }

    public static function getStandardAttributeNames() : array {
        return array_keys(static::STANDARD_ATTRIBUTES);
    }


}