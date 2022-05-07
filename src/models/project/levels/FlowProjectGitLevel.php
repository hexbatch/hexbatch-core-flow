<?php
namespace app\models\project\levels;

use app\helpers\ProjectHelper;
use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\entry\archive\FlowEntryArchive;
use app\models\entry\FlowEntryYaml;
use app\models\project\exceptions\FlowProjectGitException;
use app\models\project\exceptions\NothingToPullException;
use app\models\project\exceptions\NothingToPushException;
use app\models\project\FlowGitHistory;
use app\models\project\FlowProject;
use app\models\project\IFlowProject;
use app\models\project\setting_models\FlowProjectGitSettings;
use app\models\tag\brief\BriefCheckValidYaml;
use app\models\tag\brief\BriefDiffFromYaml;
use app\models\tag\brief\BriefUpdateFromYaml;
use app\models\user\FlowUser;
use Exception;
use InvalidArgumentException;

use JetBrains\PhpStorm\ArrayShape;
use LogicException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class FlowProjectGitLevel extends FlowProjectSettingLevel {

    const IMPORT_REMOTE_NAME = 'import';
    const PUSH_REMOTE_NAME = 'origin';
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
            $this->project_history = FlowGitHistory::get_history($this->get_project_directory());
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
    #[ArrayShape(['log' => "string"])]
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
        $dir = $this->get_project_directory();
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
    public function reset_project_repo_files(?string $commit_hash = null) {
        $this->do_git_command('add .');
        if (empty($commit_hash)) {
            $commit_part = '';
        } else {
            $commit_part = $commit_hash;
        }
        $this->do_git_command("reset --hard $commit_part");
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
            $old_head = null;
            try {
                $old_head = $this->get_head_commit_hash();
                $this->do_git_command("add .");

                if (!$this->are_files_dirty()) {
                    throw new NothingToPushException("No tracked files changed, will not try to push");
                }

                //setup repo author and email
                //git config --global user.email "you@example.com"
                //git config --global user.name "Your Name"
                $current_user = ProjectHelper::get_project_helper()->get_current_user();
                if (!$current_user) {throw new RuntimeException("Cannot save, nobody logged in");}

                $author_email_command = sprintf('config  user.email "%s"',$this->get_admin_user()->flow_user_email);
                $this->do_git_command($author_email_command);

                $author_name_command = sprintf('config  user.name "%s"',$this->get_admin_user()->flow_user_name);
                $this->do_git_command($author_name_command);


                $this->do_git_command("commit  -m '$commit_message'");
                $user_info = "$current_user->flow_user_guid <$current_user->flow_user_email>";
                $this->do_git_command("commit --amend --author='$user_info' --no-edit");
                if ($this->getGitExportSettings()->isGitAutomatePush()) {
                    $this->push_repo();
                }
            } catch (Exception $e) {
                if ($old_head) {
                    $this->reset_project_repo_files($old_head); //reset -- hard to previous
                }

                throw $e;
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
    protected function save_tag_yaml_and_commit(bool $b_commit = true,bool $b_log_message = false): false|string
    {
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
            try {
                $this->commit_changes($commit_message,$b_commit,$b_log_message);
            } catch (NothingToPushException ) {
                //ignore if no file changes
            }


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
     * @param FlowProjectGitSettings $settings
     * @param string $git_command
     * @param bool $b_create_directory_if_empty
     * @param bool $b_cd_into_project
     * @return string[]
     * @throws Exception
     */
    protected function do_git_command_with_settings(FlowProjectGitSettings $settings, string $git_command,
                                                    bool $b_create_directory_if_empty = true,
                                                    bool $b_cd_into_project = true

    ) : array {

        $private_key = $settings->getGitSshKey();

        if ($private_key) {
            $temp_file_path = null;
            try {
                //save private key as temp file, and set permissions to owner only
                $temp_file_path = tempnam(sys_get_temp_dir(), 'git-key-');
                file_put_contents($temp_file_path, $private_key);
                if ($b_create_directory_if_empty) {
                    $directory = $this->get_project_directory();
                } else {
                    $directory = $this->get_calculated_project_directory();
                }

                $cd_command = '';
                if ($b_cd_into_project) {
                    $cd_command = "cd $directory; ";
                }

                $command = "ssh-agent bash -c ' " .
                    $cd_command .
                    "ssh-add $temp_file_path; " .
                    "git $git_command'" .
                    " 2>&1";

                /*
                 * The way the current linux setup; need to have the base url of the remote in the known hosts first
                 * this is done at the dockerfile with
                 * (host=github.com; ssh-keyscan -H $host; for ip in $(dig @8.8.8.8 github.com +short); do ssh-keyscan -H $host,$ip; ssh-keyscan -H $ip; done) 2> /dev/null >> /home/www-data/.ssh/known_hosts
                 * but should be able to add others as needed by using regex to get the host base url, and running this
                 *
                 */

                exec($command, $output, $result_code);
                if ($result_code) {
                    throw new RuntimeException("Cannot do $git_command,  returned code of $result_code : "
                                                    . implode("<br>\n", $output));
                }
                return $output;

            } finally {
                if ($temp_file_path) {
                    unlink($temp_file_path);
                }
            }
        } else {
             $out_string =  $this->do_git_command($git_command,true,null,
                 $b_create_directory_if_empty,$b_cd_into_project);
             return explode("\n",$out_string);
        }
    }


    /**
     * @param string|null $archive_file_path
     * @param string $flow_project_title
     * @param FlowProjectGitSettings|null $settings
     * @return IFlowProject
     * @throws Exception
     */
    public static function create_project_from_upload(?string $archive_file_path,string $flow_project_title,
                                                      ?FlowProjectGitSettings $settings ) :IFlowProject{

        /**
         * @var IFlowProject $project
         */
        $project = null;

        try {
            $project = new FlowProject();
            $project->set_project_type( IFlowProject::FLOW_PROJECT_TYPE_TOP);
            $project->set_admin_user_id( FlowUser::get_logged_in_user()->flow_user_id);

            $project->set_project_title($flow_project_title);
            $project->set_project_blurb('');
            $project->set_read_me('');
            $project->set_public(false);
            //create a mostly empty project, and create its folder (and repo)
            $project->save();

            //delete the project git folder, then copy in the zip, then save project
            $project_directory = $project->get_project_directory();

            $command = "rm -rf $project_directory 2>&1";
            exec($command,$output,$result_code);
            if ($result_code) {
                throw new RuntimeException("Cannot do $command ,  returned code of $result_code : " . implode("<br>\n",$output));
            }


            if ($archive_file_path) {
                $project->create_project_repo($project_directory,false);
                ProjectHelper::get_project_helper()->extract_archive_from_zip_or_tar($archive_file_path,$project_directory);
            } elseif ($settings) {
                if (!$settings->getGitUrl()) {
                    throw new InvalidArgumentException("[create_project_from_upload] needs settings to have the git url");
                }
                $project->do_git_command_with_settings(
                    $settings,
                    sprintf("clone %s %s  ",$settings->getGitUrl(),$project_directory),
                    false,
                    false
                );
            } else {
                throw new InvalidArgumentException("[create_project_from_upload] need archive path or settings");
            }




            $project->create_project_repo($project_directory); //in case not repo imported
            if (is_readable($project->get_bb_code_path())) {
                $bb_code = file_get_contents($project->get_bb_code_path());
                $project->set_read_me(trim($bb_code));
            }

            if (is_readable($project->get_blurb_path())) {
                $blurb = file_get_contents($project->get_blurb_path());
                $project->set_project_blurb(trim($blurb));
            }

            if (is_readable($project->get_title_path())) {
                $title = file_get_contents($project->get_title_path());
                $project->set_project_title($title);
            } else {
                $b_ok = file_put_contents($project->get_title_path(),$project->get_project_title());
                if ($b_ok === false) {
                    throw new RuntimeException("[create_project_from_upload] Could not write to".
                        $project->get_title_path());}
            }
            $project->check_integrity();
            $project->set_db_from_file_state();
            ProjectHelper::get_project_helper()->clean_directory_from_possible_bad_things($project->get_project_directory());
            $project->save();
            return $project;
        } catch (Exception $e) {
            $project?->destroy_project();
            throw $e;
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

        if ($this->get_head_commit_hash() === $this->get_push_head_commit_hash()) {
            return [];
        }

        $push_remote_name = static::PUSH_REMOTE_NAME;
        $command = sprintf("push -u $push_remote_name %s",$push_settings->getGitBranch());
        return $this->do_git_command_with_settings($push_settings,$command);
    }

    /**
     * @param string $patch_file_path
     * @return string[]
     * @throws Exception
     */
    public function apply_patch(string $patch_file_path) :array {
        $old_head = $this->get_head_commit_hash();

        try {
            $this->reset_project_repo_files();//clear up any earlier bugs or crashes
        } catch (Exception $e) {
            $message = "Could not do a hard reset";
            $message.="<br>{$e->getMessage()}\n";
            throw new RuntimeException($message);
        }

        try {
            $git_ret =  $this->do_git_command("apply $patch_file_path");
        } catch (Exception $e) {
            $maybe_changes = $this->do_git_command('diff');
            if (!empty(trim($maybe_changes))) {
                try {
                    $this->reset_project_repo_files($old_head);
                } catch (Exception $oh_no) {
                    $message = "Failed to reset project to $old_head after error of ". $e->getMessage()
                        . ' '. $e->getFile() .' '. $e->getLine() . ' because of ' . $oh_no->getMessage()
                        . ' '. $oh_no->getFile() .' '. $oh_no->getLine();
                    throw new RuntimeException($message);
                }
            }
            throw $e;

        }

        try {
            $this->check_integrity();
            $this->set_db_from_file_state();
            ProjectHelper::get_project_helper()->clean_directory_from_possible_bad_things($this->get_project_directory());
            $this->save();
        } catch (Exception $e) {
            $this->reset_project_repo_files($old_head);
            throw $e;
        }

        return explode("\n",$git_ret);
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

        $old_head = $this->get_head_commit_hash();
        $import_head = $this->get_pull_head_commit_hash();
        if ($import_head === $old_head) {
            throw new NothingToPullException("Import head is same as local head: $old_head");
        }

        try {
            $this->reset_project_repo_files();//clear up any earlier bugs or crashes
        } catch (Exception $e) {
            $message = "Could not do a hard reset";
            $message.="<br>{$e->getMessage()}\n";
            throw new RuntimeException($message);
        }


        $command = "pull ".static::IMPORT_REMOTE_NAME ." ".$this->getGitImportSettings()->getGitBranch();
        try {
            $git_ret =  $this->do_git_command_with_settings(
                $this->getGitImportSettings(),
                $command);
        } catch (Exception $e) {
            $maybe_changes = $this->do_git_command('diff');
            $message = $e->getMessage();
            if (!empty(trim($maybe_changes))) {
                try {
                    $this->do_git_command('merge --abort');
                    $message.="<br>Aborted Merge\n";
                } catch (Exception $oh_no) {
                    $message.="<br>{$oh_no->getMessage()}\n";
                }

            }
            throw new RuntimeException($message);
        }

        try {
            $this->check_integrity();
            $this->set_db_from_file_state();
            ProjectHelper::get_project_helper()->clean_directory_from_possible_bad_things($this->get_project_directory());
            $this->save();
        } catch (Exception $e) {
            $this->reset_project_repo_files($old_head);
            throw $e;
        }

        return $git_ret;
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
        } //todo add check for entry integrity
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
            If(!$db->inTransaction()) {$db->beginTransaction(); }

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

            //mark all ignored folders in the project directory
            FlowEntryYaml::mark_invalid_folders_in_project_folder($this,true);

            if ($db->inTransaction()) {
                $db->commit();
            }



        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            static::get_logger()->alert("Project model cannot update from file stete ",['exception'=>$e]);
            throw $e;
        }



    }



    public function save(bool $b_do_transaction = true,bool $b_commit_project = true): void
    {
        $db = static::get_connection();
        try {
            if ($b_do_transaction && !$db->inTransaction()) { $db->beginTransaction();}
            parent::save(false,$b_commit_project);



            if ($this->b_new_project || !is_dir($this->get_project_directory() . DIRECTORY_SEPARATOR . '.git')) {
                $commit_title = "First Commit";
                $this->commit_changes($commit_title);
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

            $commit_message_full = 'Automatic commit';
            if (!empty($commit_title_array) || !empty($commit_message_array)) {
                $commit_title = implode('; ',$commit_title_array);
                $commit_body = implode('\n',$commit_message_array);

                $commit_message_full =  "$commit_title\n\n$commit_body";

                //if any ` in there, then escape them
                $commit_message_full = str_replace("'","\'",$commit_message_full);


            }

            try {
                if ($b_commit_project) {
                    $this->commit_changes($commit_message_full);
                }

            } catch (NothingToPushException ) {
                //ignore if no file changes
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

    /**
     * @return string|null
     * @throws Exception
     */
    protected function get_push_head_commit_hash() : ?string {
        try {
            $git_push_settings = $this->getGitExportSettings();
            if (!$git_push_settings->getGitUrl()) {return null;}

            $branch = $git_push_settings->getGitBranch();
            if (!$branch) {return null;}
            $push_remote_name = static::PUSH_REMOTE_NAME;
            $fetch_stuff =  $this->do_git_command_with_settings(
                $this->getGitExportSettings(),
                "fetch $push_remote_name");
            WillFunctions::will_do_nothing($fetch_stuff);
            return $this->do_git_command("rev-parse $push_remote_name/$branch");
        }
        catch (FlowProjectGitException $e) {
            try {
                $git_pull_settings = $this->getGitExportSettings();
                $git_url = $git_pull_settings->getGitUrl();
                if (!$git_url) {return null;}
                $branch = $git_pull_settings->getGitBranch();
                if (!$branch) {return null;}
                return $this->do_git_command("ls-remote $git_url $branch | awk '{ print $1}'");

            } catch (Exception $f) {
                static::get_logger()->error("cannot get push head " . $f->getMessage());
                throw $e;
            }

        } catch (Exception $g) {
            static::get_logger()->error("cannot get push head " . $g->getMessage());
            throw $g;
        }
    }

    /**
     * @return string|null
     * @throws Exception
     */
    protected function get_pull_head_commit_hash() : ?string {
        try {
            $git_pull_settings = $this->getGitImportSettings();
            if (!$git_pull_settings->getGitUrl()) {return null;}

            $branch = $git_pull_settings->getGitBranch();
            if (!$branch) {return null;}

            $remote_name = static::IMPORT_REMOTE_NAME;
            $fetch_stuff =  $this->do_git_command_with_settings(
                $this->getGitImportSettings(),
                "fetch $remote_name");
            WillFunctions::will_do_nothing($fetch_stuff);
            return $this->do_git_command("rev-parse $remote_name/$branch");
        }

        catch (FlowProjectGitException $e) {
            try {
                $git_pull_settings = $this->getGitImportSettings();
                $git_url = $git_pull_settings->getGitUrl();
                if (!$git_url) {return null;}
                $branch = $git_pull_settings->getGitBranch();
                if (!$branch) {return null;}
                return $this->do_git_command("ls-remote $git_url $branch | awk '{ print $1}'");

            } catch (Exception $f) {
                static::get_logger()->error("cannot get push head " . $f->getMessage());
                throw $e;
            }

        } catch (Exception $g) {
            static::get_logger()->error("cannot get push head " . $g->getMessage());
            throw $g;
        }
    }

    protected function is_repo_created() : bool {
        if (!$this->get_project_guid()) {return false;}
        try {
            return $this->do_git_command('status');
        } catch (Exception ) {
            return false ;
        }
    }

    /**
     * @return string|null
     */
    public function get_head_commit_hash() : ?string {
        if (!$this->get_project_guid()) {return null;}
        if (!$this->is_repo_created()) {return null;}
        try {
            return $this->do_git_command('rev-parse HEAD');
        } catch (Exception ) {
            return null;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function get_git_status(): array  {
        $what =  $this->do_git_command("status");
        return explode("\n",$what);
    }

    /**
     * @return int
     * @throws Exception
     */
    protected function are_files_dirty() : int {
        $what =  $this->do_git_command("status -s -uno | wc -l");
        return (int)$what;
    }

    /**
     * @param string $command
     * @param bool $b_include_git_word default true
     * @param string|null $pre_command
     * @param bool $b_create_directory_if_empty
     * @param bool $b_cd_into_project
     * @return string
     * @throws Exception
     */
    public  function do_git_command( string $command,bool $b_include_git_word = true,
                                     ?string $pre_command = null,
                                     bool $b_create_directory_if_empty = true,
                                     bool $b_cd_into_project = true
    ) : string {
        if ($b_create_directory_if_empty) {
            $dir = $this->get_project_directory();
        } else {
            $dir = $this->get_calculated_project_directory();
        }

        if (!$dir) {
            throw new FlowProjectGitException("Project Directory is not created yet");
        }
        return FlowGitHistory::do_git_command($dir,$command,$b_include_git_word,$pre_command, $b_cd_into_project);
    }

    /**
     * @param string $repo_path
     * @param bool $b_do_git
     * @throws Exception
     */
    protected function create_project_repo(string $repo_path,bool $b_do_git = true) {
        parent::create_project_repo($repo_path);
        if (!$b_do_git) {return;}
        try {
            $this->do_git_command("init");
        } catch (FlowProjectGitException $f) {
            static::get_logger()->info('[create_project_repo] ignoring'.$f->getMessage());
        }

    }

    /**
     * @param string $remote_name
     * @param FlowProjectGitSettings|null $settings
     * @return void
     * @throws Exception
     */
    protected function set_up_remote(string $remote_name,?FlowProjectGitSettings $settings) : void {
        //see if we are changing the remote
        try {
            try {
                $remote_url = $this->do_git_command("config --get remote.$remote_name.url");
            } catch (Exception ) {
                $remote_url = '';
            }

            if ($settings && $settings->getGitUrl()) {
                if ($remote_url !== $settings->getGitUrl()) {
                    if ($remote_url) {
                        //change the remote
                        $this->do_git_command("remote set-url $remote_name " . $settings->getGitUrl());
                    } else {
                        //set the remote to the url
                        $this->do_git_command("remote add $remote_name " . $settings->getGitUrl());
                    }
                }
            } else {
                if ($remote_url) {
                    $this->do_git_command("remote remove $remote_name ");
                }
            }
        } catch (Exception $e) {
            static::get_logger()->alert("Project git export settings cannot save remote ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @return FlowProjectGitSettings
     * @throws Exception
     */
    protected function getGitExportSettings() : FlowProjectGitSettings {
        $settings =  $this->findGitSetting(IFlowProject::GIT_EXPORT_SETTING_NAME,$b_was_cached );

        if ($b_was_cached ) {
            return $settings;
        }
        $this->set_up_remote(static::PUSH_REMOTE_NAME,$settings);

        return $settings;
    }

    /**
     * @return FlowProjectGitSettings
     * @throws Exception
     */
    protected function getGitImportSettings() : FlowProjectGitSettings {
        $settings =  $this->findGitSetting(IFlowProject::GIT_IMPORT_SETTING_NAME,$b_was_cached );

        if ($b_was_cached) {
            return $settings;
        }
        //see if we are changing the remote

        $this->set_up_remote(static::IMPORT_REMOTE_NAME,$settings);

        return $settings;
    }


}