<?php

namespace app\models\project;

use app\hexlet\JsonHelper;
use app\models\user\FlowUser;
use DI\Container;
use Exception;
use InvalidArgumentException;
use LogicException;
use ParagonIE\EasyDB\EasyDB;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;


class FlowProject {

    const FLOW_PROJECT_TYPE_TOP = 'top';
    const MAX_SIZE_TITLE = 40;
    const MAX_SIZE_BLURB = 120;

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
     * @var Container $container
     */
    protected static Container $container;

    public static function set_container($c) {
        static::$container = $c;
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
     * @param bool $b_refresh , default false
     * @return FlowGitHistory[]
     * @throws Exception
     */
    public function history(bool $b_refresh= false): array
    {
        if ($b_refresh || empty($this->project_history)) {
            $this->project_history = FlowGitHistory::get_history($this->get_project_directory());
        }

        return $this->project_history;
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

    /**
     * @return EasyDB
     */
    protected static function get_connection() : EasyDB {
        try {
            return  static::$container->get('connection');
        } catch (Exception $e) {
            static::get_logger()->alert("User model cannot connect to the database",['exception'=>$e]);
            die( static::class . " Cannot get connetion");
        }
    }





    /**
     * @return LoggerInterface
     */
    protected static function get_logger() : LoggerInterface {
        try {
            return  static::$container->get(LoggerInterface::class);
        } catch (Exception $e) {
            die( static::class . " Cannot get logger");
        }
    }

    public function __construct($object=null){
        $this->admin_user = null;
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

        if (empty($words)) {return false;}

        if (is_numeric(substr($words, 0, 1)) ) {
            return false;
        }

        if (ctype_digit($words) ) {
            return false;
        }

        if (ctype_xdigit($words) && (mb_strlen($words) > 25) ) {
            return false;
        }

        if ((mb_strlen($words) < 3) || (mb_strlen($words) > 40) ) {
            return false;
        }
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
    public function save() {
        $db = null;
        try {
            if (empty($this->flow_project_title)) {
                throw new InvalidArgumentException("Project Title cannot be empty");
            }
            if (mb_strlen($this->flow_project_title) > static::MAX_SIZE_TITLE) {
                throw new InvalidArgumentException("Project Title cannot be more than ".static::MAX_SIZE_TITLE." characters");
            }

            if (mb_strlen($this->flow_project_blurb) > static::MAX_SIZE_BLURB) {
                throw new InvalidArgumentException("Project Title cannot be more than ".static::MAX_SIZE_BLURB." characters");
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


            $commit_title = "Changed Project";
            $commit_message_array = [];
            if ($b_did_title_change) { $commit_message_array[] = "Updated Project Title";}
            if ($b_did_blurb_change) { $commit_message_array[] = "Updated Project Blurb";}
            if ($b_did_readme_bb_change) { $commit_message_array[] = "Updated Project Read Me";}
            //todo save entities and tags here , they will update the title and message

            $this->do_git_command("add .");

            array_unshift($commit_message_array,'');
            array_unshift($commit_message_array,$commit_title);
            $commit_message_full = implode("\n",$commit_message_array);

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
                p.flow_project_readme_bb_code
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
     * @return string
     * @throws Exception
     */
    protected function do_git_command(string $command) : string {
        $dir = $this->get_project_directory();
        return FlowGitHistory::do_git_command($dir,$command);
    }






}