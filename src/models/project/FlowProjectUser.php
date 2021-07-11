<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace app\models\project;
use DI\Container;
use Exception;
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
        if (empty($object)) {return;}
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
     * @param ?string $id
     * @return FlowProjectUser|null
     * @throws Exception
     */
    public static function find_one(?string $id): ?FlowProjectUser
    {
        if (empty($id)) {return null;}

        $db = static::get_connection();

        $sql = "SELECT 
                id,
                created_at_ts,
                HEX(flow_project_user_guid) as flow_project_user_guid,
                flow_project_id,
                flow_user_id,
                can_write,
                can_read
                FROM flow_project_users u 
                WHERE id = ?";

        try {
            $what = $db->safeQuery($sql, [(int)$id], PDO::FETCH_OBJ);
            if (empty($what)) {
                return null;
            }
            return new FlowProjectUser($what[0]);
        } catch (Exception $e) {
            static::get_logger()->alert("FlowProjectUser model cannot find_one ",['exception'=>$e]);
            throw $e;
        }
    }


}