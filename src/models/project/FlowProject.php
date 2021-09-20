<?php

namespace app\models\project;

use app\hexlet\JsonHelper;
use app\hexlet\WillFunctions;
use app\models\base\FlowBase;
use app\models\multi\GeneralSearch;
use app\models\tag\brief\BriefCheckValidYaml;
use app\models\tag\brief\BriefDiffFromYaml;
use app\models\tag\brief\BriefUpdateFromYaml;
use app\models\tag\FlowTagSearchParams;
use app\models\user\FlowUser;
use app\models\tag\FlowTag;
use Exception;
use InvalidArgumentException;
use LogicException;
use PDO;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;


class FlowProject extends FlowBase {

    const FLOW_PROJECT_TYPE_TOP = 'top';
    const MAX_SIZE_TITLE = 40;
    const MAX_SIZE_BLURB = 120;
    const MAX_SIZE_EXPORT_URL = 200;

    const MAX_SIZE_READ_ME_IN_CHARACTERS = 4000000;

    public ?int $id;
    public ?int $created_at_ts;
    public ?int $is_public;
    public ?string $flow_project_guid;


    public ?int $admin_flow_user_id;
    public ?int $parent_flow_project_id;

    public ?string $flow_project_type;
    public ?string $flow_project_title;
    public ?string $flow_project_blurb;
    public ?string $flow_project_readme;
    public ?string $flow_project_readme_bb_code;
    public ?string $flow_project_readme_html;

    protected ?string $old_flow_project_title;
    protected ?string $old_flow_project_blurb;
    protected ?string $old_flow_project_readme_bb_code;


    public ?string $export_repo_url;
    public ?string $export_repo_key;
    public ?string $export_repo_branch;
    public ?int $export_repo_do_auto_push;


    public ?string $import_repo_url;
    public ?string $import_repo_key;
    public ?string $import_repo_branch;

    /**
     * @var FlowProjectUser[] $project_users
     */
    public array $project_users;

    protected ?FlowUser $admin_user ;

    /**
     * @var FlowGitHistory[] $project_history
     */
    protected array $project_history ;

    protected ?FlowProjectUser $current_user_permissions;

    public function set_current_user_permissions(?FlowProjectUser $v) {
        $this->current_user_permissions = $v;
    }

    /** @noinspection PhpUnused */
    public function get_current_user_permissions(): ?FlowProjectUser
    {
        if (!isset($this->current_user_permissions)) {return new FlowProjectUser();} //return no permissions
        return $this->current_user_permissions;
    }

    /**
     * @var FlowTag[]|null $owned_tags
     */
    protected ?array $owned_tags = null;

    protected bool $b_did_applied_for_owned_tags = false;


    /**
     * @param bool $b_get_applied  if true will also get the applied in the set of tags found
     * @param bool $b_refresh  if true will not use previous value if set
     * @return FlowTag[]
     * @throws Exception
     */
    function get_all_owned_tags_in_project(bool $b_get_applied = false,bool $b_refresh = false) : array {
        if (!$b_refresh && is_array($this->owned_tags)) {
            //refresh cache if first time getting applied
            if ($b_get_applied && $this->b_did_applied_for_owned_tags) {
                return $this->owned_tags;
            }
        }
        $search_params = new FlowTagSearchParams();
        $search_params->flag_get_applied = $b_get_applied;
        $search_params->owning_project_guid = $this->flow_project_guid;
        $unsorted_array = FlowTag::get_tags($search_params,1,GeneralSearch::UNLIMITED_RESULTS_PER_PAGE);

        $this->owned_tags = FlowTag::sort_tag_array_by_parent($unsorted_array);

        if ($b_get_applied) {
            $this->b_did_applied_for_owned_tags = true;
        }

        return $this->owned_tags;
    }

    /**
     * @var FlowTag[]|null $tags_applied_to_this
     */
    protected ?array $tags_applied_to_this = null;

