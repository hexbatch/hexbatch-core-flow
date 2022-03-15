<?php

namespace app\models\tag\brief;



use app\models\base\FlowBase;
use app\models\project\FlowProject;
use Exception;

class BriefCheckValidYaml extends FlowBase {

    public BriefDiffFromYaml $yaml_diff;
    public FlowProject $project;
    protected int $n_is_valid = 0;
    public array $issues = [];
    public function is_valid():bool { return (bool)$this->n_is_valid; }

    /**
     * Reverse of Brief,
     *      Those marked as added are to be deleted
     *      Those marked as removed will be created
     *      Those marked as modified will be saved
     * @param FlowProject $project
     * @throws Exception
     */
    public function __construct( FlowProject $project){
        $this->n_is_valid = 1;
        $this->project = $project;
        $this->yaml_diff = new BriefDiffFromYaml($this->project,null,true);
        $this->issues = [];

        foreach ($this->yaml_diff->from_yaml_as_brief_tag_map as $bat) {
            $this->n_is_valid &= $bat->has_minimal_information($this->issues);
        }

    }


}