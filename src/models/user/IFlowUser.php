<?php

namespace app\models\user;

use app\models\project\FlowProjectUser;
use app\models\user\auth\AuthError;
use app\models\user\auth\EmailNotVerifiedException;
use app\models\user\auth\InvalidEmailException;
use app\models\user\auth\NotLoggedInException;
use app\models\user\auth\TooManyRequestsException;
use app\models\user\auth\UserAlreadyExistsException;
use Exception;

interface IFlowUser
{
    const DEFAULT_USER_PAGE_SIZE = 20;
    const MAX_SIZE_NAME = 39;
    const SESSION_USER_KEY = 'flow_user';

    /**
     * @return int|null
     */
    public function getFlowUserId(): ?int;

    /**
     * @param int|null $flow_user_id
     */
    public function setFlowUserId(?int $flow_user_id): void;

    /**
     * @return int|null
     */
    public function getFlowUserCreatedAtTs(): ?int;

    /**
     * @param int|null $flow_user_created_at_ts
     */
    public function setFlowUserCreatedAtTs(?int $flow_user_created_at_ts): void;

    /**
     * @return int|null
     */
    public function getLastLoggedInPageTs(): ?int;

    /**
     * @param int|null $last_logged_in_page_ts
     */
    public function setLastLoggedInPageTs(?int $last_logged_in_page_ts): void;

    /**
     * @return string|null
     */
    public function getFlowUserName(): ?string;

    /**
     * @param string|null $flow_user_name
     */
    public function setFlowUserName(?string $flow_user_name): void;

    /**
     * @return string|null
     */
    public function getFlowUserEmail(): ?string;

    /**
     * @param string|null $flow_user_email
     */
    public function setFlowUserEmail(?string $flow_user_email): void;

    /**
     * @return string|null
     */
    public function getFlowUserGuid(): ?string;

    /**
     * @param string|null $flow_user_guid
     */
    public function setFlowUserGuid(?string $flow_user_guid): void;

    /**
     * @return string|null
     */
    public function getBaseUserId(): ?string;

    /**
     * @param string|null $base_user_id
     */
    public function setBaseUserId(?string $base_user_id): void;


    /**
     * @return FlowProjectUser[]
     */
    public function getPermissions(): array;

    public function max_name(): int;

    public function ping();

    /**
     * @param string $old_password
     * @param string $new_password
     * @return void
     * @throws Exception
     */
    public function set_password(string $old_password, string $new_password): void;

    /**
     * @param bool $b_do_transaction
     * @throws EmailNotVerifiedException
     * @throws InvalidEmailException
     * @throws NotLoggedInException
     * @throws TooManyRequestsException
     * @throws UserAlreadyExistsException
     * @throws AuthError
     */
    public function save(bool $b_do_transaction = true);

    public function jsonSerialize(): array;
}