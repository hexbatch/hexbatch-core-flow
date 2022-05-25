<?php

namespace app\models\tag\brief;



use app\models\base\FlowBase;
use app\models\base\SearchParamBase;
use app\models\multi\GeneralSearch;
use app\models\multi\GeneralSearchParams;
use app\models\project\IFlowProject;
use app\models\tag\FlowAppliedTag;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagAttribute;
use app\models\tag\IFlowAppliedTag;
use app\models\tag\IFlowTagAttribute;
use Exception;
use RuntimeException;

class BriefUpdateFromYaml extends FlowBase {

    public BriefDiffFromYaml $yaml_diff;
    public IFlowProject $project;

    /**
     * Reverse of Brief,
     *      Those marked as added are to be deleted
     *      Those marked as removed will be created
     *      Those marked as modified will be saved
     * @param IFlowProject $project
     * @throws Exception
     */
    public function __construct( IFlowProject $project){
        parent::__construct();
        $this->project = $project;
        $this->yaml_diff = new BriefDiffFromYaml($this->project,null,true);

        //get ids for the removed and modified

        /**
         * @var array<string,string> $guids_needed
         */
        $guids_needed = [];

        /**
         * @var IFlowAppliedTag[] $saved_applied
         */
        $saved_applied = [];


        /**
         * @var IFlowTagAttribute[] $saved_attributes
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

        if (count($guids_needed)) {
            $search_params = new GeneralSearchParams();
            $search_params->guids = array_keys($guids_needed);

            $search_params->setPage(1);
            $search_params->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
            $things = GeneralSearch::general_search($search_params);

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
                    $err_name = $tag->getName();
                    $err_guid = $tag->getGuid();
                    throw new RuntimeException(
                        "[BriefUpdateFromYaml] Missing some filled guids for tag $err_guid  $err_name");
                }
            }

            //might need tag guids from tags not saved yet (will not be in the results from above)
            //so save tags without the children, then fill in the guid map, can save again below if skipping this step
            foreach ($saved_tags as $tag) {
                $tag->save();
                $guid_map_to_ids[$tag->getGuid()] = $tag->getID();
            }


            foreach ($saved_attributes as $att) {
                $att->fill_ids_from_guids($guid_map_to_ids);
                if (count($att->get_needed_guids_for_empty_ids())) {
                    throw new RuntimeException(
                        sprintf("[BriefUpdateFromYaml] Missing some filled guids for attribute %s %s ",
                        $att->getGuid() , $att->getName())
                    );
                }
            }

            foreach ($saved_applied as $app) {
                $app->fill_ids_from_guids($guid_map_to_ids);
                if (count($app->get_needed_guids_for_empty_ids())) {
                    throw new RuntimeException(
                        "[BriefUpdateFromYaml] Missing some filled guids for applied ".
                        $app->getGuid() );
                }
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

        foreach ($this->yaml_diff->added_applied as $b_applied) {
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