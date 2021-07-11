<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace app\models\user;
use app\models\project\FlowProject;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserManager;
use DI\Container;
use Exception;
use InvalidArgumentException;
use ParagonIE\EasyDB\EasyDB;
use PDO;
use Psr\Log\LoggerInterface;
use Delight\Auth\Auth;

class FlowUser {
    public ?int $id;
    public ?int $created_at_ts;
    public ?string $flow_user_name;
    public ?string $flow_user_email;
    public ?string $flow_user_guid;
    public ?string $base_user_id;

    protected ?string $old_email;
    protected ?string $old_username;

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
     * @return Auth
     */
    public  static function get_user_auth() : Auth {
        try {
            return  static::$container->get('auth');
        } catch (Exception $e) {
            static::get_logger()->alert("User model cannot get the user auth",['exception'=>$e]);
            die( static::class . " Cannot get auth");
        }
    }

    /**
     * @return FlowUser
     */
    public static function get_logged_in_user() : FlowUser {
        try {
            return  static::$container->get('user');
        } catch (Exception $e) {
            static::get_logger()->alert("FlowUser cannot get logged in user info",['exception'=>$e]);
            die( static::class . " Cannot get user info");
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

    public static function get_base_details($base_user_id,&$base_email, &$base_username) :?int{
        $db = static::get_connection();
        $sql = "SELECT u.email, u.username FROM users u WHERE u.id = ?";
        $what = $db->safeQuery($sql, [(int)$base_user_id], PDO::FETCH_OBJ);
        if (empty($what)) {
            return null;
        }
        $base_email = $what[0]->email;
        $base_username = $what[0]->username;
        return (int)$base_user_id;

    }

    public function __construct($object=null){
        if (empty($object)) {
            $this->id = null;
            $this->flow_user_name = null;
            $this->flow_user_email = null;
            $this->flow_user_guid = null;
            $this->base_user_id = null;
            return;
        }
        foreach ($object as $key => $val) {
            if (property_exists($this,$key)) {
                $this->$key = $val;
            }
        }

        $this->old_email = $this->flow_user_email;
        $this->old_username = $this->flow_user_name;
    }

    /**
     * @param string $old_password
     * @param string $new_password
     * @return string
     * @return string
     * @throws Exception

     */
    public function set_password(string $old_password, string $new_password) : string {
        try {
            static::get_user_auth()->changePassword($old_password,$new_password);
            return 'Password Updated';
        }
        catch (InvalidPasswordException $e) {
            static::get_logger()->notice("User model cannot update password due to user error ",['exception'=>$e]);
            return $e->getMessage();
        }
        catch (TooManyRequestsException $e) {
            static::get_logger()->notice("User model cannot update password due to timeout",['exception'=>$e]);
            return $e->getMessage();
        }
        catch (Exception $e) {
            static::get_logger()->error("User model cannot update password ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function save() {
        $db = null;

        $b_match = FlowProject::check_valid_title($this->flow_user_name);
        if (!$b_match) {
            throw new InvalidArgumentException("Project Title needs to be all alpha numeric or dash only. First character cannot be a number");
        }
        try {
            $db = static::get_connection();
            $db->beginTransaction();
            if ($this->flow_user_guid) {
                $db->update('flow_users',[
                    'flow_user_name' => $this->flow_user_name,
                    'flow_user_email' => $this->flow_user_email
                ],[
                    'id' => $this->id
                ]);

                if ($this->old_email !== $this->flow_user_email) {
                    static::get_user_auth()->changeEmail($this->flow_user_email, function() {
                        static::get_logger()->notice("User email changed from $this->old_email to $this->flow_user_email");
                    });
                }

                if ($this->old_username !== $this->flow_user_name) {
                    $db->update('users',[
                        'username' => $this->flow_user_name,
                    ],[
                        'id' => $this->base_user_id
                    ]);
                    $_SESSION[UserManager::SESSION_FIELD_USERNAME] = $this->flow_user_name;
                }

            } else {
                $db->insert('flow_users',[
                    'flow_user_name' => $this->flow_user_name,
                    'flow_user_email' => $this->flow_user_email,
                    'base_user_id' => $this->base_user_id
                ]);
            }

            $db->commit();


        } catch (Exception $e) {
            $db AND $db->rollBack();
            static::get_logger()->alert("User model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ?string $user_name_or_base_user_id
     * @return FlowUser|null
     * @throws Exception
     */
    public static function find_one(?string $user_name_or_base_user_id): ?FlowUser
    {
        $db = static::get_connection();

        if (ctype_digit($user_name_or_base_user_id)) {
            $where_condition = " u.base_user_id = ?";
            $arg = (int)$user_name_or_base_user_id;
        } else {
            $where_condition = " u.flow_user_name = ?";
            $arg = $user_name_or_base_user_id;
        }


        $sql = "SELECT 
                id,
                base_user_id,
                created_at_ts,
                HEX(flow_user_guid) as flow_user_guid,
                flow_user_name,
                flow_user_email
                FROM flow_users u 
                WHERE 1 AND $where_condition";

        try {
            $what = $db->safeQuery($sql, [$arg], PDO::FETCH_OBJ);
            if (empty($what)) {
                return null;
            }
            return new FlowUser($what[0]);
        } catch (Exception $e) {
            static::get_logger()->alert("User model cannot find_one ",['exception'=>$e]);
            throw $e;
        }
    }


}