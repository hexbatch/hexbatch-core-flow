<?php

namespace app\models\user;

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
use app\models\user\auth\UserNotFoundException;

interface IFlowUserAuth
{
    /**
     * Attempts to sign up a user
     *
     * If you want the user's account to be activated by default, pass `null` as the callback
     *
     * If you want to make the user verify their email address first, pass an anonymous function as the callback
     *
     * The callback function must have the following signature:
     *
     * `function ($selector, $token)`
     *
     * Both pieces of information must be sent to the user, usually embedded in a link
     *
     * When the user wants to verify their email address as a next step, both pieces will be required again
     *
     * @param string $email the email address to register
     * @param string $password the password for the new account
     * @param string|null $username (optional) the username that will be displayed
     * @param callable|null $callback (optional) the function that sends the confirmation email to the user
     * @return int the ID of the user that has been created (if any)
     * @throws InvalidEmailException if the email address was invalid
     * @throws InvalidPasswordException if the password was invalid
     * @throws UserAlreadyExistsException if a user with the specified email address already exists
     * @throws TooManyRequestsException if the number of allowed attempts/requests has been exceeded
     * @throws AuthError if an internal problem occurred (do *not* catch)
     *
     * @see confirmEmail
     * @see confirmEmailAndSignIn
     */
    public function register(string $email, string  $password, string  $username = null, callable $callback = null): int ;



    /**
     * Attempts to sign in a user with their email address and password
     *
     * @param string $email the user's email address
     * @param string $password the user's password
     * @param int|null $rememberDuration (optional) the duration in seconds to keep the user logged in ("remember me"), e.g. `60 * 60 * 24 * 365.25` for one year
     * @param callable|null $onBeforeSuccess (optional) a function that receives the user's ID as its single parameter and is executed before successful authentication; must return `true` to proceed or `false` to cancel
     * @throws InvalidEmailException if the email address was invalid or could not be found
     * @throws InvalidPasswordException if the password was invalid
     * @throws EmailNotVerifiedException if the email address has not been verified yet via confirmation email
     * @throws AttemptCancelledException if the attempt has been cancelled by the supplied callback that is executed before success
     * @throws TooManyRequestsException if the number of allowed attempts/requests has been exceeded
     * @throws AuthError if an internal problem occurred (do *not* catch)
     */
    public function login(string $email, string $password, ?int $rememberDuration = null, callable $onBeforeSuccess = null) : void;



    /**
     * Attempts to sign in a user with their username and password
     *
     * When using this method to authenticate users, you should ensure that usernames are unique
     *
     * Consistently using {@see registerWithUniqueUsername} instead of {@see register} can be helpful
     *
     * @param string $username the user's username
     * @param string $password the user's password
     * @param int|null $rememberDuration (optional) the duration in seconds to keep the user logged in ("remember me"), e.g. `60 * 60 * 24 * 365.25` for one year
     * @param callable|null $onBeforeSuccess (optional) a function that receives the user's ID as its single parameter and is executed before successful authentication; must return `true` to proceed or `false` to cancel
     * @throws UnknownUsernameException if the specified username does not exist
     * @throws AmbiguousUsernameException if the specified username is ambiguous, i.e. there are multiple users with that name
     * @throws InvalidPasswordException if the password was invalid
     * @throws EmailNotVerifiedException if the email address has not been verified yet via confirmation email
     * @throws AttemptCancelledException if the attempt has been cancelled by the supplied callback that is executed before success
     * @throws TooManyRequestsException if the number of allowed attempts/requests has been exceeded
     * @throws AuthError if an internal problem occurred (do *not* catch)
     */
    public function loginWithUsername(string $username, string $password, ?int $rememberDuration = null, callable $onBeforeSuccess = null) : void;



    /**
     * Logs the user out
     *
     * @throws AuthError if an internal problem occurred (do *not* catch)
     */
    public function logOut();



    /**
     * Confirms an email address (and activates the account) by supplying the correct selector/token pair
     *
     * The selector/token pair must have been generated previously by registering a new account
     *
     * @param string $selector the selector from the selector/token pair
     * @param string $token the token from the selector/token pair
     * @return string[] an array with the old email address (if any) at index zero and the new email address (which has just been verified) at index one
     * @throws InvalidSelectorTokenPairException if either the selector or the token was not correct
     * @throws TokenExpiredException if the token has already expired
     * @throws UserAlreadyExistsException if an attempt has been made to change the email address to a (now) occupied address
     * @throws TooManyRequestsException if the number of allowed attempts/requests has been exceeded
     * @throws AuthError if an internal problem occurred (do *not* catch)
     */
    public function confirmEmail(string $selector, string $token) : array ;



    /**
     * Changes the currently signed-in user's password while requiring the old password for verification
     *
     * @param string $oldPassword the old password to verify account ownership
     * @param string $newPassword the new password that should be set
     * @throws NotLoggedInException if the user is not currently signed in
     * @throws InvalidPasswordException if either the old password has been wrong or the desired new one has been invalid
     * @throws TooManyRequestsException if the number of allowed attempts/requests has been exceeded
     * @throws AuthError if an internal problem occurred (do *not* catch)
     */
    public function changePassword(string $oldPassword, string  $newPassword) : void ;



    /**
     * Attempts to change the email address of the currently signed-in user (which requires confirmation)
     *
     * The callback function must have the following signature:
     *
     * `function ($selector, $token)`
     *
     * Both pieces of information must be sent to the user, usually embedded in a link
     *
     * When the user wants to verify their email address as a next step, both pieces will be required again
     *
     * @param string $newEmail the desired new email address
     * @param callable $callback the function that sends the confirmation email to the user
     * @throws InvalidEmailException if the desired new email address is invalid
     * @throws UserAlreadyExistsException if a user with the desired new email address already exists
     * @throws EmailNotVerifiedException if the current (old) email address has not been verified yet
     * @throws NotLoggedInException if the user is not currently signed in
     * @throws TooManyRequestsException if the number of allowed attempts/requests has been exceeded
     * @throws AuthError if an internal problem occurred (do *not* catch)
     *
     * @see confirmEmail
     * @see confirmEmailAndSignIn
     */
    public function changeEmail(string $newEmail, callable $callback) : void ;


    public function changeUsername(string $new_user_name, callable $callback) : void;



    /**
     * Returns whether the user is currently logged in by reading from the session
     *
     * @return boolean whether the user is logged in or not
     */
    public function isLoggedIn() :bool ;



    /**
     * Returns the currently signed-in user's ID by reading from the session
     *
     * @return int|null the user ID
     */
    public function getUserId() : ?int ;


    /**
     * @param int $base_user_id
     * @return string|null
     * @throws UserNotFoundException
     */
    public function getUserName(int $base_user_id) : ?string ;

    /**
     * @param int $base_user_id
     * @return string|null
     * @throws UserNotFoundException
     */
    public function getUserEmail(int $base_user_id) : ?string ;


}