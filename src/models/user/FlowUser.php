<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace app\models\user;
use app\helpers\UserHelper;
use app\models\base\FlowBase;
use app\models\project\FlowProjectUser;

use app\models\user\auth\AuthError;
use app\models\user\auth\AuthException;
use app\models\user\auth\EmailNotVerifiedException;
use app\models\user\auth\InvalidEmailException;
use app\models\user\auth\InvalidPasswordException;
use app\models\user\auth\NotLoggedInException;
use app\models\user\auth\TooManyRequestsException;
use app\models\user\auth\UserAlreadyExistsException;
use app\models\user\bridge\FlowUserAuthDelightBridge;
use Exception;
use InvalidArgumentException;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;
use PDO;

class FlowUser extends FlowBase implements JsonSerializable, IFlowUser
{


    protected ?int $flow_user_id;
    protected ?int $flow_user_created_at_ts;
    protected ?int $last_logged_in_page_ts;
    protected ?string $flow_user_name;
    protected ?string $flow_user_email;
    protected ?string $flow_user_guid;
    protected ?string $base_user_id;

    protected ?string $old_email;
    protected ?string $old_username;

    /**
     * @return IFlowUserAuth
     */
    public static function create_auth() : IFlowUserAuth {

        return new FlowUserAuthDelightBridge();
    }

    /**
     * @return int|null
     */
    public function getFlowUserId(): ?int
    {
        return $this->flow_user_id;
    }

    /**
     * @param int|null $flow_user_id
     */
    public function setFlowUserId(?int $flow_user_id): void
    {
        $this->flow_user_id = $flow_user_id;
    }

    /**
     * @return int|null
     */
    public function getFlowUserCreatedAtTs(): ?int
    {
        return $this->flow_user_created_at_ts;
    }

    /**
     * @param int|null $flow_user_created_at_ts
     */
    public function setFlowUserCreatedAtTs(?int $flow_user_created_at_ts): void
    {
        $this->flow_user_created_at_ts = $flow_user_created_at_ts;
    }

    /**
     * @return int|null
     */
    public function getLastLoggedInPageTs(): ?int
    {
        return $this->last_logged_in_page_ts;
    }

    /**
     * @param int|null $last_logged_in_page_ts
     */
    public function setLastLoggedInPageTs(?int $last_logged_in_page_ts): void
    {
        $this->last_logged_in_page_ts = $last_logged_in_page_ts;
    }

    /**
     * @return string|null
     */
    public function getFlowUserName(): ?string
    {
        return $this->flow_user_name;
    }

    /**
     * @param string|null $flow_user_name
     */
    public function setFlowUserName(?string $flow_user_name): void
    {
        $this->flow_user_name = $flow_user_name;
    }

    /**
     * @return string|null
     */
    public function getFlowUserEmail(): ?string
    {
        return $this->flow_user_email;
    }

    /**
     * @param string|null $flow_user_email
     */
    public function setFlowUserEmail(?string $flow_user_email): void
    {
        $this->flow_user_email = $flow_user_email;
    }

    /**
     * @return string|null
     */
    public function getFlowUserGuid(): ?string
    {
        return $this->flow_user_guid;
    }

    /**
     * @param string|null $flow_user_guid
     */
    public function setFlowUserGuid(?string $flow_user_guid): void
    {
        $this->flow_user_guid = $flow_user_guid;
    }

    /**
     * @return string|null
     */
    public function getBaseUserId(): ?string
    {
        return $this->base_user_id;
    }

    /**
     * @param string|null $base_user_id
     */
    public function setBaseUserId(?string $base_user_id): void
    {
        $this->base_user_id = $base_user_id;
    }


    /**
     * @var FlowProjectUser[] $permissions
     */
    protected array $permissions = [];

    /**
     * @return FlowProjectUser[]
     */
    public function getPermissions() :array {
        return $this->permissions;
    }

    /** @noinspection PhpUnused */
    public  function max_name(): int
    {
        return IFlowUser::MAX_SIZE_NAME;
    }



    public  static function get_user_auth() : IFlowUserAuth {
        try {
            return  static::$container->get('auth');
        } catch (Exception $e) {
            static::get_logger()->alert("User model cannot get the user auth",['exception'=>$e]);
            die( static::class . " Cannot get auth");
        }
    }

