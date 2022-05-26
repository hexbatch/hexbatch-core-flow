<?php /** @noinspection PhpInternalEntityUsedInspection */

namespace app\models\user;

use app\models\base\FlowBase;
use Delight\Auth\Auth;

use Delight\Auth\UserManager;
use Exception;
use PDO;


use app\models\user\auth\AmbiguousUsernameException;
use app\models\user\auth\AttemptCancelledException;
use app\models\user\auth\AuthError;
use app\models\user\auth\EmailNotVerifiedException;
use app\models\user\auth\InvalidEmailException;
use app\models\user\auth\InvalidPasswordException;
use app\models\user\auth\InvalidSelectorTokenPairException;
use app\models\user\auth\NotLoggedInException;
use app\models\user\auth\TokenExpiredException;
use app\models\user\auth\TooManyRequestsException;
use app\models\user\auth\UnknownUsernameException;
use app\models\user\auth\UserAlreadyExistsException;

class FlowUserAuthDelightBridge extends FlowBase implements IFlowUserAuth {

    protected Auth $auth;

    protected static bool $b_no_auth = false;

    /**
     * @throws AuthError
     */
    public function __construct()
    {

        parent::__construct();
        if (static::$b_no_auth) {
            throw new AuthError("Auth turned off");
        }
        $db = static::get_connection();
        $pdo = $db->getPdo();
        try {
            $this->auth = new Auth($pdo);
        } catch (Exception $e) {
            throw new AuthError($e->getMessage(),$e->getCode(),$e);
        }

    }

    public function isLoggedIn(): bool {
        return $this->auth->isLoggedIn();
    }

    /**
     * @return void
     * @throws AuthError
     */
    public function logOut() : void {
        try {
            $this->auth->logOut();
        } catch (Exception $e) {
            throw new AuthError($e->getMessage(),$e->getCode(),$e);
        }

    }

