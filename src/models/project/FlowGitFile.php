<?php
namespace app\models\project;


use Exception;

class FlowGitFile {


    public string $project_path;

    public string $commit;

    /**
     * @var ?string $file
     */
    public ?string $file;

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
            case 'flow_project_blurb': { $this->is_public = true ; return 'Project Blurb'; }
            case 'flow_project_title': { $this->is_public = true ; return 'Project Title'; }
            case 'tags.yaml': { $this->is_public = true ; return 'Tags'; }
            default: {
                if ($this->is_valid_resource_file()) {
                    $this->is_public = true ; return $this->get_resouce_file_name();
                } else {
                    $this->is_public = false; return '';
                }

            }
        }
    }

    protected function is_valid_resource_file() : bool{
        if (strpos($this->file,FlowProject::REPO_FILES_DIRECTORY.DIRECTORY_SEPARATOR) !== false) {return true;}
        if (strpos($this->file,FlowProject::REPO_RESOURCES_DIRECTORY.DIRECTORY_SEPARATOR) !== false) {return true;}
        return false;
    }

    protected function get_resouce_file_name() : ?string  {
        return $this->file;
    }

    /**
     * @param bool $b_show_all, default true if false will show trimmed
     * @return string
     * @throws Exception
     */
    public function show_diff(bool $b_show_all = true) : string {
        $file_diff_command = "diff $this->commit^! $this->file";
        $raw_diff = FlowGitHistory::do_git_command($this->project_path,$file_diff_command);
        if ($b_show_all) {return $raw_diff;}
        $raw_array = explode("\n",$raw_diff);
        $raw_array_reversed = array_reverse($raw_array);
        $output_as_array_reversed = [];
        foreach ($raw_array_reversed as $thing) {
            if (strpos($thing,"\ No newline at end of file") !== false) {continue;}
            if (strpos($thing,"+++ b/$this->file") !== false) {break;}
            $output_as_array_reversed[] = $thing;
        }

        $output_as_array = array_reverse($output_as_array_reversed);
        $ret = implode("\n",$output_as_array);
        return $ret;
    }
}