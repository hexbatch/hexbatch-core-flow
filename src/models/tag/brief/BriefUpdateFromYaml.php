<?php

namespace app\models\tag\brief;



use app\models\base\FlowBase;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\project\FlowProject;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagAttribute;
use Exception;
use RuntimeException;

class BriefUpdateFromYaml extends FlowBase {

    public BriefDiffFromYaml $yaml_diff;
    public FlowProject $project;

    /**
     * Reverse of Brief,
     *      Those marked as added are to be deleted
     *      Those marked as removed will be created
     *      Those marked as modified will be saved
     * @param FlowProject $project
     * @throws Exception
     */
    public function __construct( FlowProject $project){
        $this->project = $project;
        $this->yaml_diff = new BriefDiffFromYaml($this->project);

        //get ids for the removed and modified

        /**
         * @var array<string,string> $guids_needed
         */
        $guids_needed = [];

        /**
         * @var FlowAppliedTag[] $saved_applied
         */
        $saved_applied = [];


        /**
         * @var FlowTagAttribute[] $saved_attributes
         */
        $saved_attributes = [];


        /**
         * @var FlowTag[] $saved_tags
         */
        $saved_tags = [];



        foreach ($this->yaml_diff->removed_applied as $b_applied) {
            $app = new FlowAppliedTag($b_applied);
            $saved_applied[]=$app;
            $guids = $app->get_needed_guids_for_empty_ids();
            foreach ($guids as $guid) {
                $guids_needed[$guid] = true;
            }
        }

        foreach ($this->yaml_diff->removed_attributes as $b_attribute) {
            $att = new FlowTagAttribute($b_attribute);
            $saved_attributes[]=$att;
            $guids = $att->get_needed_guids_for_empty_ids();
            foreach ($guids as $guid) {
                $guids_needed[$guid] = true;
            }
        }

        foreach ($this->yaml_diff->changed_attributes as $b_attribute) {
            $att = new FlowTagAttribute($b_attribute);
            $saved_attributes[]=$att;
            $guids = $att->get_needed_guids_for_empty_ids();
            foreach ($guids as $guid) {
                $guids_needed[$guid] = true;
            }
        }

        foreach ($this->yaml_diff->removed_tags as $b_tag) {
            $tag = new FlowTag($b_tag);
            $saved_tags[]=$tag;
            $guids = $tag->get_needed_guids_for_empty_ids();
            foreach ($guids as $guid) {
                $guids_needed[$guid] = true;
            }
        }

        foreach ($this->yaml_diff->changed_tags as $b_tag) {
            $tag = new FlowTag($b_tag);
            $saved_tags[]=$tag;
            $guids = $tag->get_needed_guids_for_empty_ids();
            foreach ($guids as $guid) {
                $guids_needed[$guid] = true;
            }
        }

        $search_params = new GeneralSearchParams();
        $search_params->guids = array_keys($guids_needed);
        $things = GeneralSearch::general_search($search_params,1,GeneralSearch::UNLIMITED_RESULTS_PER_PAGE);

        /**
         * @type array<string,int> $guid_map_to_ids
         */
        $guid_map_to_ids = [];
        foreach ($things as $thing) {
            $guid_map_to_ids[$thing->guid] = $thing->id;
        }

        foreach ($saved_tags as $tag) {
            $tag->fill_ids_from_guids($guid_map_to_ids);
            if (count($tag->get_needed_guids_for_empty_ids())) {
                throw new RuntimeException(
                    "[BriefUpdateFromYaml] Missing some filled guids for tag $tag->flow_tag_guid  $tag->flow_tag_name");
            }
        }

        foreach ($saved_attributes as $att) {
            $att->fill_ids_from_guids($guid_map_to_ids);
            if (count($att->get_needed_guids_for_empty_ids())) {
                throw new RuntimeException(
                    "[BriefUpdateFromYaml] Missing some filled guids for attribute ".
                    "$att->flow_tag_attribute_guid  $att->tag_attribute_name");
            }
        }

        foreach ($saved_applied as $app) {
            $app->fill_ids_from_guids($guid_map_to_ids);
            if (count($app->get_needed_guids_for_empty_ids())) {
                throw new RuntimeException(
                    "[BriefUpdateFromYaml] Missing some filled guids for applied ".
                    "$app->flow_applied_tag_guid ");
            }
        }

        //got all guids in, so lets insert, update, and delete
        foreach ($saved_tags as $tag) {
            $tag->save();
        }

        foreach ($saved_attributes as $att) {
            $att->save();
        }

        foreach ($saved_applied as $app) {
            $app->save();
        }

        //deleting go backwards because do not know what was cherry picked or removed entirely for tag parts

        foreach ($this->yaml_diff->removed_applied as $b_applied) {
            $app = new FlowAppliedTag($b_applied);
            $app->delete_applied();
        }

        foreach ($this->yaml_diff->added_attributes as $b_attribute) {
            $att = new FlowTagAttribute($b_attribute);
            $att->delete_attribute();
        }

        foreach ($this->yaml_diff->added_tags as $b_tag) {
            $tag = new FlowTag($b_tag);
            $tag->delete_tag();
        }



    }


}