    /**
     * @param string $username
     * @param string $password
     * @param int|null $rememberDuration
     * @param callable|null $onBeforeSuccess
     * @return void
     * @throws AmbiguousUsernameException
     * @throws AttemptCancelledException
     * @throws AuthError
     * @throws EmailNotVerifiedException
     * @throws InvalidPasswordException
     * @throws TooManyRequestsException
     * @throws UnknownUsernameException
     */
    public function loginWithUsername(string $username, string $password, ?int $rememberDuration = null,
                                      callable $onBeforeSuccess = null) : void {
        try {
            $this->auth->loginWithUsername($username, $password, $rememberDuration, $onBeforeSuccess);
        } catch (\Delight\Auth\AmbiguousUsernameException $e) {
            throw new AmbiguousUsernameException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\AttemptCancelledException $e) {
            throw new AttemptCancelledException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\AuthError $e) {
            throw new AuthError($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            throw new EmailNotVerifiedException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            throw new InvalidPasswordException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new TooManyRequestsException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\UnknownUsernameException $e) {
            throw new UnknownUsernameException($e->getMessage(),$e->getCode(),$e);
        }
    }

    /**
     * @param string $selector
     * @param string $token
     * @return string[]
     * @throws AuthError
     * @throws InvalidSelectorTokenPairException
     * @throws TokenExpiredException
     * @throws TooManyRequestsException
     * @throws UserAlreadyExistsException
     */
    public function confirmEmail(string $selector, string  $token) :array {
        try {
            return $this->auth->confirmEmail($selector, $token);
        } catch (\Delight\Auth\AuthError $e) {
            throw new AuthError($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\InvalidSelectorTokenPairException $e) {
            throw new InvalidSelectorTokenPairException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\TokenExpiredException $e) {
            throw new TokenExpiredException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new TooManyRequestsException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            throw new UserAlreadyExistsException($e->getMessage(),$e->getCode(),$e);
        }
    }

    /**
     * @param string $email
     * @param string $password
     * @param int|null $rememberDuration
     * @param callable|null $onBeforeSuccess
     * @return void
     * @throws AttemptCancelledException
     * @throws AuthError
     * @throws EmailNotVerifiedException
     * @throws InvalidEmailException
     * @throws InvalidPasswordException
     * @throws TooManyRequestsException
     */
    public function login(string $email, string $password, ?int $rememberDuration = null, callable $onBeforeSuccess = null) : void {
        try {
            $this->auth->login($email, $password, $rememberDuration, $onBeforeSuccess);
        } catch (\Delight\Auth\AttemptCancelledException $e) {
            throw new AttemptCancelledException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\AuthError $e) {
            throw new AuthError($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            throw new EmailNotVerifiedException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\InvalidEmailException $e) {
            throw new InvalidEmailException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            throw new InvalidPasswordException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new TooManyRequestsException($e->getMessage(),$e->getCode(),$e);
        }
    }

    public function getUserId() : ?int {
        return $this->auth->getUserId();
    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $username
     * @param callable|null $callback
     * @return int
     * @throws AuthError
     * @throws InvalidEmailException
     * @throws InvalidPasswordException
     * @throws TooManyRequestsException
     * @throws UserAlreadyExistsException
     */
    public function register(string $email, string  $password, string  $username = null, callable $callback = null): int  {
        try {
            return $this->auth->register($email, $password, $username, $callback);
        } catch (\Delight\Auth\AuthError $e) {
            throw new AuthError($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\InvalidEmailException $e) {
            throw new InvalidEmailException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            throw new InvalidPasswordException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new TooManyRequestsException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            throw new UserAlreadyExistsException($e->getMessage(),$e->getCode(),$e);
        }
    }

    /**
     * @param string $oldPassword
     * @param string $newPassword
     * @return void
     * @throws AuthError
     * @throws InvalidPasswordException
     * @throws NotLoggedInException
     * @throws TooManyRequestsException
     */
    public function changePassword(string $oldPassword, string  $newPassword) : void  {
        try {
            $this->auth->changePassword($oldPassword, $newPassword);
        } catch (\Delight\Auth\AuthError $e) {
            throw new AuthError($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\InvalidPasswordException $e) {
            throw new InvalidPasswordException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\NotLoggedInException $e) {
            throw new NotLoggedInException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new TooManyRequestsException($e->getMessage(),$e->getCode(),$e);
        }
    }


    /**
     * @param string $newEmail
     * @param callable $callback
     * @return void
     * @throws AuthError
     * @throws EmailNotVerifiedException
     * @throws InvalidEmailException
     * @throws NotLoggedInException
     * @throws TooManyRequestsException
     * @throws UserAlreadyExistsException
     */
    public function changeEmail(string $newEmail, callable $callback): void
    {
        try {
            $this->auth->changeEmail($newEmail, $callback);
        } catch (\Delight\Auth\AuthError $e) {
            throw new AuthError($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\EmailNotVerifiedException $e) {
            throw new EmailNotVerifiedException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\InvalidEmailException $e) {
            throw new InvalidEmailException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\NotLoggedInException $e) {
            throw new NotLoggedInException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\TooManyRequestsException $e) {
            throw new TooManyRequestsException($e->getMessage(),$e->getCode(),$e);
        } catch (\Delight\Auth\UserAlreadyExistsException $e) {
            throw new UserAlreadyExistsException($e->getMessage(),$e->getCode(),$e);
        }
    }

    public function changeUsername(string $new_user_name, callable $callback) : void  {
        $db = static::get_connection();
        $db->update('users',[
            'username' => $new_user_name,
        ],[
            'id' => $this->getUserId()
        ]);
        $_SESSION[UserManager::SESSION_FIELD_USERNAME] = $new_user_name;

        if (is_callable($callback)) {
            $callback();
        }

    }

    public function get_base_details(int $base_user_id,&$base_email, &$base_username) :?int {
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
}