    /** @noinspection PhpUnused */
    /**
     * @param bool $b_refresh  if true will not use previous value if set
     * @return FlowTag[]
     * @throws Exception
     */
    function get_applied_tags(bool $b_refresh = false) : array {
        if (!$b_refresh && is_array($this->tags_applied_to_this)) {
            //refresh cache if first time getting applied
            return $this->tags_applied_to_this;
        }
        $search_params = new FlowTagSearchParams();
        $search_params->flag_get_applied = true;
        $search_params->owning_project_guid = $this->flow_project_guid;
        $search_params->only_applied_to_guids[] = $this->flow_project_guid;
        $this->tags_applied_to_this = FlowTag::get_tags($search_params,1,GeneralSearch::UNLIMITED_RESULTS_PER_PAGE);
        return $this->tags_applied_to_this;
    }


    /** @noinspection PhpUnused */
    public  function max_read_me(): int
    {
        return static::MAX_SIZE_READ_ME_IN_CHARACTERS;
    }

    /** @noinspection PhpUnused */
    public  function max_blurb(): int
    {
        return static::MAX_SIZE_BLURB;
    }

    /** @noinspection PhpUnused */
    public  function max_title(): int
    {
        return static::MAX_SIZE_TITLE;
    }


    /**
     * @return FlowUser|null
     * @throws Exception
     */
    public function get_admin_user(): ?FlowUser
    {
        if ($this->admin_user) {return $this->admin_user;}
        if ($this->admin_flow_user_id) {
            $this->admin_user =  FlowUser::find_one($this->admin_flow_user_id);
        }
        return $this->admin_user;
    }

    /** @noinspection PhpUnused */
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

    /** @noinspection PhpUnused */
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

    /** @noinspection PhpUnused */
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
     * @return FlowUser[]
     * @throws Exception
     */
    public function get_flow_project_users() : array {

        $page = 1;
        $ret = [];
        do {
            $info = FlowUser::find_users_by_project(true,$this->flow_project_guid,null,null,null ,$page);
            $page++;
            $ret = array_merge($ret,$info);
        } while(count($info));
        return $ret;
    }




    public function __construct($object=null){
        $this->admin_user = null;
        $this->b_did_applied_for_owned_tags = false;
        if (empty($object)) {
            $this->admin_flow_user_id = null;
            $this->flow_project_blurb = null;
            $this->flow_project_title = null;
            $this->flow_project_readme = null;
            $this->flow_project_readme_bb_code = null;
            $this->flow_project_readme_html = null;
            $this->flow_project_guid = null;
            $this->flow_project_type = null;
            $this->created_at_ts = null;
            $this->is_public = null;

            $this->old_flow_project_blurb = null;
            $this->old_flow_project_readme_bb_code = null;
            $this->old_flow_project_title = null;

            $this->export_repo_do_auto_push = null;
            $this->export_repo_key = null;
            $this->export_repo_url = null;
            $this->export_repo_branch = null;
            $this->import_repo_key = null;
            $this->import_repo_url = null;
            $this->import_repo_branch = null;
            return;
        }
        $this->project_users = [];
        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

        $this->old_flow_project_blurb = $this->flow_project_blurb;
        $this->old_flow_project_readme_bb_code = $this->flow_project_readme_bb_code;
        $this->old_flow_project_title = $this->flow_project_title;
    }

    public static function check_valid_title($words) : bool  {

        if (!static::minimum_check_valid_name($words,static::MAX_SIZE_TITLE)) {return false;}

        $b_match = preg_match('/^[[:alnum:]\-]+$/u',$words,$matches);
        if ($b_match === false) {
            $error_preg = array_flip(array_filter(get_defined_constants(true)['pcre'], function ($value) {
                return substr($value, -6) === '_ERROR';
            }, ARRAY_FILTER_USE_KEY))[preg_last_error()];
            throw new RuntimeException($error_preg);
        }
        return (bool)$b_match;

    }


