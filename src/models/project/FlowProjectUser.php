<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace app\models\project;
use DI\Container;
use Exception;
use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use PDO;
use Psr\Log\LoggerInterface;

class FlowProjectUser {
    public ?int $id;
    public ?int $created_at_ts;
    public ?string $flow_project_user_guid;

    public ?int $flow_project_id;
    public ?int $flow_user_id;
    public ?int $can_write;
    public ?int $can_read;

    public ?string $flow_user_name;
    public ?string $flow_user_guid;

    /**
     * @var Container $container
     */
    protected static Container $container;

    public static function set_container($c) {
        static::$container = $c;
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
        if (empty($object)) {
            $this->id = null;
            $this->can_read = null;
            $this->can_write = null;
            $this->flow_user_id = null;
            $this->flow_project_user_guid = null;
            $this->created_at_ts = null;
            $this->flow_user_name = null;
            $this->flow_user_guid = null;
            return;
        }
        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

    }


    /**
     * @throws Exception
     */
    public function save() {
        try {
            $db = static::get_connection();
            if ($this->flow_project_user_guid) {
                $db->update('flow_project_users',[
                    'flow_project_id' => $this->flow_project_id,
                    'flow_user_id' => $this->flow_user_id,
                    'can_write' => $this->can_write,
                    'can_read' => $this->can_read,
                ],[
                    'id' => $this->id
                ]);


            } else {
                $db->insert('flow_project_users',[
                    'flow_project_id' => $this->flow_project_id,
                    'flow_user_id' => $this->flow_user_id,
                    'can_write' => $this->can_write,
                    'can_read' => $this->can_read,
                ]);
            }


        } catch (Exception $e) {
            static::get_logger()->alert("FlowProjectUser model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ?string $project_title_or_id
     * @param ?string $user_name_or_id
     * @return FlowProjectUser[]
     * @throws Exception
     */
    public static function find_users_in_project(?string $project_title_or_id, ?string $user_name_or_id = null): array
    {

        $db = static::get_connection();

        $args = [];
        $wheres = [];

        if (ctype_digit($project_title_or_id)) {
            $wheres[] = " p.id = ?";
            $args[] = (int)$project_title_or_id;
        } elseif (trim($project_title_or_id)) {
            $wheres[] = " p.flow_project_title = ?";
            $args[] = $project_title_or_id;
        } else {
            throw new InvalidArgumentException("Need to specify the project in find_users_in_project");
        }

        if (ctype_digit($user_name_or_id)) {
            $wheres[] = " u.id = ?";
            $args[] = (int)$user_name_or_id;
        } elseif (trim($user_name_or_id)) {
            $wheres[] = " u.flow_user_name = ?";
            $args[] = $user_name_or_id;
        }

        $where_condition = 2;
        if (count($wheres)) {
            $where_condition = implode(' AND ',$wheres);
        }


        $sql = "SELECT 
                f.id,
                f.created_at_ts,
                HEX(f.flow_project_user_guid) as flow_project_user_guid,
                f.flow_project_id,
                f.flow_user_id,
                f.can_write,
                f.can_read,
                u.flow_user_name,
                u.flow_user_guid
                FROM flow_project_users f 
                INNER JOIN flow_users u on f.flow_user_id = u.id
                INNER JOIN flow_projects p on f.flow_project_id = p.id
                WHERE 1 AND $where_condition
                ";

        try {
            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

            $ret = [];
            foreach ($res as $row) {
                $ret[] = new FlowProjectUser($row);
            }
            return  $ret;
        } catch (Exception $e) {
            static::get_logger()->alert("FlowProjectUser model cannot find_one ",['exception'=>$e]);
            throw $e;
        }
    }


}