<?php

namespace app\models\standard;

use app\helpers\UserHelper;
use app\helpers\Utilities;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\tag\FlowTag;
use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;


class FlowTagStandardAttribute extends FlowBase implements JsonSerializable,IFlowTagStandardAttribute {


    protected ?string $standard_name;
    /**
     * @var mixed|null
     */
    protected  $standard_value;

    protected ?int $standard_updated_ts;
    protected ?string $project_guid;
    protected ?string $tag_guid;
    protected ?string $standard_guid;
    protected ?int $tag_id;
    protected ?int $standard_id;


    public function __construct($object=null) {
        $this->standard_name = null;
        $this->standard_value = null;
        $this->standard_updated_ts = null;
        $this->project_guid = null;
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

        $this->standard_value = Utilities::convert_to_object($this->standard_value);
    }

    public function getStandardValue(): ?object
    {
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

    public function getProjectGuid() : string  {
        if (!$this->project_guid) {
            throw new RuntimeException("Standard Project Guid is not set");
        }
        return $this->project_guid;
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
                'project_guid' => $this->project_guid,
                'tag_guid' => $this->tag_guid,
                'standard_guid' => $this->standard_guid,
            ];
    }

    /**
     * gets hash with guid of tag as key, and array of standard attributes as value
     * (reads these from db)
     * @param FlowTag[] $flow_tags
     * @return array<string,IFlowTagStandardAttribute[]>
     */
    public static function read_standard_attributes_of_tags(array $flow_tags): array
    {
        $params = new StandardAttributeSearchParams();

        foreach ($flow_tags as $tag) {
            $params->addTagGuid($tag->flow_tag_guid);
        }

        $found = StandardAttributeSearch::search($params);
        $ret = [];
        foreach ($found as $standard) {
            if (!isset($ret[$standard->getTagGuid()])) { $ret[$standard->getTagGuid()] = [];}
            $ret[$standard->getTagGuid()][] = $standard;
        }
        return $ret;
    }

    /**
     * @param string|string[] $project_guid
     * @return array<string,IFlowTagStandardAttribute[]>  mapped to project guid
     */
    public  static function read_standard_attributes_of_projects( $project_guid) : array {
        $params = new StandardAttributeSearchParams();

        $params->addOwningProject($project_guid);

        $found = StandardAttributeSearch::search($params);
        $ret = [];
        foreach ($found as $standard) {
            if (!isset($ret[$standard->getProjectGuid()])) { $ret[$standard->getProjectGuid()] = [];}
            $ret[$standard->getProjectGuid()][] = $standard;
        }
        return $ret;
    }

    /**
     * @param string $user_name_email_or_guid (email, username or guid)
     * @param bool $b_user_project_only default true
     * @return IFlowTagStandardAttribute[]  flat array of user's project's guids
     * @throws
     */
    public  static function read_standard_attributes_of_user(string $user_name_email_or_guid,bool $b_user_project_only = true) : array {
        $params = new StandardAttributeSearchParams();

        $params->setOwningUser($user_name_email_or_guid);

        if ($b_user_project_only) {
            $home_project = UserHelper::get_user_helper()->get_user_home_project();
            $params->addOwningProject($home_project->flow_project_guid);
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
        if (!isset(self::STANDARD_ATTRIBUTES[$name])) {
            throw new InvalidArgumentException("[get_standard_attribute_keys] name not found in standard meta: $name");
        }
        $key_array =  self::STANDARD_ATTRIBUTES[$name]['keys'];
        $ret = [];
        foreach ($key_array as $key_name => $dets) {
            $det_keys = array_keys($dets);
            if (in_array(static::OPTION_NO_ENUMERATION,$det_keys)) {
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

    public static function isNameKey(string $key_name,bool $is_also_protected = false ) : bool {
        foreach ( static::STANDARD_ATTRIBUTES as $attribute_name  => $dets) {
            WillFunctions::will_do_nothing($attribute_name);
            $keys = array_keys($dets['keys']??[]);
            if (in_array($key_name,$keys)) {
                if ($is_also_protected) {
                    if ( isset($dets[$key_name][static::OPTION_NO_SERIALIZATION])) {
                        if ($dets[$key_name][static::OPTION_NO_SERIALIZATION]) {
                            return true;
                        }
                    }
                    return false;
                } else {
                    return true;
                }
            }
        }

        return false;
    }


}