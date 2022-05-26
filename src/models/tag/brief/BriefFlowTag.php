<?php

namespace app\models\tag\brief;



use app\helpers\Utilities;
use app\hexlet\WillFunctions;
use app\models\tag\FlowTag;
use app\models\tag\IFlowTagBasic;
use InvalidArgumentException;

use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use JsonSerializable;
use stdClass;

class BriefFlowTag implements JsonSerializable,IFlowTagBasic {

    protected string $flow_tag_guid;
    protected ?string $parent_tag_guid;
    protected ?string $flow_project_guid;
    protected ?int $tag_created_at_ts;
    protected ?int $tag_updated_at_ts;
    protected string $flow_tag_name;

    protected ?string $new_name;

    /**
     * @return string|null
     */
    public function getNewName(): ?string
    {
        return $this->new_name;
    }


    /**
     * @var BriefFlowTagAttribute[] $attributes
     */
    protected array $attributes = [];

    /**
     * @var BriefFlowAppliedTag[] $applied
     */
    protected array $applied = [];


    /**
     * @param FlowTag|BriefFlowTag|stdClass|array $tag
     * @throws JsonException
     */
    public function __construct(FlowTag|BriefFlowTag|stdClass|array $tag){

        if (is_array($tag)) {
            $tag = Utilities::convert_to_object($tag);
        }
        $this->new_name = null;

        if ($tag instanceof stdClass) {
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
            $this->tag_created_at_ts =  WillFunctions::value_from_property_names_or_default($tag,
                ['tag_created_at_ts','updated_at_ts']);
            $this->tag_updated_at_ts = WillFunctions::value_from_property_names_or_default($tag,
                ['tag_updated_at_ts','updated_at_ts']);
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

            $this->tag_created_at_ts =  WillFunctions::value_from_property_names_or_default(
                $tag,['tag_created_at_ts','updated_at_ts']);
            $this->tag_updated_at_ts = WillFunctions::value_from_property_names_or_default(
                $tag,['tag_updated_at_ts','updated_at_ts']);

            $this->flow_tag_name = $tag->flow_tag_name;

        } else {
            $this->flow_tag_guid = $tag->getGuid();
            if (!WillFunctions::is_valid_guid_format($this->flow_tag_guid)) {
                throw new InvalidArgumentException(
                    sprintf("Cannot process tag %s guid %s  from yaml, invalid format (length or chars) : ",
                        $tag->getName()??'(no name)', $this->getName() ));
            }
            $this->parent_tag_guid = $tag->getParentGuid();

            if ($this->parent_tag_guid) {
                if (!WillFunctions::is_valid_guid_format($this->flow_tag_guid)) {
                    throw new InvalidArgumentException(
                        sprintf("Cannot process parent of tag %s guid %s  from yaml, invalid format (length or chars) : ",
                            $tag->getGuid()??'(no name)', $this->parent_tag_guid ));
                }
            }


            $this->flow_project_guid = $tag->getProjectGuid();


            $brief_attributes = [];
            foreach ($tag->getAttributes() as $att) {
                $brief_attributes[] = new BriefFlowTagAttribute($att);
            }
            $this->attributes = $brief_attributes;

            $brief_applied = [];
            foreach ($tag->getApplied() as $app) {
                $brief_applied[] = new BriefFlowAppliedTag($app);
            }

            $this->tag_created_at_ts =  $tag->getCreatedAtTs();
            $this->tag_updated_at_ts = $tag->getUpdatedAtTs();

            $this->flow_tag_name = $tag->getName();
        }
        $this->applied = $brief_applied;

    }

    #[ArrayShape(["flow_tag_guid" => "string", "parent_tag_guid" => "null|string", "flow_project_guid" => "null|string", "created_at_ts" => "\int|mixed|null", "updated_at_ts" => "\int|mixed|null", "flow_tag_name" => "string", "attributes" => "\app\models\tag\brief\BriefFlowTagAttribute[]|array", "applied" => "\app\models\tag\brief\BriefFlowAppliedTag[]|array"])]
    public function jsonSerialize(): array {
        return $this->to_array();
    }

    #[ArrayShape(["flow_tag_guid" => "string", "parent_tag_guid" => "null|string", "flow_project_guid" => "null|string",
                     "created_at_ts" => "int|mixed|null", "updated_at_ts" => "int|mixed|null", "flow_tag_name" => "string",
                      "attributes" => "\app\models\tag\brief\BriefFlowTagAttribute[]|array",
                       "applied" => "\app\models\tag\brief\BriefFlowAppliedTag[]|array"])]
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



    public function getCreatedAtTs(): ?int
    {
        return $this->tag_created_at_ts;
    }

    public function setCreatedAtTs(?int $tag_created_at_ts): void
    {
        $this->tag_created_at_ts = $tag_created_at_ts;
    }

    public function getGuid(): ?string
    {
        return $this->flow_tag_guid;
    }

    public function setGuid(?string $flow_tag_guid): void
    {
        $this->flow_tag_guid = $flow_tag_guid;
    }

    public function getName(): ?string
    {
        return $this->flow_tag_name;
    }

    public function setName(?string $flow_tag_name): void
    {
        $this->flow_tag_name = $flow_tag_name;
    }

    public function getUpdatedAtTs(): ?int
    {
        return $this->tag_updated_at_ts;
    }

    public function setUpdatedAtTs(?int $tag_updated_at_ts): void
    {
        $this->tag_updated_at_ts = $tag_updated_at_ts;
    }

    public function getParentGuid(): ?string
    {
        return $this->parent_tag_guid;
    }

    public function setParentGuid(?string $parent_tag_guid): void
    {
        $this->parent_tag_guid = $parent_tag_guid;
    }

    /**
     * @return BriefFlowTagAttribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return BriefFlowAppliedTag[]
     */
    public function getApplied(): array
    {
        return $this->applied;
    }

    public function getProjectGuid(): ?string
    {
        return $this->flow_project_guid;
    }

    public function setProjectGuid(?string $flow_project_guid): void
    {
        $this->flow_project_guid = $flow_project_guid;
    }
}