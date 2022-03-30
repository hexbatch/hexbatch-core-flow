<?php

namespace app\models\tag\brief;

use app\models\base\SearchParamBase;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\multi\GeneralSearchResult;
use app\models\project\FlowProject;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagAttribute;
use Exception;
use LogicException;
use Symfony\Component\Yaml\Yaml;

class BriefDiffFromYaml {

    public FlowProject $project;

    /**
     * @var BriefFlowTag[] $changed_tags
     */
    public array $changed_tags = [];

    /**
     * @var BriefFlowTag[] $added_tags
     */
    public array $added_tags = [];

    /**
     * @var BriefFlowTag[] $removed_tags
     */
    public array $removed_tags = [];


    /**
     * @var BriefFlowTagAttribute[] $changed_attributes
     */
    public array $changed_attributes = [];

    /**
     * @var BriefFlowTagAttribute[] $added_attributes
     */
    public array $added_attributes = [];

    /**
     * @var BriefFlowTagAttribute[] $removed_attributes
     */
    public array $removed_attributes = [];


    /**
     * @var BriefFlowAppliedTag[] $added_applied
     */
    public array $added_applied = [];

    /**
     * @var BriefFlowAppliedTag[] $removed_applied
     */
    public array $removed_applied = [];


    /**
     * @var array<string,BriefFlowTag> $brief_tag_map
     */
    public $brief_tag_map = [];


    /**
     * @var array<string,BriefFlowTag> $brief_tag_map
     */
    public $from_yaml_as_brief_tag_map = [];

    protected bool $b_yaml_file_exists = false;

    public function does_yaml_exist() : bool { return $this->b_yaml_file_exists;}

    public function count_changes() : int {
        $total_count =
            count($this->changed_tags) +
            count($this->added_tags) +
            count($this->removed_tags) +
            count($this->changed_attributes)+
            count($this->added_attributes)+
            count($this->removed_attributes)+
            count($this->added_applied) +
            count($this->removed_applied);
        return $total_count;
    }

    public function get_changed_tag_summary_line() : string {

        /**
         * @var string[] $changes
         */
        $changes = [];

        foreach ($this->added_tags as $tag) {
            $changes[] = "Added tag $tag->flow_tag_name";
        }

        foreach ($this->removed_tags as $tag) {
            $changes[] = "Removed tag $tag->flow_tag_name";
        }

        foreach ($this->changed_tags as $tag) {
            $name_to_use = $tag->new_name ?? $tag->flow_tag_name;
            $changes[] = "Changed tag $name_to_use";
        }

        $ret = implode(', ',$changes);

        return $ret;
    }


    public function get_changed_attribute_summary_line() : string {

        /**
         * @var string[] $changes
         */
        $changes = [];

        foreach ($this->added_attributes as $att) {
            if (!array_key_exists($att->flow_tag_guid,$this->brief_tag_map)) {
                throw new LogicException("[get_changed_attribute_summary_line:added] tag guid $att->flow_tag_guid is not in map ");
            }
            $tag = $this->brief_tag_map[$att->flow_tag_guid];
            $tag_name_to_use = $tag->new_name ?? $tag->flow_tag_name;
            $changes[] = "Added attribute $att->tag_attribute_name to tag $tag_name_to_use";
        }

        foreach ($this->removed_attributes as $att) {
            if (!array_key_exists($att->flow_tag_guid,$this->brief_tag_map)) {
                throw new LogicException("[get_changed_attribute_summary_line:added] tag guid $att->flow_tag_guid is not in map ");
            }
            $tag = $this->brief_tag_map[$att->flow_tag_guid];
            $tag_name_to_use = $tag->new_name ?? $tag->flow_tag_name;
            $changes[] = "Removed attribute $att->tag_attribute_name from tag $tag_name_to_use";
        }

        foreach ($this->changed_attributes as $att) {
            if (!array_key_exists($att->flow_tag_guid,$this->brief_tag_map)) {
                throw new LogicException("[get_changed_attribute_summary_line:added] tag guid $att->flow_tag_guid is not in map ");
            }
            $tag = $this->brief_tag_map[$att->flow_tag_guid];
            $tag_name_to_use = $tag->new_name ?? $tag->flow_tag_name;
            $name_to_use = $att->new_name ?? $att->tag_attribute_name;
            $changes[] = "Changed attribute $name_to_use in tag $tag_name_to_use";
        }

        $ret = implode(', ',$changes);

        return $ret;
    }

