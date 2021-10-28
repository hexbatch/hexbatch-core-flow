<?php
namespace app\models\project;
/*
 * git --no-pager log -n30000 --pretty=format:'{%n  "commit": "%H",%n  "abbreviated_commit": "%h",%n  "tree": "%T",%n  "abbreviated_tree": "%t",%n  "parent": "%P",%n  "abbreviated_parent": "%p",%n  "refs": "%D",%n  "encoding": "%e",%n  "subject": "%s",%n  "sanitized_subject_line": "%f",%n  "body": "%b",%n  "commit_notes": "%N",%n  "verification_flag": "%G?",%n  "signer": "%GS",%n  "signer_key": "%GK",%n  "author": {%n    "name": "%aN",%n    "email": "%aE",%n    "date": "%at"%n  },%n  "commiter": {%n    "name": "%cN",%n    "email": "%cE",%n    "date": "%ct"%n  }%n},'
 *
 * without newlines or trailing comma, and different quotes
 * git --no-pager log -n30000 --pretty=format:'{   ||qq|| commit ||qq|| :  ||qq|| %H ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| %h ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| %T ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| %t ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| %P ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| %p ||qq|| ,   ||qq|| refs ||qq|| :  ||qq|| %D ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq|| %e ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| %s ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| %f ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| %b ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq|| %N ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| %G? ||qq|| ,   ||qq|| signer ||qq|| :  ||qq|| %GS ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq|| %GK ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| %aN ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| %aE ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| %at ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| %cN ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| %cE ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| %ct ||qq||   }}::end::' |  tr '\n' '||nn||' | tr '"' '\\"'
 *
 */

/*
{   ||qq|| commit ||qq|| :  ||qq|| 205c5add57482498a915abf764cce981814f61f0 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| 205c5ad ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 227c371795a7fda9bbd8f7f91bfe83f4af969fe4 ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 227c371 ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| 3fb9d53f4e20119daa7e3750e117113a8967e46b ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| 3fb9d53 ||qq|| ,   ||qq|| refs ||qq|| :  ||qq|| HEAD -> master ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| 11EBE1D3BC0292BFB6380242AC130005 ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| willwoodlief+help@gmail.com ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797270 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797278 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| 3fb9d53f4e20119daa7e3750e117113a8967e46b ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| 3fb9d53 ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 85c177adc9a816f0fd42f46394eef23e487b89b6 ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 85c177a ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| ee8e29e4cf71e1826198bcbe1d545ff5feff0166 ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| ee8e29e ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797203 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797203 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| ee8e29e4cf71e1826198bcbe1d545ff5feff0166 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| ee8e29e ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| eaac2ae3ceabcfcfae93174f668604160bc8ec17 ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| eaac2ae ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| d8d07ec002f263dac4f68fe1c5d611cfe7d0cf28 ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| d8d07ec ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797162 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628797162 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| d8d07ec002f263dac4f68fe1c5d611cfe7d0cf28 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| d8d07ec ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| b9d84fdcd1a064642a1d3f8dad243a25894b0020 ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| b9d84fd ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| bb8e0c3de357112ea3e441cab404004dad34c206 ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| bb8e0c3 ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628796860 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628796860 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| bb8e0c3de357112ea3e441cab404004dad34c206 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| bb8e0c3 ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 89ad5401fdb869b8ffee865ff85f7bbc8ae89eaa ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 89ad540 ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| 6ca0fb1f74cd197226af0703f6ad3fbc019f7d7b ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| 6ca0fb1 ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Changed Project ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Changed-Project ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| Updated Project Blurb| ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628796572 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628796572 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| 6ca0fb1f74cd197226af0703f6ad3fbc019f7d7b ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| 6ca0fb1 ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 3677a4ebda8625a5759dcd6070d0a758ceece1dc ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 3677a4e ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| 2f7f44fcb08e7c6fdce4d1031892a406554901a5 ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| 2f7f44f ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Auto Commit ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Auto-Commit ||qq|| ,   ||qq|| body ||qq|| :  ||qq||  ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628698994 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628698994 ||qq||   }}::end::|{   ||qq|| commit ||qq|| :  ||qq|| 2f7f44fcb08e7c6fdce4d1031892a406554901a5 ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| 2f7f44f ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| 79a92c8b83832a1a557f8c7d5adeaa0644c0f27a ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| 79a92c8 ||qq|| ,   ||qq|| parent ||qq|| :  ||qq||  ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq||  ||qq|| ,   ||qq|| refs ||qq|| :  ||qq||  ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq||  ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| Auto Commit ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| Auto-Commit ||qq|| ,   ||qq|| body ||qq|| :  ||qq||  ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq||  ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| N ||qq|| ,   ||qq|| signer ||qq|| :  ||qq||  ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq||  ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628698713 ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| www-data ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| www-data@048adf2db077 ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| 1628698713 ||qq||   }}::end::
*/