    /**
     * @throws Exception
     */
    public function save_export_settings() {
        try {
            if (empty($this->export_repo_do_auto_push)) {
                $this->export_repo_do_auto_push = 0;
            }

            if (empty($this->export_repo_key)) {
                $this->export_repo_key =null;
                $this->export_repo_do_auto_push = 0;
            }

            if (empty($this->export_repo_url)) {
                $this->export_repo_url =null;
                $this->export_repo_do_auto_push = 0;
            }

            if (empty($this->export_repo_branch)) {
                $this->export_repo_branch =null;
                $this->export_repo_do_auto_push = 0;
            }



            if ($this->export_repo_url && (mb_strlen($this->export_repo_url) > static::MAX_SIZE_EXPORT_URL)) {
                throw new InvalidArgumentException("Project Export Repo cannot be more than " . static::MAX_SIZE_EXPORT_URL . " characters");
            }



            $db = static::get_connection();
            $db->update('flow_projects', [
                'export_repo_do_auto_push' => $this->export_repo_do_auto_push,
                'export_repo_url' => $this->export_repo_url,
                'export_repo_branch' => $this->export_repo_branch,
                'export_repo_key' => $this->export_repo_key
            ], [
                'id' => $this->id
            ]);

            //see if we are changing the remote

            try {
                $remote_url = $this->do_git_command("config --get remote.origin.url");
            } catch (Exception $e) {
                $remote_url = '';
            }

            if ( $this->export_repo_url && ($remote_url !== $this->export_repo_url)) {
                if ($remote_url) {
                    //change the origin
                    $this->do_git_command("remote set-url origin $this->export_repo_url");
                } else {
                    //set the origin to the url
                    $this->do_git_command("remote add origin $this->export_repo_url");
                }
            }



        } catch (Exception $e) {
            static::get_logger()->alert("Project export settings cannot save ",['exception'=>$e]);
            throw $e;
        }
    }



