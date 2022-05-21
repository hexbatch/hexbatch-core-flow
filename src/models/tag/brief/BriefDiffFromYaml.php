<?php

namespace app\models\tag\brief;

use app\models\base\SearchParamBase;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\multi\GeneralSearchResult;
use app\models\project\IFlowProject;
use app\models\tag\FlowTag;
use app\models\tag\IFlowAppliedTag;
use app\models\tag\IFlowTagAttribute;
use Exception;
use JsonException;
use LogicException;
use Symfony\Component\Yaml\Yaml;

class BriefDiffFromYaml {

    public IFlowProject $project;

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
    public array $brief_tag_map = [];


    /**
     * @var array<string,BriefFlowTag> $brief_tag_map
     */
    public array $from_yaml_as_brief_tag_map = [];

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
            $changes[] = "Added tag ".$tag->getName();
        }

        foreach ($this->removed_tags as $tag) {
            $changes[] = "Removed tag ".$tag->getName();
        }

        foreach ($this->changed_tags as $tag) {
            $name_to_use = $tag->getNewName() ?? $tag->getName();
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
            if (!array_key_exists($att->getTagGuid(),$this->brief_tag_map)) {
                throw new LogicException(sprintf(
                    "[get_changed_attribute_summary_line:added] tag guid %s is not in map ",$att->getTagGuid()));
            }
            $tag = $this->brief_tag_map[$att->getTagGuid()];
            $tag_name_to_use = $tag->getNewName() ?? $tag->getName();
            $changes[] = sprintf("Added attribute %s to tag $tag_name_to_use",$att->getName());
        }

        foreach ($this->removed_attributes as $att) {
            if (!array_key_exists($att->getTagGuid(),$this->brief_tag_map)) {
                throw new LogicException(sprintf(
                    "[get_changed_attribute_summary_line:added] tag guid %s is not in map ",$att->getTagGuid()));
            }
            $tag = $this->brief_tag_map[$att->getTagGuid()];
            $tag_name_to_use = $tag->getNewName() ?? $tag->getName();
            $changes[] = sprintf("Removed attribute %s  from tag $tag_name_to_use",$att->getName());
        }

        foreach ($this->changed_attributes as $att) {
            if (!array_key_exists($att->getTagGuid(),$this->brief_tag_map)) {
                throw new LogicException(sprintf(
                    "[get_changed_attribute_summary_line:added] tag guid %s  is not in map ",$att->getTagGuid()));
            }
            $tag = $this->brief_tag_map[$att->getTagGuid()];
            $tag_name_to_use = $tag->getNewName() ?? $tag->getName();
            $name_to_use = $att->getNewName() ?? $att->getName();
            $changes[] = "Changed attribute $name_to_use in tag $tag_name_to_use";
        }

        $ret = implode(', ',$changes);

        return $ret;
    }

    /**
     * @throws JsonException
     */
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
            if (!array_key_exists($app->getTagGuid(),$this->brief_tag_map)) {
                throw new LogicException(
                    sprintf("[get_changed_applied_summary_line:added] tag guid  %s  is not in map ",$app->getTagGuid()));
            }
            $tag = $this->brief_tag_map[$app->getTagGuid()];
            $tag_name_to_use = $tag->getNewName() ?? $tag->getName();
            $tagged_guid = $app->get_tagged_guid();
            if (!array_key_exists($tagged_guid,$target_map)) {
                throw new LogicException("[get_changed_applied_summary_line:added] target guid $tagged_guid is not in map ");
            }
            $target_name = $target_map[$tagged_guid]->title;
            $target_type = $target_map[$tagged_guid]->type;
            $changes[] = "Added applied $tag_name_to_use to $target_type $target_name";
        }

        foreach ($this->removed_applied as $app) {
            if (!array_key_exists($app->getTagGuid(),$this->brief_tag_map)) {
                throw new LogicException(
                    sprintf("[get_changed_applied_summary_line:removed] tag guid %s is not in map ",$app->getTagGuid()));
            }
            $tag = $this->brief_tag_map[$app->getTagGuid()];
            $tag_name_to_use = $tag->getNewName() ?? $tag->getName();
            $tagged_guid = $app->get_tagged_guid();
            $target_name = $target_map[$tagged_guid]->title;
            $target_type = $target_map[$tagged_guid]->type;
            $changes[] = "Removed applied $tag_name_to_use from $target_type $target_name";
        }



        $ret = implode(', ',$changes);

        return $ret;
    }


    /**
     * @param IFlowProject $project
     * @param string|null $yaml_file
     * @param bool $b_changed_is_set_from_file
     * @throws Exception
     */
    public function __construct(IFlowProject $project,?string $yaml_file=null,bool $b_changed_is_set_from_file = false){
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
         * @var array<string,IFlowTagAttribute> $attribute_map
         */
        $attribute_map = [];


        /**
         * @var array<string,IFlowAppliedTag> $applied_map
         */
        $applied_map = [];


        foreach ($saved as $btag) {

            $btag_object = new BriefFlowTag($btag);
            $btag_object->setProjectGuid($project->get_project_guid());
            $b_tag_map[$btag_object->getGuid()] = $btag_object;
            $this->brief_tag_map[$btag_object->getGuid()] = $btag_object;
            $this->from_yaml_as_brief_tag_map[$btag_object->getGuid()] = $btag_object;

            foreach ($btag_object->getAttributes() as $batt) {
                $b_attribute_map[$batt->getGuid()] = new BriefFlowTagAttribute($batt);
            }

            foreach ($btag_object->getApplied() as $bapp) {
                $b_applied_map[$bapp->getGuid()] = new BriefFlowAppliedTag($bapp);
            }

        }

        foreach ($current_tags as $tag) {
            $tag_map[$tag->getGuid()] = $tag;
            if (!array_key_exists($tag->getGuid(),$this->brief_tag_map)) {
                $this->brief_tag_map[$tag->getGuid()] = new BriefFlowTag($tag);
            }

            foreach ($tag->getAttributes() as $att) {
                $attribute_map[$att->getGuid()] = $att;
            }

            foreach ($tag->getApplied() as $app) {
                $applied_map[$app->getGuid()] = $app;
            }
        }

        foreach ($b_tag_map as $tag_guid => $btag) {
            if (array_key_exists($tag_guid,$tag_map)) {
                $tag = $tag_map[$tag_guid];
                if ($tag->getName() !== $btag->getName() ||
                    $tag->getParentGuid() !== $btag->getParentGuid())
                {
                    if ($b_changed_is_set_from_file) {
                        $this->changed_tags[] = $btag;
                    } else {
                        $this->changed_tags[] = new BriefFlowTag($tag);
                    }

                }

                if ($tag->getName() !== $btag->getName()) {
                    $btag->setName($tag->getName());
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
                    $attribute->getPointsToFlowEntryGuid() !== $battribute->getPointsToFlowEntryGuid() ||
                    $attribute->getPointsToFlowUserGuid() !== $battribute->getPointsToFlowUserGuid() ||
                    $attribute->getPointsToFlowProjectGuid() !== $battribute->getPointsToFlowProjectGuid() ||
                    $attribute->getPointsToFlowTagGuid() !== $battribute->getPointsToFlowTagGuid() ||
                    $attribute->getText() !== $battribute->getText() ||
                    intval($attribute->getLong()) !== intval($battribute->getLong()) ||
                    $attribute->getName() !== $battribute->getName()
                ) {
                    if ($b_changed_is_set_from_file) {
                        $this->changed_attributes[] = $battribute;
                    } else {
                        $this->changed_attributes[] = new BriefFlowTagAttribute($attribute);
                    }

                }

                if ($attribute->getName() !== $battribute->getName()) {
                    $battribute->setNewName($attribute->getName());
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