//example output
/*
{
  "commit": "205c5add57482498a915abf764cce981814f61f0",
  "abbreviated_commit": "205c5ad",
  "tree": "227c371795a7fda9bbd8f7f91bfe83f4af969fe4",
  "abbreviated_tree": "227c371",
  "parent": "3fb9d53f4e20119daa7e3750e117113a8967e46b",
  "abbreviated_parent": "3fb9d53",
  "refs": "HEAD -> master",
  "encoding": "",
  "subject": "Changed Project",
  "sanitized_subject_line": "Changed-Project",
  "body": "Updated Project Blurb",
  "commit_notes": "",
  "verification_flag": "N",
  "signer": "",
  "signer_key": "",
  "author": {
    "name": "11EBE1D3BC0292BFB6380242AC130005",
    "email": "willwoodlief+help@gmail.com",
    "date": "1628797270"
  },
  "commiter": {
    "name": "www-data",
    "email": "www-data@048adf2db077",
    "date": "1628797278"
  }
}

 */




/*
 * tags
 * git show-ref --tags -d
 * //the line ending with the ^{} is the commit hash for annotated tags, light tags only have the one line for commit; discard dupliate tagname with no {}
205c5add57482498a915abf764cce981814f61f0 refs/tags/BBB
ee9620b8189e9efc59c665afab141a0a9f245c4d refs/tags/v1.4
205c5add57482498a915abf764cce981814f61f0 refs/tags/v1.4^{}

extra
19:38 $ git for-each-ref refs/tags
205c5add57482498a915abf764cce981814f61f0 commit	refs/tags/BBB
ee9620b8189e9efc59c665afab141a0a9f245c4d tag	refs/tags/v1.4

 */

use app\hexlet\JsonHelper;
use app\models\user\FlowUser;
use Exception;
use RuntimeException;

class FlowGitHistory {

    public ?string $project_guid;
    public ?string $commit;
    public ?string $abbreviated_commit;
    public ?string $tree;
    public ?string $abbreviated_tree;
    public ?string $parent;
    public ?string $abbreviated_parent;
    public ?string $refs;
    public ?string $subject;
    public ?string $body;
    public ?string $author_guid;
    public ?string $author_email;

    public ?string $commit_ts;


    public ?FlowUser $author_object;

    public function get_author() {
        if (empty($this->author_object)) {
            $this->author_object = new FlowUser();
        }
        return $this->author_object;
    }

    public function has_changed_public_files() : bool {
        if (empty($this->changed_files)) {
            return false;
        }
        foreach ($this->changed_files as $file) {
            if ($file->is_public) {return true;}
        }
        return false;
    }

    /**
     * @var FlowGitFile[] $changed_files
     */
    public array $changed_files = [];

    /**
     * @var string[] $tags
     */
    public array $tags = [];

    /**
     * @var string[] $tags
     */
    public array $branches = [];

    public bool $is_head = false;



    protected static string $last_log_json;
    public static function last_log_json() :string { return static::$last_log_json;}

    public function __construct($object=null){

        if (empty($object)) {
            foreach ($this as $key => $val) {
                if (!is_array($this->$key) && !is_bool($this->$key)) {
                    $this->$key = null;
                }
            }
            return;
        }

        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

        $refs_themselves = explode(',',$this->refs);
        foreach ($refs_themselves as $a_ref) {
            $a_ref = trim($a_ref);
            if (strpos($a_ref,'tag') !== false) {
                $tag_parts = explode(' ',$a_ref);
                array_shift($tag_parts);
                $tag = trim(join('',$tag_parts));
                $this->tags[] = $tag;
            } else {
                if (strpos($a_ref,'HEAD ->') !== false) {
                    $this->is_head = true;
                }

                $maybe_branch =  trim(str_replace('HEAD ->','',$a_ref));
                if ($maybe_branch) {
                    $this->branches[] = $maybe_branch;
                }
            }
        }

        $this->body = str_replace("|","\n",$this->body);

    }