    /**
     * @throws Exception
     */
    public function save_import_settings() {
        try {


            if (empty($this->import_repo_key)) {
                $this->import_repo_key =null;
            }

            if (empty($this->import_repo_url)) {
                $this->import_repo_url =null;
            }

            if (empty($this->import_repo_branch)) {
                $this->export_repo_branch =null;
            }

            if ($this->import_repo_url && (mb_strlen($this->import_repo_url) > static::MAX_SIZE_EXPORT_URL)) {
                throw new InvalidArgumentException("Project Import Repo cannot be more than " . static::MAX_SIZE_EXPORT_URL . " characters");
            }



            $db = static::get_connection();
            $db->update('flow_projects', [
                'import_repo_url' => $this->import_repo_url,
                'import_repo_branch' => $this->import_repo_branch,
                'import_repo_key' => $this->import_repo_key
            ], [
                'id' => $this->id
            ]);

            //see if we are changing the remote

            try {
                $remote_url = $this->do_git_command("config --get remote.import.url");
            } catch (Exception $e) {
                $remote_url = '';
            }

            if ( $this->import_repo_url && ($remote_url !== $this->import_repo_url)) {
                if ($remote_url) {
                    //change the import
                    $this->do_git_command("remote set-url import $this->import_repo_url");
                } else {
                    //set the origin to the url
                    $this->do_git_command("remote add import $this->import_repo_url");
                }
            }



        } catch (Exception $e) {
            static::get_logger()->alert("Project import settings cannot save ",['exception'=>$e]);
            throw $e;
        }
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
    public function save_tags_to_yaml_in_project_directory()  {
        $tags = $this->get_all_owned_tags_in_project(true);

        foreach ($tags as $tag) {
            $tag->set_brief_json(true);
        }

        $pigs_in_space = JsonHelper::toString($tags);
        $tags_serialized = JsonHelper::fromString($pigs_in_space);

        foreach ($tags as $tag) {
            $tag->set_brief_json(false);
        }

        $tag_yaml = Yaml::dump($tags_serialized);

        $tag_yaml_path = $this->get_tag_yaml_path();
        $b_ok = file_put_contents($tag_yaml_path,$tag_yaml);
        if ($b_ok === false) {throw new RuntimeException("Could not write to $tag_yaml_path");}
    }

    /**
     * returns true if changes and commit made
     * @param bool $b_commit , default true, will only commit if true. If false will write commit message to log
     * @param bool $b_log_message , default false, if true will write commit message to log
     * @throws Exception
     * @return false|string  returns false if no changes, else returns the commit message (regardless if committed)
     */
    public function save_tag_yaml_and_commit(bool $b_commit = true,bool $b_log_message = false)  {
        $brief_changes = new BriefDiffFromYaml($this); //compare current changes to older saved in yaml

        $this->save_tags_to_yaml_in_project_directory();

        $number_tag_changes = $brief_changes->count_changes();
        $commit_message = null;
        if ($number_tag_changes) {
            $this->do_git_command("add .");

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

            if ($b_log_message) {
                static::get_logger()->debug("Tag Commit message",['message'=>$commit_message]);
            }
            if ($b_commit) {
                $this->do_git_command("commit  -m '$commit_message'");
                if (isset($_SESSION[FlowUser::SESSION_USER_KEY])) {
                    /**
                     * @var FlowUser $logged_in_user
                     */
                    $logged_in_user = $_SESSION[FlowUser::SESSION_USER_KEY];
                    $user_info = "$logged_in_user->flow_user_guid <$logged_in_user->flow_user_email>";
                    $this->do_git_command("commit --amend --author='$user_info' --no-edit");
                }
                if ($this->export_repo_do_auto_push) {
                    $this->push_repo();
                }
            }
        }

        return $commit_message?? false;

    }

    /**
     * @throws Exception
     */
    public function do_tag_save() {
        $this->save_tag_yaml_and_commit();
    }

    /**
     * @throws Exception
     * @return bool true if committed, false if nothing to commit
     */
    public function save() :bool {
        $db = null;
        try {
            if (empty($this->flow_project_title)) {
                throw new InvalidArgumentException("Project Title cannot be empty");
            }
            if (mb_strlen($this->flow_project_title) > static::MAX_SIZE_TITLE) {
                throw new InvalidArgumentException("Project Title cannot be more than ".static::MAX_SIZE_TITLE." characters");
            }

            if (mb_strlen($this->flow_project_blurb) > static::MAX_SIZE_BLURB) {
                throw new InvalidArgumentException("Project Blurb cannot be more than ".static::MAX_SIZE_BLURB." characters");
            }

            $b_match = static::check_valid_title($this->flow_project_title);
            if (!$b_match) {
                throw new InvalidArgumentException(
                    "Project Title needs to be all alpha numeric or dash only. ".
                    "First character cannot be a number. Title Cannot be less than 3 or greater than 40. ".
                    " Title cannot be a hex number greater than 25 and cannot be a decimal number");
            }

            $b_did_title_change = ($this->flow_project_title !== $this->old_flow_project_title);
            $b_did_blurb_change = ($this->flow_project_blurb !== $this->old_flow_project_blurb);
            $b_did_readme_bb_change = ($this->flow_project_readme_bb_code !== $this->old_flow_project_readme_bb_code);

            $db = static::get_connection();
            $db->beginTransaction();
            if ($this->flow_project_guid) {
                $db->update('flow_projects',[
                    'admin_flow_user_id' => $this->admin_flow_user_id,
                    'is_public' => $this->is_public,
                    'parent_flow_project_id' => $this->parent_flow_project_id,
                    'flow_project_type' => $this->flow_project_type,
                    'flow_project_title' => $this->flow_project_title,
                    'flow_project_blurb' => $this->flow_project_blurb,
                    'flow_project_readme' => $this->flow_project_readme,
                    'flow_project_readme_html' => $this->flow_project_readme_html,
                    'flow_project_readme_bb_code' => $this->flow_project_readme_bb_code,
                ],[
                    'id' => $this->id
                ]);



            } else {
                $db->insert('flow_projects',[
                    'admin_flow_user_id' => $this->admin_flow_user_id,
                    'is_public' => $this->is_public,
                    'parent_flow_project_id' => $this->parent_flow_project_id,
                    'flow_project_type' => $this->flow_project_type,
                    'flow_project_title' => $this->flow_project_title,
                    'flow_project_blurb' => $this->flow_project_blurb,
                    'flow_project_readme' => $this->flow_project_readme,
                    'flow_project_readme_html' => $this->flow_project_readme_html,
                    'flow_project_readme_bb_code' => $this->flow_project_readme_bb_code,
                ]);
                $this->id = $db->lastInsertId();
            }

            if (!$this->flow_project_guid) {
                $this->flow_project_guid = $db->cell("SELECT HEX(flow_project_guid) as flow_project_guid FROM flow_projects WHERE id = ?",$this->id);
                if (!$this->flow_project_guid) {
                    throw new RuntimeException("Could not get project guid using id of ". $this->id);
                }
            }

            //update the old to have the new, for next save
            $this->old_flow_project_readme_bb_code = $this->flow_project_readme_bb_code;
            $this->old_flow_project_blurb = $this->flow_project_blurb;
            $this->old_flow_project_title = $this->flow_project_title;

            $dir = $this->get_project_directory();
            $make_first_commit = false;
            if (!is_readable($dir)) {
                $this->create_project_repo();
                $make_first_commit = true;
            }
            $read_me_path_bb = $dir . DIRECTORY_SEPARATOR . 'flow_project_readme_bb_code.bbcode';
            $read_me_path_html = $dir . DIRECTORY_SEPARATOR . 'flow_project_readme_html.html';
            $blurb_path = $dir . DIRECTORY_SEPARATOR . 'flow_project_blurb';
            $title_path = $dir . DIRECTORY_SEPARATOR . 'flow_project_title';
            $yaml_path = $dir . DIRECTORY_SEPARATOR . 'flow_project.yaml';



            $yaml_array = [
              'timestamp' => time(),
              'flow_project_guid' => $this->flow_project_guid
            ];

            $yaml = Yaml::dump($yaml_array);
            $b_ok = file_put_contents($yaml_path,$yaml);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $yaml_path");}

            if ($make_first_commit) {
                $commit_title = "First Commit";
                $this->do_git_command("add .");
                $this->do_git_command("commit  -m '$commit_title'");
            }


            $b_ok = file_put_contents($read_me_path_bb,$this->flow_project_readme_bb_code);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $read_me_path_bb");}

            $b_ok = file_put_contents($read_me_path_html,$this->flow_project_readme_html);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $read_me_path_html");}

            $b_ok = file_put_contents($blurb_path,$this->flow_project_blurb);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $blurb_path");}

            $b_ok = file_put_contents($title_path,$this->flow_project_title);
            if ($b_ok === false) {throw new RuntimeException("Could not write to $title_path");}


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
                return ''; //nothing to commit
            }
            $this->do_git_command("add .");

            $commit_title = implode('; ',$commit_title_array);
            $commit_body = implode('\n',$commit_message_array);

            $commit_message_full =  "$commit_title\n\n$commit_body";

            //if any ` in there, then escape them
            $commit_message_full = str_replace("'","\'",$commit_message_full);



            $this->do_git_command("commit  -m '$commit_message_full'");

            if (isset($_SESSION[FlowUser::SESSION_USER_KEY])) {
                /**
                 * @var FlowUser $logged_in_user
                 */
                $logged_in_user = $_SESSION[FlowUser::SESSION_USER_KEY];
                $user_info = "$logged_in_user->flow_user_guid <$logged_in_user->flow_user_email>";
                $this->do_git_command("commit --amend --author='$user_info' --no-edit");
            }


            $db->commit();


        } catch (Exception $e) {
            $db AND $db->rollBack();
            static::get_logger()->alert("Project model cannot save ",['exception'=>$e]);
            throw $e;
        }

        if ($this->export_repo_do_auto_push) {
            $push_info = $this->push_repo();
            return "Saved and pushed to $this->export_repo_url<br>$push_info";
        }
        return "Saved";
    }

