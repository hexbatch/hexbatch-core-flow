<?php
namespace app\models\project\levels;

use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\archive\FlowEntryArchive;
use app\models\project\FlowGitHistory;
use app\models\tag\brief\BriefCheckValidYaml;
use app\models\tag\brief\BriefDiffFromYaml;
use app\models\tag\brief\BriefUpdateFromYaml;
use app\models\user\FlowUser;
use Carbon\Carbon;
use Exception;
use InvalidArgumentException;
use LogicException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class FlowProjectGitLevel extends FlowProjectSettingLevel {

    /**
     * @var FlowGitHistory[] $project_history
     */
    protected array $project_history ;

    /**
     * @param null|array|object $object
     * @throws Exception
     */
    public function __construct($object=null)
    {
        parent::__construct($object);
        $this->project_history = [];
    }


    /**
     * @param int|null $start_at
     * @param int|null $limit
     * @param bool $b_refresh , default false
     * @param bool $b_public , default false
     * @return FlowGitHistory[]
     * @throws Exception
     */
    public function history(?int $start_at = null, ?int $limit = null,  bool $b_refresh= false, bool $b_public = false): array
    {
        if ($b_refresh || empty($this->project_history)) {
            $this->project_history = FlowGitHistory::get_history($this->getFlowProjectFiles()->get_project_directory());
        }
        $history_to_scan = $this->project_history;
        if ($b_public) {
            $public_history = [];
            foreach ($history_to_scan as $history) {
                if ($history->has_changed_public_files()) {
                    $public_history[] = $history;
                }
            }
            $history_to_scan = $public_history;
        }
        if (is_null($start_at) && is_null($limit) ) {
            return $history_to_scan;
        }

        return array_slice($history_to_scan,$start_at,min($limit,count($this->project_history)));

    }


    /**
     * @param bool $b_refresh
     * @return int
     * @throws Exception
     */
    public function count_total_public_history(bool $b_refresh= false) : int {
        $history = $this->history($b_refresh);
        $count = 0;
        foreach ($history as $h) {
            if ($h->has_changed_public_files()) {$count++;}
        }
        return $count;
    }


    /**
     * @return array<string, string>
     * @throws Exception
     */
    public function raw_history(): array
    {
        $this->history();
        return ['log'=>FlowGitHistory::last_log_json()];
    }





    /**
     * @return string
     * @throws Exception
     */
    public function get_tag_yaml_path() : string {
        $dir = $this->getFlowProjectFiles()->get_project_directory();
        if (!is_readable($dir)) {
            throw new LogicException("Project directory of $dir not created before saving tags");
        }

        $tag_yaml_path = $dir . DIRECTORY_SEPARATOR . 'tags.yaml';
        return $tag_yaml_path;
    }

    /**
     * writes a yaml file [tags.yaml] of all the tags, attributes and applied to the project repo base folder
     * will overwrite if already existing
     * does not refresh tags, so make sure is current
     * @throws Exception
     */
    protected function save_tags_to_yaml_in_project_directory()  {
        $tags = $this->get_all_owned_tags_in_project(true,true);

        foreach ($tags as $tag) {
            $tag->set_brief_json_flag(true);
        }

        $pigs_in_space = JsonHelper::toString($tags);
        $tags_serialized = JsonHelper::fromString($pigs_in_space);

        foreach ($tags as $tag) {
            $tag->set_brief_json_flag(false);
        }

        $tag_yaml = Yaml::dump($tags_serialized);

        $tag_yaml_path = $this->get_tag_yaml_path();
        $b_ok = file_put_contents($tag_yaml_path,$tag_yaml);
        if ($b_ok === false) {throw new RuntimeException("Could not write to $tag_yaml_path");}
    }

    /**
     * @throws Exception
     */
    public function reset_project_repo_files() {
        $this->getFlowProjectFiles()->do_git_command('add .');
        $this->getFlowProjectFiles()->do_git_command('reset --hard');
    }


    /**
     * @param string $commit_message
     * @param bool $b_commit
     * @param bool $b_log_message
     * @throws Exception
     */
    public function commit_changes(string $commit_message,bool $b_commit = true,bool $b_log_message = false): void  {

        if ($b_log_message) {
            static::get_logger()->debug(" Commit message",['message'=>$commit_message]);
        }
        if ($b_commit) {
            $this->getFlowProjectFiles()->do_git_command("add .");
            //todo see if anything in staged if not, do not commit
            $this->getFlowProjectFiles()->do_git_command("commit  -m '$commit_message'");
            if (isset($_SESSION[FlowUser::SESSION_USER_KEY])) {
                /**
                 * @var FlowUser $logged_in_user
                 */
                $logged_in_user = $_SESSION[FlowUser::SESSION_USER_KEY];
                $user_info = "$logged_in_user->flow_user_guid <$logged_in_user->flow_user_email>";
                $this->getFlowProjectFiles()->do_git_command("commit --amend --author='$user_info' --no-edit");
            }
            if ($this->getGitExportSettings()->isGitAutomatePush()) {
                $this->push_repo();
            }
        }
    }

    /**
     * returns true if changes and commit made
     * @param bool $b_commit , default true, will only commit if true. If false will write commit message to log
     * @param bool $b_log_message , default false, if true will write commit message to log
     * @throws Exception
     * @return false|string  returns false if no changes, else returns the commit message (regardless if committed)
     */
    protected function save_tag_yaml_and_commit(bool $b_commit = true,bool $b_log_message = false)  {
        $brief_changes = new BriefDiffFromYaml($this); //compare current changes to older saved in yaml

        $this->save_tags_to_yaml_in_project_directory();
        if (!$brief_changes->does_yaml_exist() || !$brief_changes->count_changes()) {
            //reload because now we have yaml (or should)
            $brief_changes = new BriefDiffFromYaml($this);
            if (!$brief_changes->does_yaml_exist()) {
                throw new RuntimeException("[save_tag_yaml_and_commit] cannot write or read yaml file");
            }
        }
        $number_tag_changes = $brief_changes->count_changes();
        $commit_message = null;
        if ($number_tag_changes) {

            $tag_summary = $brief_changes->get_changed_tag_summary_line();
            $attribute_summary = $brief_changes->get_changed_attribute_summary_line();
            $applied_summary = $brief_changes->get_changed_applied_summary_line();
            if ($number_tag_changes === 1) {
                $commit_message = $tag_summary .  $attribute_summary . $applied_summary;
            } else {
                $commit_message = "Did $number_tag_changes tag operations";
                if ($tag_summary) {$commit_message .= "\n$tag_summary";}
                if ($attribute_summary) {$commit_message .= "\n$attribute_summary";}
                if ($applied_summary) {$commit_message .= "\n$applied_summary";}
            }
            $this->commit_changes($commit_message,$b_commit,$b_log_message);

        }

        return $commit_message?? false;

    }

    /**
     * @throws Exception
     */
    public function do_tag_save_and_commit() {
        $this->save_tag_yaml_and_commit();
    }


    /**
     * Saves the private key to a file, and deletes it after, and might take care of ssh local host issues
     * @param string $private_key
     * @param string $to_ssh_url
     * @param string $git_command
     * @return string[]
     * @throws
     */
    protected function do_key_command_with_private_key(string $private_key,string $to_ssh_url,string $git_command) : array {

        WillFunctions::will_do_nothing($to_ssh_url); //reserved for future use
        $temp_file_path = null;
        try {
            //save private key as temp file, and set permissions to owner only
            $temp_file_path = tempnam(sys_get_temp_dir(), 'git-key-');
            file_put_contents($temp_file_path,$private_key);
            $directory = $this->getFlowProjectFiles()->get_project_directory();
            $command = "ssh-agent bash -c ' ".
                "cd $directory; ".
                "ssh-add $temp_file_path; ".
                "git $git_command'".
                " 2>&1";

            /*
             * The way the current linux setup; need to have the base url of the remote in the known hosts first
             * this is done at the dockerfile with
             * (host=github.com; ssh-keyscan -H $host; for ip in $(dig @8.8.8.8 github.com +short); do ssh-keyscan -H $host,$ip; ssh-keyscan -H $ip; done) 2> /dev/null >> /home/www-data/.ssh/known_hosts
             * but should be able to add others as needed by using regex to get the host base url, and running this
             *
             */

            exec($command,$output,$result_code);
            if ($result_code) {
                throw new RuntimeException("Cannot do $git_command,  returned code of $result_code : " . implode("<br>\n",$output));
            }
            return $output;

        } finally {
            if ($temp_file_path) {
                unlink($temp_file_path);
            }
        }
    }



    /**
     * @throws Exception
     */
    public function push_repo() : array  {
        $push_settings = $this->getGitExportSettings();
        if (!$push_settings->getGitUrl()) {
            throw new RuntimeException("Export Repo Url not set");
        }
        if (!$push_settings->getGitSshKey()) {
            throw new RuntimeException("Export Repo Key not set");
        }

        if (!$push_settings->getGitBranch()) {
            throw new RuntimeException("Export Repo Branch not set");
        }

        $command = sprintf("push -u origin %s",$push_settings->getGitBranch());
        //todo get hash of this and hash of origin, see if same, if same do not push and report nothing new to push
        return $this->do_key_command_with_private_key($push_settings->getGitSshKey(),$push_settings->getGitUrl(),$command);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function import_pull_repo_from_git() :array {
        if (!$this->getGitImportSettings()->getGitUrl()) {
            throw new RuntimeException("Import Repo Url not set");
        }
        if (!$this->getGitImportSettings()->getGitSshKey()) {
            throw new RuntimeException("Import Repo Key not set");
        }

        if (!$this->getGitImportSettings()->getGitBranch()) {
            throw new RuntimeException("Import Repo Branch not set");
        }

        $old_head = $this->getFlowProjectFiles()->get_head_commit_hash();
        //todo get hash of import, see if same, if same just report nothing to change
        try {
            $this->getFlowProjectFiles()->do_git_command('reset --hard'); //clear up any earlier bugs or crashes
        } catch (Exception $e) {
            $message = "Could not do a hard reset";
            $message.="<br>{$e->getMessage()}\n";
            throw new RuntimeException($message);
        }

        $command = "pull import ".$this->getGitImportSettings()->getGitBranch();
        try {
            $git_ret =  $this->do_key_command_with_private_key(
                $this->getGitImportSettings()->getGitSshKey(),
                $this->getGitImportSettings()->getGitUrl(),
                $command);
        } catch (Exception $e) {
            $maybe_changes = $this->getFlowProjectFiles()->do_git_command('diff');
            $message = $e->getMessage();
            if (!empty(trim($maybe_changes))) {
                try {
                    $this->getFlowProjectFiles()->do_git_command('merge --abort');
                    $message.="<br>Aborted Merge\n";
                } catch (Exception $oh_no) {
                    $message.="<br>{$oh_no->getMessage()}\n";
                }

            }
            throw new RuntimeException($message);
        }

        $new_head = $this->getFlowProjectFiles()->get_head_commit_hash();
        try {
            $this->check_integrity();
            $this->set_db_from_file_state();
        } catch (Exception $e) {
            //do not use commit just imported, and remove any changes to files
            if ($old_head !== $new_head) {
                $this->getFlowProjectFiles()->do_git_command("reset --hard $old_head");
            }
            throw $e;
        }

        return $git_ret;
    }

    function update_repo_from_file(UploadedFileInterface $uploaded_file) :string {
        WillFunctions::will_do_nothing($uploaded_file);
        return 'stub updated repo from file';
    }

    /**
     * @throws Exception
     */
    protected function check_integrity()  {
        $this->do_project_directory_command('stat flow_project_blurb');
        $this->do_project_directory_command('stat flow_project_title');
        $this->do_project_directory_command('stat flow_project_readme_bb_code.bbcode');
        $title = $this->do_project_directory_command('cat flow_project_title');
        static::check_valid_title($title);
        $blurb = $this->do_project_directory_command('cat flow_project_blurb');
        if (mb_strlen($blurb) > static::MAX_SIZE_BLURB) {
            throw new InvalidArgumentException("Project Blurb cannot be more than ".static::MAX_SIZE_BLURB." characters");
        }
        $valid_tags = new BriefCheckValidYaml($this);
        if (!$valid_tags->is_valid()) {
            throw new InvalidArgumentException("tags.yaml does not have minimal information for each thing in it: \n<br>".
                implode("\n<br>",$valid_tags->issues));
        }
    }

    /**
     * @throws Exception
     */
    protected function set_db_from_file_state()  {
        $this->flow_project_blurb = $this->do_project_directory_command('cat flow_project_blurb');
        $this->flow_project_title = $this->do_project_directory_command('cat flow_project_title');
        $this->flow_project_readme_bb_code = $this->do_project_directory_command('cat flow_project_readme_bb_code.bbcode');
        $this->set_read_me($this->flow_project_readme_bb_code);

        $db = null;
        try {
            $db = static::get_connection();
            $db->beginTransaction();

            $db->update('flow_projects',[
                'flow_project_type' => $this->flow_project_type,
                'flow_project_title' => $this->flow_project_title,
                'flow_project_blurb' => $this->flow_project_blurb,
                'flow_project_readme' => $this->flow_project_readme,
                'flow_project_readme_bb_code' => $this->flow_project_readme_bb_code,
            ],[
                'id' => $this->id
            ]);

            FlowEntryArchive::update_all_entries_from_project_directory($this);

            $tags = new BriefUpdateFromYaml($this);
            WillFunctions::will_do_nothing($tags); //for debugging

            $db->commit();


        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            static::get_logger()->alert("Project model cannot update from file stete ",['exception'=>$e]);
            throw $e;
        }



    }



    public function save(bool $b_do_transaction = true): void
    {
        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) { $db->beginTransaction();}
            parent::save(false);

            $b_already_created = false;
            $dir = $this->getFlowProjectFiles()->get_project_directory($b_already_created);
            $make_first_commit = !$b_already_created;

            $yaml_path = $dir . DIRECTORY_SEPARATOR . 'flow_project.yaml';



            $yaml_array = [
                'timestamp' => time(),
                'flow_project_guid' => $this->flow_project_guid,
                'title' => $this->flow_project_title,
                'author' => $this->get_admin_user()->flow_user_name,
                'human_date_time' => Carbon::now()->toIso8601String()
            ];

            $yaml = Yaml::dump($yaml_array);
            $b_ok = file_put_contents($yaml_path,$yaml);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $yaml_path");}

            if ($make_first_commit) {
                $commit_title = "First Commit";
                $this->getFlowProjectFiles()->do_git_command("add .");
                $this->getFlowProjectFiles()->do_git_command("commit  -m '$commit_title'");
            }




            $b_did_title_change = ($this->flow_project_title !== $this->old_flow_project_title);
            $b_did_blurb_change = ($this->flow_project_blurb !== $this->old_flow_project_blurb);
            $b_did_readme_bb_change = ($this->flow_project_readme_bb_code !== $this->old_flow_project_readme_bb_code);

            $commit_title_array = [];
            $commit_message_array = [];
            if ($b_did_title_change) {
                $commit_message_array[] = "Updated Project Title"; $commit_title_array[] = "Project Title";}
            if ($b_did_blurb_change) { $commit_message_array[] = "Updated Project Blurb"; $commit_title_array[] = "Project Blurb";}
            if ($b_did_readme_bb_change) { $commit_message_array[] = "Updated Project Read Me"; $commit_title_array[] = "Project Description";}

            $tag_changes_message_or_false = $this->save_tag_yaml_and_commit(false);
            if ($tag_changes_message_or_false) {
                $commit_message_array[] = $tag_changes_message_or_false;
                $commit_title_array[] = "Tags";
            }

            if (empty($commit_title_array) && empty($commit_message_array)) {
                return ; //nothing to commit
            }
            $this->getFlowProjectFiles()->do_git_command("add .");

            $commit_title = implode('; ',$commit_title_array);
            $commit_body = implode('\n',$commit_message_array);

            $commit_message_full =  "$commit_title\n\n$commit_body";

            //if any ` in there, then escape them
            $commit_message_full = str_replace("'","\'",$commit_message_full);



            $this->getFlowProjectFiles()->do_git_command("commit  -m '$commit_message_full'");

            if (isset($_SESSION[FlowUser::SESSION_USER_KEY])) {
                /**
                 * @var FlowUser $logged_in_user
                 */
                $logged_in_user = $_SESSION[FlowUser::SESSION_USER_KEY];
                $user_info = "$logged_in_user->flow_user_guid <$logged_in_user->flow_user_email>";
                $this->getFlowProjectFiles()->do_git_command("commit --amend --author='$user_info' --no-edit");
            }


            if ($b_do_transaction && $db->inTransaction()) { $db->commit();}
        } catch (Exception $e) {
            if ($b_do_transaction && $db->inTransaction()) { $db->rollBack();}
            throw $e;
        }

        if ($this->getGitExportSettings()->isGitAutomatePush()) {
            $this->push_repo();
        }
    }


}