    /**
     * @param string $project_path
     * @return FlowGitHistory[]
     * @throws Exception
     */
    public static function get_history(string $project_path) :array {

        $path_parts = explode(DIRECTORY_SEPARATOR,trim($project_path,DIRECTORY_SEPARATOR));
        $project_guid = end($path_parts);
        $log_command = <<<END
          --no-pager log -n30000 --pretty=format:'{   ||qq|| commit ||qq|| :  ||qq|| %H ||qq|| ,   ||qq|| abbreviated_commit ||qq|| :  ||qq|| %h ||qq|| ,   ||qq|| tree ||qq|| :  ||qq|| %T ||qq|| ,   ||qq|| abbreviated_tree ||qq|| :  ||qq|| %t ||qq|| ,   ||qq|| parent ||qq|| :  ||qq|| %P ||qq|| ,   ||qq|| abbreviated_parent ||qq|| :  ||qq|| %p ||qq|| ,   ||qq|| refs ||qq|| :  ||qq|| %D ||qq|| ,   ||qq|| encoding ||qq|| :  ||qq|| %e ||qq|| ,   ||qq|| subject ||qq|| :  ||qq|| %s ||qq|| ,   ||qq|| sanitized_subject_line ||qq|| :  ||qq|| %f ||qq|| ,   ||qq|| body ||qq|| :  ||qq|| %b ||qq|| ,   ||qq|| commit_notes ||qq|| :  ||qq|| %N ||qq|| ,   ||qq|| verification_flag ||qq|| :  ||qq|| %G? ||qq|| ,   ||qq|| signer ||qq|| :  ||qq|| %GS ||qq|| ,   ||qq|| signer_key ||qq|| :  ||qq|| %GK ||qq|| ,   ||qq|| author ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| %aN ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| %aE ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| %at ||qq||   },   ||qq|| commiter ||qq|| : {     ||qq|| name ||qq|| :  ||qq|| %cN ||qq|| ,     ||qq|| email ||qq|| :  ||qq|| %cE ||qq|| ,     ||qq|| date ||qq|| :  ||qq|| %ct ||qq||   }}||end||' |  tr '\n' '||nn||' | tr '"' '\\"'
        END;
        $log_command = trim($log_command);
        $raw_log = static::do_git_command($project_path,$log_command);
        $log_with_double_quotes = str_replace(' ||qq|| ','"',$raw_log);
        $log_with_commas = str_replace('||end||',',',$log_with_double_quotes);
        $fix_for_comma_bar = str_replace(',|',',',$log_with_commas);
        $trim_trailing_comma = trim($fix_for_comma_bar,', ');
        $json_log = '['.$trim_trailing_comma.']';
        static::$last_log_json = $json_log;
        $what = JsonHelper::fromString($json_log);

        $ret = [];
        /**
         * @var array<string,FlowGitHistory> $hash
         */

        $files_changed = FlowGitHistoryStat::get_stats($project_path);
        $file_changed_hash = [];
        foreach ($files_changed as $changed) {
            $file_changed_hash[$changed->commit] = $changed->files;
        }


        /**
         * @var array<string,FlowGitHistory> $user_guid_hash
         */
        $user_guid_hash = [];

        foreach ($what as $when) {
            $when['author_guid'] = isset($when['author'])? ($when['author']['name'] ?? null) : null  ;
            $when['author_email'] = isset($when['author'])? ($when['author']['email'] ?? null) : null  ;
            $when['commit_ts'] = isset($when['commiter'])? ($when['commiter']['date'] ?? null) : null  ;
            $when['project_guid'] = $project_guid;
            $node = new FlowGitHistory($when);
            if (array_key_exists($node->commit,$file_changed_hash)) {
                $node->changed_files = $file_changed_hash[$node->commit];
            }
            if (!isset($user_guid_hash[$node->author_guid])) {$user_guid_hash[$node->author_guid] = [];}
            $user_guid_hash[$node->author_guid][] = $node;
            $ret[] = $node;
        }

        //get user info
        $users = FlowUser::get_basic_info_by_guid_array(array_keys($user_guid_hash));
        foreach ($users as $user) {
            if (isset($user_guid_hash[$user->flow_user_guid])) {

                /**
                 * @var FlowGitHistory $history
                 */
                foreach ($user_guid_hash[$user->flow_user_guid] as $history) {
                    $history->author_object = $user;
                }
            }
        }





        return $ret;
    }

    /**
     * @param string $directory
     * @param string $command
     * @param bool $b_include_git_word, default true
     * @param string|null $pre_command
     * @return string
     */
    public static function do_git_command(string $directory, string $command,bool $b_include_git_word = true, ?string $pre_command = null) : string {
        if (empty($pre_command)) {$pre_command = '';}
        $git_word = '';
        if ($b_include_git_word) {
            $git_word = 'git';
        }
        exec("$pre_command cd $directory && $git_word $command 2>&1",$output,$result_code);
        if ($result_code) {
            throw new RuntimeException("Git returned code of $result_code : " . implode("\n",$output));
        }
        return  implode("\n",$output);
    }



}