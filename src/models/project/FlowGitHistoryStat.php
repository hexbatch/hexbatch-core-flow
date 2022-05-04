<?php
namespace app\models\project;


/*
 * git --no-pager log --stat -n30000 --no-color
 */

/*
commit 205c5add57482498a915abf764cce981814f61f0 (HEAD -> master)
Author: 11EBE1D3BC0292BFB6380242AC130005 <willwoodlief+help@gmail.com>
Date:   Thu Aug 12 19:41:10 2021 +0000

    Changed Project

    Updated Project Blurb

 flow_project.yaml      | 2 +-
 flow_project_blurb | 2 +-
 2 files changed, 2 insertions(+), 2 deletions(-)
(blank line at end of commit)
 */

use Exception;

class FlowGitHistoryStat {

    public string $commit;
    /**
     * @var FlowGitFile[] $files
     */
    public array $files = [];
    /**
     * @param string $project_path
     * @return FlowGitHistoryStat[]
     * @throws Exception
     */
    public static function get_stats(string $project_path) :array {
        $changed_files_command = '--no-pager log --stat -n30000 --no-color ';
        $raw_log = FlowGitHistory::do_git_command($project_path,$changed_files_command);
        $raw_log_parts = explode(")\n\ncommit",$raw_log);

        $ret = [];
        foreach ($raw_log_parts as $raw_thing) {
            $raw_array = explode("\n",$raw_thing);
            if (str_contains($raw_array[0], 'commit')) {
                $raw_array[0] = str_replace('commit ','',$raw_array[0]);
            }
            $node = new FlowGitHistoryStat();
            $node->commit = trim($raw_array[0]);
            $raw_array_reversed = array_reverse($raw_array);
            for($i=1; $i < count($raw_array_reversed); $i++) {
                if (!str_contains($raw_array_reversed[$i], ' | ')) {break;}
                $file_parts =  preg_split('/\s+/',$raw_array_reversed[$i]);
                if (empty($file_parts[0])) {$file_path =$file_parts[1]; }
                else { $node->files[] = $file_path =$file_parts[0];}

                $node->files[] = new FlowGitFile($project_path,$node->commit,$file_path);
            }
            $ret[] = $node;
        }
        return $ret;
    }
}