    /**
     * @param int $flow_user_id
     * @return FlowProject[]
     * @throws Exception
     */
    public static function get_all_top_projects(int $flow_user_id) : array {
        $db = static::get_connection();
        $sql = "SELECT DISTINCT p.id, p.created_at_ts FROM flow_projects p 
                WHERE p.admin_flow_user_id = ? and p.flow_project_type = ?
                ORDER BY  p.created_at_ts DESC ";

        try {
            $what = $db->safeQuery($sql, [$flow_user_id,static::FLOW_PROJECT_TYPE_TOP], PDO::FETCH_OBJ);
            if (empty($what)) {
                return [];
            }
            /**
             * @var FlowProject[] $ret;
             */
            $ret = [];
            foreach ($what as $row) {
                $node = static::find_one($row->id);
                if ($node) {
                    $ret[] = $node;
                }
            }
            return $ret;
        } catch (Exception $e) {
            static::get_logger()->alert("Project model cannot find_one ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * get a project without a user if supply an id or guid
     * or get a project based on any number of guid, and name combinations for project and user
     * @param ?string $project_title_guid_or_id
     * @param ?string $user_name_guid_or_id
     * @return FlowProject|null
     * @throws Exception
     */
    public static function find_one(?string $project_title_guid_or_id, ?string $user_name_guid_or_id = null): ?FlowProject
    {
        $db = static::get_connection();


        if (trim($project_title_guid_or_id) && trim($user_name_guid_or_id)) {
            $where_condition = " ( u.flow_user_name = ? OR u.flow_user_guid = UNHEX(?) ) AND ".
                " (  p.flow_project_title = ? or p.flow_project_guid = UNHEX(?))";
            $args = [$user_name_guid_or_id,$user_name_guid_or_id,
                $project_title_guid_or_id,$project_title_guid_or_id];
        } else if (trim($project_title_guid_or_id) ) {
            $where_condition = " (p.id = ? OR  p.flow_project_guid = UNHEX(?) )";
            $args = [(int)$project_title_guid_or_id,$project_title_guid_or_id];
        } else{
            throw new LogicException("Need at least one project id/name/string");
        }



        $sql = "SELECT 
                p.id,
                p.created_at_ts,
                p.is_public,    
                HEX(p.flow_project_guid) as flow_project_guid,
                p.admin_flow_user_id,
                p.parent_flow_project_id,      
                p.flow_project_type,
                p.flow_project_title,
                p.flow_project_blurb,
                p.flow_project_readme,
                p.flow_project_readme_html,
                p.flow_project_readme_bb_code,
       
                p.export_repo_do_auto_push,
                p.export_repo_url,
                p.export_repo_branch,
                p.export_repo_key,
       
                p.import_repo_url,
                p.import_repo_branch,
                p.import_repo_key

                FROM flow_projects p 
                INNER JOIN  flow_users u ON u.id = p.admin_flow_user_id
                WHERE 1 AND $where_condition";

        try {
            $what = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
            if (empty($what)) {
                return null;
            }
            return new FlowProject($what[0]);
        } catch (Exception $e) {
            static::get_logger()->alert("Project model cannot find_one ",['exception'=>$e]);
            throw $e;
        }
    }

    public function set_read_me(string $read_me) {
        $this->flow_project_readme_bb_code = $read_me;
        $this->flow_project_readme_html = JsonHelper::html_from_bb_code($read_me);
        $this->flow_project_readme = str_replace('&nbsp;',' ',strip_tags($this->flow_project_readme_html));
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function get_projects_base_directory() : string {
        $check =  static::$container->get('settings')->project->parent_directory;
        if (!is_readable($check)) {
            throw new RuntimeException("The directory of $check is not readable");
        }
        return $check;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function get_project_directory() : string {
        $check =  $this->get_projects_base_directory(). DIRECTORY_SEPARATOR .
            $this->get_admin_user()->flow_user_guid . DIRECTORY_SEPARATOR . $this->flow_project_guid;
        return $check;
    }

    /**
     * @throws Exception
     */
    protected function create_project_repo() {
        $dir = $this->get_project_directory();
        if (!is_readable($dir)) {
            $check =  mkdir($dir,0777,true);
            if (!$check) {
                throw new RuntimeException("Could not create the directory of $dir");
            }
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if (!is_readable($dir)) {
                throw new RuntimeException("Could not make a readable directory of $dir");
            }
        }
        //have a directory, now need to add the .gitignore
        $ignore = file_get_contents(__DIR__. DIRECTORY_SEPARATOR . 'repo'. DIRECTORY_SEPARATOR . 'gitignore.txt');
        file_put_contents($dir.DIRECTORY_SEPARATOR.'.gitignore',$ignore);
        $this->do_git_command("init");
    }

    /**
     * @param string $command
     * @param string|null $pre_command
     * @param  bool $b_include_git_word  default true
     * @return string
     * @throws Exception
     */
    protected function do_git_command(string $command,bool $b_include_git_word = true,?string $pre_command = null) : string {
        $dir = $this->get_project_directory();
        return FlowGitHistory::do_git_command($dir,$command,$b_include_git_word,$pre_command);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function get_head_commit_hash() : string {
        try {
            return $this->do_git_command('rev-parse HEAD');
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Saves the private key to a file, and deletes it after, and might take care of ssh local host issues
     * @param string $private_key
     * @param string $to_ssh_url
     * @param string $git_command
     * @return string
     * @throws
     */
    protected function do_key_command_with_private_key(string $private_key,string $to_ssh_url,string $git_command) : string {

        WillFunctions::will_do_nothing($to_ssh_url); //reserved for future use
        $temp_file_path = null;
        try {
            //save private key as temp file, and set permissions to owner only
            $temp_file_path = tempnam(sys_get_temp_dir(), 'git-key-');
            file_put_contents($temp_file_path,$private_key);
            $directory = $this->get_project_directory();
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
            return implode("<br>\n",$output);

        } finally {
            if ($temp_file_path) {
                unlink($temp_file_path);
            }
        }
    }

    /**
     * @param $command
     * @return string
     * @throws Exception
     */
    protected function do_project_directory_command($command) :string  {
        $directory = $this->get_project_directory();
        $full_command = "cd $directory && $command";
        exec($full_command,$output,$result_code);
        if ($result_code) {
            throw new RuntimeException("Cannot do $command,  returned code of $result_code : " . implode("\n",$output));
        }
        return implode("\n",$output);
    }

    /**
     * @throws Exception
     */
    public function push_repo() : string  {
        if (!$this->export_repo_url) {
            throw new RuntimeException("Export Repo Url not set");
        }
        if (!$this->export_repo_key) {
            throw new RuntimeException("Export Repo Key not set");
        }

        if (!$this->export_repo_branch) {
            throw new RuntimeException("Export Repo Branch not set");
        }

        $command = "push -u origin $this->export_repo_branch";
        return $this->do_key_command_with_private_key($this->export_repo_key,$this->export_repo_url,$command);
    }

    /**
     * @return string
     * @throws Exception
     */
    function import_pull_repo_from_git() :string {
        if (!$this->import_repo_url) {
            throw new RuntimeException("Import Repo Url not set");
        }
        if (!$this->import_repo_key) {
            throw new RuntimeException("Import Repo Key not set");
        }

        if (!$this->import_repo_branch) {
            throw new RuntimeException("Import Repo Branch not set");
        }

        $old_head = $this->get_head_commit_hash();
        $command = "reset --hard"; //clear up any earlier bugs or crashes
        try {
            $this->do_key_command_with_private_key($this->export_repo_key,$this->export_repo_url,$command);
        } catch (Exception $e) {
            $message = "Could not do a hard reset";
            $message.="<br>{$e->getMessage()}\n";
            throw new RuntimeException($message);
        }

        $command = "pull import $this->import_repo_branch";
        try {
            $git_ret =  $this->do_key_command_with_private_key($this->export_repo_key,$this->export_repo_url,$command);
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

        $new_head = $this->get_head_commit_hash();
        try {
            $this->check_integrity();
            $this->set_db_from_file_state();
        } catch (Exception $e) {
            //do not use commit just imported, and remove any changes to files
            if ($old_head !== $new_head) {
                $this->do_git_command("reset --hard $old_head");
            }
            throw $e;
        }

        return $git_ret;
    }

    function update_repo_from_file() :string {
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
        $this->check_valid_title($title);
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

        $dir = $this->get_project_directory();
        $read_me_path_html = $dir . DIRECTORY_SEPARATOR . 'flow_project_readme_html.html';

        $b_ok = file_put_contents($read_me_path_html,$this->flow_project_readme_html);
        if ($b_ok === false) {throw new RuntimeException("[set_db_from_file_state] Could not write to $read_me_path_html");}

        $db = null;
        try {
            $db = static::get_connection();
            $db->beginTransaction();

            $db->update('flow_projects',[
                'flow_project_type' => $this->flow_project_type,
                'flow_project_title' => $this->flow_project_title,
                'flow_project_blurb' => $this->flow_project_blurb,
                'flow_project_readme' => $this->flow_project_readme,
                'flow_project_readme_html' => $this->flow_project_readme_html,
                'flow_project_readme_bb_code' => $this->flow_project_readme_bb_code,
            ],[
                'id' => $this->id
            ]);

            //do children here

            $tags = new BriefUpdateFromYaml($this);
            WillFunctions::will_do_nothing($tags); //for debugging

            $db->commit();


        } catch (Exception $e) {
            $db AND $db->rollBack();
            static::get_logger()->alert("Project model cannot update from file stete ",['exception'=>$e]);
            throw $e;
        }

    }




}