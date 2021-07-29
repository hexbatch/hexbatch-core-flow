<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace app\models\project;

use app\hexlet\JsonHelper;
use app\models\user\FlowUser;
use DI\Container;
use Exception;
use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use PDO;
use Psr\Log\LoggerInterface;
use RuntimeException;


class FlowProject {

    const FLOW_PROJECT_TYPE_TOP = 'top';
    const MAX_SIZE_TITLE = 40;
    const MAX_SIZE_BLURB = 120;

    const MAX_SIZE_READ_ME_IN_CHARACTERS = 4000000;

    public ?int $id;
    public ?int $created_at_ts;
    public ?string $flow_project_guid;


    public ?int $admin_flow_user_id;
    public ?int $parent_flow_project_id;

    public ?string $flow_project_type;
    public ?string $flow_project_title;
    public ?string $flow_project_blurb;
    public ?string $flow_project_readme;
    public ?string $flow_project_readme_bb_code;
    public ?string $flow_project_readme_html;

    /**
     * @var FlowProjectUser[] $project_users
     */
    public array $project_users;

    protected ?FlowUser $admin_user ;


    /**
     * @var Container $container
     */
    protected static Container $container;

    public static function set_container($c) {
        static::$container = $c;
    }

    public  function max_read_me(): int
    {
        return static::MAX_SIZE_READ_ME_IN_CHARACTERS;
    }

    public  function max_blurb(): int
    {
        return static::MAX_SIZE_BLURB;
    }

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

    /**
     * @return FlowProjectUser[]
     * @throws Exception
     */
    public function get_flow_project_users() : array {
        return FlowProjectUser::find_users_in_project($this->id);
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
            return;
        }
        $this->project_users = [];
        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }
    }

    public static function check_valid_title($words) : bool  {

        if (empty($words)) {return false;}
        if (is_numeric(substr($words, 0, 1)) ) {
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
                throw new InvalidArgumentException("Project Title needs to be all alpha numeric or dash only. First character cannot be a number");
            }

            $db = static::get_connection();
            $db->beginTransaction();
            if ($this->flow_project_guid) {
                $db->update('flow_projects',[
                    'admin_flow_user_id' => $this->admin_flow_user_id,
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
                    'parent_flow_project_id' => $this->parent_flow_project_id,
                    'flow_project_type' => $this->flow_project_type,
                    'flow_project_title' => $this->flow_project_title,
                    'flow_project_blurb' => $this->flow_project_blurb,
                    'flow_project_readme' => $this->flow_project_readme,
                    'flow_project_readme_html' => $this->flow_project_readme_html,
                    'flow_project_readme_bb_code' => $this->flow_project_readme_bb_code,
                ]);
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
     * @param ?string $project_title_or_id
     * @param ?string $user_name_or_id
     * @return FlowProject|null
     * @throws Exception
     */
    public static function find_one(?string $project_title_or_id,?string $user_name_or_id = null): ?FlowProject
    {
        $db = static::get_connection();

        if (ctype_digit($project_title_or_id)) {
            $where_condition = " p.id = ?";
            $args = [(int)$project_title_or_id];
        } else {
            if (ctype_digit($user_name_or_id)) {
                $where_condition = " u.id = ? AND p.flow_project_title = ?";
                $args = [(int)$user_name_or_id,$project_title_or_id];
            } else {
                $where_condition = " u.flow_user_name = ? AND p.flow_project_title = ?";
                $args = [$user_name_or_id,$project_title_or_id];
            }

        }


        $sql = "SELECT 
                p.id,
                p.created_at_ts,
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


}