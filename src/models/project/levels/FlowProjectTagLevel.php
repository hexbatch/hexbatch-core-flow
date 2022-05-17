<?php
namespace app\models\project\levels;

use app\models\base\SearchParamBase;
use app\models\tag\FlowTag;
use app\models\tag\FlowTagSearch;
use app\models\tag\FlowTagSearchParams;
use Exception;

abstract class FlowProjectTagLevel extends FlowProjectFileLevel {


    /**
     * @var FlowTag[]|null $owned_tags
     */
    protected ?array $owned_tags = null;

    protected bool $b_did_applied_for_owned_tags = false;

    /**
     * @param null|array|object $object
     * @throws Exception
     */
    public function __construct($object=null)
    {
        parent::__construct($object);
        $this->b_did_applied_for_owned_tags = false;
        $this->owned_tags = null;
    }

    /**
     * @param bool $b_get_applied  if true will also get the applied in the set of tags found
     * @param bool $b_refresh  if true will not use previous value if set
     * @return FlowTag[]
     * @throws Exception
     */
    public function get_all_owned_tags_in_project(bool $b_get_applied = false,bool $b_refresh = false) : array {
        if (!$b_refresh && is_array($this->owned_tags)) {
            //refresh cache if first time getting applied
            if ($b_get_applied && $this->b_did_applied_for_owned_tags) {
                return $this->owned_tags;
            }
        }
        $search_params = new FlowTagSearchParams();
        $search_params->flag_get_applied = $b_get_applied;
        $search_params->setOwningProjectGuid($this->flow_project_guid);

        $search_params->setPage(1);
        $search_params->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
        $tag_search = new FlowTagSearch();
        $unsorted_array = $tag_search->get_tags($search_params)->get_found_tags();

        $this->owned_tags = FlowTagSearch::sort_tag_array_by_parent($unsorted_array);

        if ($b_get_applied) {
            $this->b_did_applied_for_owned_tags = true;
        }

        return $this->owned_tags;
    }

    /**
     * @var FlowTag[]|null $tags_applied_to_this
     */
    protected ?array $tags_applied_to_this = null;

    
    /**
     * @param bool $b_refresh  if true will not use previous value if set
     * @return FlowTag[]
     * @throws Exception
     */
    public function get_applied_tags(bool $b_refresh = false) : array {
        if (!$b_refresh && is_array($this->tags_applied_to_this)) {
            //refresh cache if first time getting applied
            return $this->tags_applied_to_this;
        }
        $search_params = new FlowTagSearchParams();
        $search_params->flag_get_applied = true;
        $search_params->setOwningProjectGuid($this->flow_project_guid);
        $search_params->only_applied_to_guids[] = $this->flow_project_guid;

        $search_params->setPage(1);
        $search_params->setPageSize(SearchParamBase::UNLIMITED_RESULTS_PER_PAGE);
        $tag_search = new FlowTagSearch();
        $this->tags_applied_to_this = $tag_search->get_tags($search_params)->get_found_tags();
        return $this->tags_applied_to_this;
    }



    /**
     * @param string $name
     * @return FlowTag
     * @throws Exception
     */
    protected function get_tag_by_name(string $name) : FlowTag {
        $all_tags = $this->get_all_owned_tags_in_project();
        foreach ($all_tags as $tag) {
            if ($tag->flow_tag_name === $name) { return $tag;}
        }
        $baby_steps = new FlowTag();
        $baby_steps->flow_project_id = $this->id;
        $baby_steps->flow_tag_name = $name;
        $baby_steps->save();
        return $baby_steps->clone_refresh();
    }

    public function destroy_project(bool $b_do_transaction = true): void {
        parent::destroy_project($b_do_transaction);
        $this->owned_tags = null;
    }

}