    /**
     * @return IFlowUser|null
     */
    public static function get_logged_in_user() : ?IFlowUser {
        try {
            return  static::$container->get('user');
        } catch (Exception $e) {
            static::get_logger()->alert("FlowUser cannot get logged in user info",['exception'=>$e]);
            die( static::class . " Cannot get user info");
        }
    }



    public function __construct($object=null){
        parent::__construct();
        if (empty($object)) {
            $this->flow_user_id = null;
            $this->flow_user_name = null;
            $this->flow_user_email = null;
            $this->flow_user_guid = null;
            $this->base_user_id = null;
            $this->flow_user_created_at_ts = null;
            $this->last_logged_in_page_ts = null;
            $this->permissions = [];
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

    public function ping() {
        $db = static::get_connection();
        $this->last_logged_in_page_ts = time();
        $db->update('flow_users',[
            'last_logged_in_page_ts' => $this->last_logged_in_page_ts
        ],[
            'id' => $this->flow_user_id
        ]);
    }



    /**
     * @param string $old_password
     * @param string $new_password
     * @return void
     * @throws AuthError|AuthException

     */
    public function set_password(string $old_password, string $new_password) : void {

       UserHelper::get_user_helper()->check_valid_password($new_password);

        try {
            static::get_user_auth()->changePassword($old_password,$new_password);
            return ;
        }
        catch (InvalidPasswordException $e) {
            static::get_logger()->notice("User model cannot update password due to user error ",['exception'=>$e]);
            throw $e;
        }
        catch (TooManyRequestsException $e) {
            static::get_logger()->notice("User model cannot update password due to timeout",['exception'=>$e]);
            throw $e;
        }
        catch (Exception $e) {
            static::get_logger()->error("User model cannot update password ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param bool $b_do_transaction
     * @throws AuthError
     * @throws AuthException
     */
    public function save(bool $b_do_transaction = true) {
        $db = null;

        $b_match = static::check_valid_title($this->flow_user_name);
        if (!$b_match) {
            throw new InvalidArgumentException(
                "User Name needs to be all alpha numeric or dash only. ".
                "First character cannot be a number. Name Cannot be less than 3 or greater than 40. ".
                " User Name cannot be a hex number greater than 25 and cannot be a decimal number");
        }
        try {
            $db = static::get_connection();
            if ($b_do_transaction && !$db->inTransaction()) {$db->beginTransaction();}
            if ($this->flow_user_guid) {


                try {
                    if (
                        $this->old_email !== $this->flow_user_email ||
                        static::get_user_auth()->getUserEmail($this->base_user_id) !== $this->flow_user_email
                    ) {
                        static::get_user_auth()->changeEmail($this->flow_user_email, function () {
                            static::get_logger()->notice("User email changed from $this->old_email to $this->flow_user_email");
                        });
                    }
                } catch (TooManyRequestsException ) {
                    throw new TooManyRequestsException("Cannot save email, too many requests at once");
                } catch (InvalidEmailException $too) {
                    throw new InvalidEmailException("Cannot save email, it is invalid ". $too->getMessage());
                } catch (UserAlreadyExistsException $too) {
                    throw new UserAlreadyExistsException("Cannot save email, someone is using it". $too->getMessage());
                } catch (EmailNotVerifiedException $too) {
                    throw new EmailNotVerifiedException("Cannot save email, email not verified". $too->getMessage());
                } catch (NotLoggedInException $too) {
                    throw new NotLoggedInException("Cannot save email, not logged in with subsystem". $too->getMessage());
                }


                if (
                    $this->old_username !== $this->flow_user_name ||
                    static::get_user_auth()->getUserName($this->base_user_id) !== $this->flow_user_email
                ) {
                    static::get_user_auth()->changeUsername($this->flow_user_name,function () {
                        static::get_logger()->notice("User email changed from $this->old_email to $this->flow_user_email");
                    });
                }

                $db->update('flow_users',[
                    'flow_user_name' => $this->flow_user_name,
                    'flow_user_email' => $this->flow_user_email
                ],[
                    'id' => $this->flow_user_id
                ]);

            } else {
                $db->insert('flow_users',[
                    'flow_user_name' => $this->flow_user_name,
                    'flow_user_email' => $this->flow_user_email,
                    'base_user_id' => $this->base_user_id
                ]);
            }
            if ($b_do_transaction && $db->inTransaction()) {
                $db->commit();
            }



        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            static::get_logger()->alert("User model cannot save ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param ?string $user_name_or_guid_or_base_user_id
     * @param int|null $flow_user_id
     * @return IFlowUser|null
     * @throws Exception
     */
    public static function find_one(?string $user_name_or_guid_or_base_user_id,?int $flow_user_id = null): ?IFlowUser
    {
        $db = static::get_connection();


        if ($flow_user_id) {
            $where_condition = " u.id = ?";
            $args = [(int)$flow_user_id];
        }
        else if (ctype_digit($user_name_or_guid_or_base_user_id)) {
            $where_condition = " u.base_user_id = ?";
            $args = [(int)$user_name_or_guid_or_base_user_id];
        } else {
            $where_condition = " ( u.flow_user_name = ? OR u.flow_user_guid = UNHEX(?) )";
            $args = [$user_name_or_guid_or_base_user_id,$user_name_or_guid_or_base_user_id];
        }


        $sql = "SELECT 
                id as flow_user_id,
                base_user_id,
                created_at_ts as flow_user_created_at_ts,
                last_logged_in_page_ts as last_logged_in_page_ts,
                HEX(flow_user_guid) as flow_user_guid,
                flow_user_name,
                flow_user_email
                FROM flow_users u 
                WHERE 1 AND $where_condition";

        try {
            $what = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
            if (empty($what)) {
                return null;
            }
            return new FlowUser($what[0]);
        } catch (Exception $e) {
            static::get_logger()->alert("User model cannot find_one ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * @param array $guid_array
     * @return IFlowUser[]
     * @throws Exception
     */
    public static function get_basic_info_by_guid_array(array $guid_array) : array {
        $question_marks = [];
        $args = [];
        foreach (array_unique($guid_array) as $guid) {
            if (empty(trim($guid))) {continue;}
            if (!ctype_xdigit($guid)) {continue;}
            $question_marks[] = 'UNHEX(?)';
            $args[] = $guid;
        }
        if (empty($args)) {return [];}

        $question_marks_delimited = implode(',',$question_marks);

        $sql = "SELECT 
                u.id as flow_user_id,
                u.base_user_id,
                u.created_at_ts as flow_user_created_at_ts,
                u.last_logged_in_page_ts as last_logged_in_page_ts,
                HEX(u.flow_user_guid) as flow_user_guid,
                u.flow_user_name,
                u.flow_user_email
                FROM flow_users u
                WHERE u.flow_user_guid IN ($question_marks_delimited)";

        $db = static::get_connection();
        try {
            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);
            $ret = [];

            foreach ($res as $row) {
                $ret[] = new FlowUser($row);
            }

            return  $ret;
        } catch (Exception $e) {
            static::get_logger()->alert("FlowUser model cannot find_users_by_project ",['exception'=>$e]);
            throw $e;
        }
    }

    /**
     * Finds users, and their permissions, that are in or out of a project
     * @param bool $b_in_project
     * @param string $project_guid
     * @param ?string $role_in_project
     * @param ?bool $b_is_user_guid
     * @param ?string $user_name_search_or_guid
     * @param int $page ,
     * @param int $page_size ,
     * @return IFlowUser[]
     *
     * @throws
     */
    public static function find_users_by_project(
        bool    $b_in_project ,
        string  $project_guid,
        ?string $role_in_project =null,
        ?bool   $b_is_user_guid =null,
        ?string $user_name_search_or_guid =null,
        int     $page = 1,
        int     $page_size =  IFlowUser::DEFAULT_USER_PAGE_SIZE
    ): array
    {

        $db = static::get_connection();

        $args = [];
        $args[] = $project_guid;
        if (!$b_in_project) {
            $where_flags_set = "users_in_project.can_admin = 0 AND users_in_project.can_read = 0 AND users_in_project.can_write = 0";
            if ($role_in_project) {
                switch ($role_in_project) {
                    case 'admin': {
                        $where_flags_set = "users_in_project.can_admin = 0";
                        break;
                    }
                    case 'write': {
                        $where_flags_set = "users_in_project.can_write = 0";
                        break;
                    }
                    case 'read': {
                        $where_flags_set = "users_in_project.can_read = 0";
                        break;
                    }
                    default: {
                        throw new InvalidArgumentException("role needs to be read|write|admin");
                    }
                }
            }
        }

        if ($user_name_search_or_guid) {
            if ($b_is_user_guid) {
                $where_user = "u.flow_user_guid = UNHEX(?)";
                $args[] = $user_name_search_or_guid;
            } else {
                $where_user = "u.flow_user_name LIKE ?";
                $args[] = $user_name_search_or_guid . '%';
            }
        } else {
            $where_user = 4;
        }



        $start_place = ($page - 1) * $page_size;

        $columns = "
                u.id as flow_user_id,
                u.base_user_id,
                u.created_at_ts as flow_user_created_at_ts,
                u.last_logged_in_page_ts as last_logged_in_page_ts,
                HEX(u.flow_user_guid) as flow_user_guid,
                u.flow_user_name,
                u.flow_user_email,
                f.id as flow_project_user_id,
                f.created_at_ts as flow_project_user_created_at_ts,
                HEX(f.flow_project_user_guid) as flow_project_user_guid,
                f.flow_project_id,
                f.flow_user_id,
                f.can_write,
                f.can_read,
                f.can_admin,
                HEX(p.flow_project_guid) as flow_project_guid,
                p.admin_flow_user_id,
                p.flow_project_title,
                p.flow_project_type,
                p.is_public
        ";




        if ($b_in_project) {

            //find users in project
            $sql = "
            SELECT
                $columns

            FROM flow_users u
                     INNER JOIN flow_project_users f on f.flow_user_id = u.id
                     INNER JOIN flow_projects p on f.flow_project_id = p.id
                    WHERE p.flow_project_guid = UNHEX(?) AND  $where_user
                    ORDER BY u.id ASC
                    LIMIT $start_place , $page_size
            ";
        } else {
            //find users not in project
            $sql = "
            SELECT
                $columns

            FROM flow_users u
                 INNER JOIN (
                    SELECT
                        DISTINCT u.id as flow_user_id, f.can_write, f.can_admin, f.can_read
                    FROM flow_users u
                             LEFT JOIN flow_project_users f on f.flow_user_id = u.id
                             LEFT JOIN flow_projects p on f.flow_project_id = p.id
                    LEFT JOIN (
                        SELECT
                            DISTINCT u.id as flow_user_id, f.can_write, f.can_admin, f.can_read
                        FROM flow_users u
                                 LEFT JOIN flow_project_users f on f.flow_user_id = u.id
                                 LEFT JOIN flow_projects p on f.flow_project_id = p.id
                        WHERE p.flow_project_guid = UNHEX(?)
                        ) AS users_in_project ON users_in_project.flow_user_id = u.id
                    WHERE (
                           users_in_project.flow_user_id IS NULL 
                            OR (
                                $where_flags_set
                            )
                        ) 
                      AND $where_user 
                    ORDER BY flow_user_id ASC
                    LIMIT $start_place , $page_size
                ) as driver ON driver.flow_user_id = u.id
        
                 LEFT JOIN flow_project_users f on f.flow_user_id = u.id
                 LEFT JOIN flow_projects p on f.flow_project_id = p.id
            
            WHERE 1
            ORDER BY u.flow_user_name ASC;
                
            ";

        }

        try {
            $res = $db->safeQuery($sql, $args, PDO::FETCH_OBJ);

            $ret = [];

            /**
             * @var array<string, IFlowUser> $rem_users
             */
            $rem_users = [];
            foreach ($res as $row) {
                $user_node = new FlowUser($row);
                if (array_key_exists($user_node->flow_user_guid,$rem_users)) {
                    $user_node = $rem_users[$user_node->flow_user_guid];
                } else {
                    $rem_users[$user_node->flow_user_guid] = $user_node;
                    $ret[] = $user_node;
                }

                $permission_node = new FlowProjectUser($row);
                if ($permission_node->flow_project_user_id) {
                    $user_node->permissions[] = $permission_node;
                }


            }

            return  $ret;
        } catch (Exception $e) {
            static::get_logger()->alert("FlowUser model cannot find_users_by_project ",['exception'=>$e]);
            throw $e;
        }
    }


    #[ArrayShape(["flow_user_guid" => "null|string", "flow_user_created_at_ts" => "int|null",
        "last_logged_in_page_ts" => "int|null", "flow_user_name" => "null|string", "flow_user_email" => "null|string",
        "permissions" => "\app\models\project\FlowProjectUser[]"])]
    public function jsonSerialize(): array
    {
        return [
             "flow_user_guid" => $this->flow_user_guid,
             "flow_user_created_at_ts" => $this->flow_user_created_at_ts,
             "last_logged_in_page_ts" => $this->last_logged_in_page_ts,
             "flow_user_name" => $this->flow_user_name,
             "flow_user_email" => $this->flow_user_email,
             "permissions" => $this->getPermissions()
        ];
    }
}