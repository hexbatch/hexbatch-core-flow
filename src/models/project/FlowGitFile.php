<?php
namespace app\models\project;


use Exception;

class FlowGitFile {


    public string $project_path;

    public string $commit;

    /**
     * @var string $file
     */
    public string $file;

    /**
     * @var string $file
     */
    public string $short_name;

    public bool $is_public = false;

    public function __construct($project_path,$commit,$file){
        $this->project_path = $project_path;
        $this->commit = $commit;
        $this->file = $file;
        $this->short_name = $this->get_short_name();
    }

    protected function get_short_name() : string {
        switch ($this->file) {
            case 'flow_project_readme_bb_code.bbcode': { $this->is_public = true ; return 'Project Read Me'; }
            case 'flow_project_blurb.txt': { $this->is_public = true ; return 'Project Blurb'; }
            case 'flow_project_title.txt': { $this->is_public = true ; return 'Project Title'; }
            default: {$this->is_public = false; return '';}
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function show_diff() : string {
        $file_diff_command = "diff $this->commit $this->file";
        $raw_diff = FlowGitHistory::do_git_command($this->project_path,$file_diff_command);
        $raw_array = explode("\n",$raw_diff);
        $raw_array_reversed = array_reverse($raw_array);
        $output_as_array_reversed = [];
        foreach ($raw_array_reversed as $thing) {
            if (strpos($thing,"+++ b/$this->file") !== false) {break;}
            $output_as_array_reversed[] = $thing;
        }

        $output_as_array = array_reverse($output_as_array_reversed);
        $ret = implode("\n",$output_as_array);
        return $ret;
    }
}