<?php

namespace app\models\tag\brief;



use app\helpers\Utilities;
use app\hexlet\WillFunctions;
use app\models\tag\FlowTag;
use InvalidArgumentException;
use JsonSerializable;

class BriefFlowTag implements JsonSerializable {

    public string $flow_tag_guid;
    public ?string $parent_tag_guid;
    public ?string $flow_project_guid;
    public ?int $tag_created_at_ts;
    public ?int $tag_updated_at_ts;
    public string $flow_tag_name;

    public ?string $new_name;

    /**
     * @var BriefFlowTagAttribute[] $attributes
     */
    public array $attributes = [];

    /**
     * @var BriefFlowAppliedTag[] $applied
     */
    public array $applied = [];


    /**
     * @param FlowTag|BriefFlowTag $tag
     */
    public function __construct( $tag){

        if (is_array($tag)) {
            $tag = Utilities::convert_to_object($tag);
        }

        $this->new_name = null;

        $this->flow_tag_guid = $tag->flow_tag_guid;
        if (!WillFunctions::is_valid_guid_format($this->flow_tag_guid)) {
            throw new InvalidArgumentException(
                sprintf("Cannot process tag %s guid %s  from yaml, invalid format (length or chars) : ",
                    $tag->flow_tag_name??'(no name)', $this->flow_tag_guid ));
        }
        $this->parent_tag_guid = $tag->parent_tag_guid;

        if ($this->parent_tag_guid) {
            if (!WillFunctions::is_valid_guid_format($this->flow_tag_guid)) {
                throw new InvalidArgumentException(
                    sprintf("Cannot process parent of tag %s guid %s  from yaml, invalid format (length or chars) : ",
                        $tag->flow_tag_name??'(no name)', $this->parent_tag_guid ));
            }
        }


        $this->flow_project_guid = $tag->flow_project_guid;
        $this->tag_created_at_ts =  WillFunctions::value_from_property_names_or_default($tag,['tag_created_at_ts','updated_at_ts']);
        $this->tag_updated_at_ts = WillFunctions::value_from_property_names_or_default($tag,['tag_updated_at_ts','updated_at_ts']);
        $this->flow_tag_name = $tag->flow_tag_name;

        $brief_attributes = [];
        foreach ($tag->attributes as $att) {
            $brief_attributes[] = new BriefFlowTagAttribute($att);
        }
        $this->attributes = $brief_attributes;

        $brief_applied = [];
        foreach ($tag->applied as $app) {
            $brief_applied[] = new BriefFlowAppliedTag($app);
        }
        $this->applied = $brief_applied;
    }

    public function jsonSerialize(): array {
        return $this->to_array();
    }

    public function to_array() : array {
        return [
            "flow_tag_guid" => $this->flow_tag_guid,
            "parent_tag_guid" => $this->parent_tag_guid,
            "flow_project_guid" => $this->flow_project_guid,
            "created_at_ts" => $this->tag_created_at_ts,
            "updated_at_ts" => $this->tag_updated_at_ts,
            "flow_tag_name" => $this->flow_tag_name,
            "attributes" => $this->attributes,
            "applied" => $this->applied
        ];
    }

    /**
     * @param string[] $put_issues_here
     * @return int  returns 0 or 1
     */
    public function has_minimal_information(array &$put_issues_here = []) : int {

        $us =
            (
            ($this->flow_project_guid && WillFunctions::is_valid_guid_format($this->flow_project_guid) ) &&
            ($this->flow_tag_guid && WillFunctions::is_valid_guid_format($this->flow_tag_guid) ) &&
            $this->tag_created_at_ts &&
            $this->flow_tag_name

            );
        $missing_list = [];

        if (!$this->flow_project_guid || !WillFunctions::is_valid_guid_format($this->flow_project_guid) ) {$missing_list[] = 'project guid';}
        if (!$this->flow_tag_guid || !WillFunctions::is_valid_guid_format($this->flow_project_guid) ) {$missing_list[] = 'own guid';}
        if (!$this->tag_created_at_ts) {$missing_list[] = 'timestamp';}
        if (!$this->flow_tag_name  ) {$missing_list[] = 'name';}

        $tag_name = $this->flow_tag_name??'{unnamed}';
        $tag_guid = $this->flow_tag_guid??'{no-guid}';
        if (!$us) {
            $put_issues_here[] = "Tag $tag_name of guid $tag_guid missing: ". implode(',',$missing_list);
        }
        $b_bad_children = false;

        foreach ($this->attributes as $att) {
            $what =  $att->has_minimal_information($put_issues_here);
            if (!$what) {$b_bad_children = true;}
            $us &= $what;
        }

        foreach ($this->applied as $app) {
            $what =  $app->has_minimal_information($put_issues_here);
            if (!$what) {$b_bad_children = true;}
            $us &= $what;
        }

        if ($b_bad_children) {
            $put_issues_here[] = "Tag $tag_name of guid $tag_guid children missing data ";
        }

        return intval($us);
    }
}