    public function get_changed_applied_summary_line() : string {

        /**
         * @var string[] $changes
         */
        $changes = [];

        /**
         * @var string[] $guids_to_get
         */
        $guids_to_get = [];

        foreach ($this->added_applied as $app) {
            $tagged_guid = $app->get_tagged_guid();
            if (!in_array($tagged_guid,$guids_to_get)) { $guids_to_get[]= $tagged_guid;}
        }

        foreach ($this->removed_applied as $app) {
            $tagged_guid = $app->get_tagged_guid();
            if (!in_array($tagged_guid,$guids_to_get)) { $guids_to_get[]= $tagged_guid;}
        }

        if (empty($guids_to_get)) {return '';}

        $search = new GeneralSearchParams();
        $search->guids = $guids_to_get;
        $search->setPage(1);
        $search->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
        $targets = GeneralSearch::general_search($search);

        /**
         * @var array<string,GeneralSearchResult> $target_map
         */
        $target_map = [];

        foreach ($targets as $target) {
            $target_map[$target->guid] = $target;
        }

        foreach ($this->added_applied as $app) {
            if (!array_key_exists($app->flow_tag_guid,$this->brief_tag_map)) {
                throw new LogicException("[get_changed_applied_summary_line:added] tag guid $app->flow_tag_guid is not in map ");
            }
            $tag = $this->brief_tag_map[$app->flow_tag_guid];
            $tag_name_to_use = $tag->new_name ?? $tag->flow_tag_name;
            $tagged_guid = $app->get_tagged_guid();
            if (!array_key_exists($tagged_guid,$target_map)) {
                throw new LogicException("[get_changed_applied_summary_line:added] target guid $tagged_guid is not in map ");
            }
            $target_name = $target_map[$tagged_guid]->title;
            $target_type = $target_map[$tagged_guid]->type;
            $changes[] = "Added applied $tag_name_to_use to $target_type $target_name";
        }

        foreach ($this->removed_applied as $app) {
            if (!array_key_exists($app->flow_tag_guid,$this->brief_tag_map)) {
                throw new LogicException("[get_changed_applied_summary_line:removed] tag guid $app->flow_tag_guid is not in map ");
            }
            $tag = $this->brief_tag_map[$app->flow_tag_guid];
            $tag_name_to_use = $tag->new_name ?? $tag->flow_tag_name;
            $tagged_guid = $app->get_tagged_guid();
            if (!array_key_exists($tagged_guid,$target_map)) {
                throw new LogicException("[get_changed_applied_summary_line:removed] target guid $tagged_guid is not in map ");
            }
            $target_name = $target_map[$tagged_guid]->title;
            $target_type = $target_map[$tagged_guid]->type;
            $changes[] = "Removed applied $tag_name_to_use from $target_type $target_name";
        }



        $ret = implode(', ',$changes);

        return $ret;
    }


    /**
     * @param FlowProject $project
     * @param string|null $yaml_file
     * @param bool $b_changed_is_set_from_file
     * @throws Exception
     */
    public function __construct(FlowProject $project,?string $yaml_file=null,bool $b_changed_is_set_from_file = false){
        $this->project = $project;
        $this->changed_tags = [];
        $this->added_tags = [];
        $this->removed_tags = [];
        $this->changed_attributes = [];
        $this->added_attributes = [];
        $this->removed_attributes = [];
        $this->added_applied = [];
        $this->removed_applied = [];
        $this->brief_tag_map = [];
        $this->from_yaml_as_brief_tag_map = [];
        $this->b_yaml_file_exists = false;

        $current_tags = $this->project->get_all_owned_tags_in_project(true,true);
        if (empty($yaml_file)) {
            $yaml_file = $this->project->get_tag_yaml_path();
        }

        if (is_readable($yaml_file)) {
            $this->b_yaml_file_exists = true;
            $saved = Yaml::parseFile($yaml_file);
        } else {
            $saved = [];
        }



        /**
         * @var array<string,BriefFlowTag> $b_tag_map
         */
        $b_tag_map = [];


        /**
         * @var array<string,BriefFlowTagAttribute> $b_attribute_map
         */
        $b_attribute_map = [];


        /**
         * @var array<string,BriefFlowAppliedTag> $b_applied_map
         */
        $b_applied_map = [];



        /**
         * @var array<string,FlowTag> $tag_map
         */
        $tag_map = [];


        /**
         * @var array<string,FlowTagAttribute> $attribute_map
         */
        $attribute_map = [];


        /**
         * @var array<string,FlowAppliedTag> $applied_map
         */
        $applied_map = [];


        foreach ($saved as $btag) {

            $btag_object = new BriefFlowTag($btag);
            $b_tag_map[$btag_object->flow_tag_guid] = $btag_object;
            $this->brief_tag_map[$btag_object->flow_tag_guid] = $btag_object;
            $this->from_yaml_as_brief_tag_map[$btag_object->flow_tag_guid] = $btag_object;

            foreach ($btag_object->attributes as $batt) {
                $b_attribute_map[$batt->flow_tag_attribute_guid] = new BriefFlowTagAttribute($batt);
            }

            foreach ($btag_object->applied as $bapp) {
                $b_applied_map[$bapp->flow_applied_tag_guid] = new BriefFlowAppliedTag($bapp);
            }

        }

        foreach ($current_tags as $tag) {
            $tag_map[$tag->flow_tag_guid] = $tag;
            if (!array_key_exists($tag->flow_tag_guid,$this->brief_tag_map)) {
                $this->brief_tag_map[$tag->flow_tag_guid] = new BriefFlowTag($tag);
            }

            foreach ($tag->attributes as $att) {
                $attribute_map[$att->getFlowTagAttributeGuid()] = $att;
            }

            foreach ($tag->applied as $app) {
                $applied_map[$app->flow_applied_tag_guid] = $app;
            }
        }

        foreach ($b_tag_map as $tag_guid => $btag) {
            if (array_key_exists($tag_guid,$tag_map)) {
                $tag = $tag_map[$tag_guid];
                if ($tag->flow_tag_name !== $btag->flow_tag_name ||
                    $tag->parent_tag_guid !== $btag->parent_tag_guid)
                {
                    if ($b_changed_is_set_from_file) {
                        $this->changed_tags[] = $btag;
                    } else {
                        $this->changed_tags[] = new BriefFlowTag($tag);
                    }

                }

                if ($tag->flow_tag_name !== $btag->flow_tag_name) {
                    $btag->new_name = $tag->flow_tag_name;
                }
            } else {
                $this->removed_tags[] = $btag;
            }
        }

        foreach ($tag_map as $tag_guid => $tag) {
            if (!array_key_exists($tag_guid,$b_tag_map)) {
                $this->added_tags[] = new BriefFlowTag($tag);
            }
        }


        foreach ($b_attribute_map as $attribute_guid => $battribute) {
            if (array_key_exists($attribute_guid,$attribute_map)) {
                $attribute = $attribute_map[$attribute_guid];
                if (
                    $attribute->getPointsToFlowEntryGuid() !== $battribute->points_to_flow_entry_guid ||
                    $attribute->getPointsToFlowUserGuid() !== $battribute->points_to_flow_user_guid ||
                    $attribute->getPointsToFlowProjectGuid() !== $battribute->points_to_flow_project_guid ||
                    $attribute->getTagAttributeText() !== $battribute->tag_attribute_text ||
                    $attribute->getTagAttributeLong() !== $battribute->tag_attribute_long ||
                    $attribute->getTagAttributeName() !== $battribute->tag_attribute_name
                ) {
                    if ($b_changed_is_set_from_file) {
                        $this->changed_attributes[] = $battribute;
                    } else {
                        $this->changed_attributes[] = new BriefFlowTagAttribute($attribute);
                    }

                }

                if ($attribute->getTagAttributeName() !== $battribute->tag_attribute_name) {
                    $battribute->new_name = $attribute->getTagAttributeName();
                }
            } else {
                $this->removed_attributes[] = $battribute;
            }
        }

        foreach ($attribute_map as $attribute_guid => $attribute) {
            if (!array_key_exists($attribute_guid,$b_attribute_map)) {
                $this->added_attributes[] = new BriefFlowTagAttribute($attribute);
            }
        }


        foreach ($b_applied_map as $applied_guid => $bapplied) {
            if (!array_key_exists($applied_guid,$applied_map)) {
                $this->removed_applied[] = $bapplied;
            }
        }

        foreach ($applied_map as $applied_guid => $applied) {
            if (!array_key_exists($applied_guid,$b_applied_map)) {
                $this->added_applied[] = new BriefFlowAppliedTag($applied);
            }
        }


    }
}