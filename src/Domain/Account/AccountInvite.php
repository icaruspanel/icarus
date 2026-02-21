<?php
declare(strict_types=1);

namespace Icarus\Domain\Account;

use Icarus\Domain\Account\Exceptions\InvalidUserForInvite;
use Icarus\Domain\Account\Exceptions\InviteAcceptanceMissingUser;
use Icarus\Domain\Permission\RoleId;
use Icarus\Domain\User\UserId;

final class AccountInvite
{
    /**
     * @param \Icarus\Domain\Account\AccountId $accountId
     * @param \Icarus\Domain\Permission\RoleId $roleId
     * @param \Icarus\Domain\User\UserId       $invitedBy
     * @param string|null                      $email
     * @param \Icarus\Domain\User\UserId|null  $userId
     *
     * @return self
     *
     * @throws \Random\RandomException
     */
    public static function create(
        AccountId $accountId,
        RoleId    $roleId,
        UserId    $invitedBy,
        ?string   $email = null,
        ?UserId   $userId = null
    ): self
    {
        return new self(
            AccountInviteId::generate(),
            $accountId,
            $invitedBy,
            $roleId,
            bin2hex(random_bytes(8)),
            $email,
            $userId
        );
    }

    public readonly AccountInviteId $id;

    public readonly AccountId $accountId;

    public readonly UserId $invitedBy;

    public readonly RoleId $roleId;

    public readonly string $code;

    public readonly ?string $email;

    private(set) ?UserId $userId;

    private(set) bool $accepted;

    public function __construct(
        AccountInviteId $id,
        AccountId       $accountId,
        UserId          $invitedBy,
        RoleId          $roleId,
        string          $code,
        ?string         $email = null,
        ?UserId         $userId = null,
        bool            $accepted = false
    )
    {
        $this->id        = $id;
        $this->accountId = $accountId;
        $this->invitedBy = $invitedBy;
        $this->roleId    = $roleId;
        $this->code      = $code;
        $this->email     = $email;
        $this->userId    = $userId;
        $this->accepted  = $accepted;
    }

    public function accept(?UserId $userId = null): void
    {
        $this->accepted = true;

        if ($this->userId === null && $userId === null) {
            throw InviteAcceptanceMissingUser::make();
        }

        if ($userId !== null) {
            if ($this->userId !== null && $this->userId->is($userId) === false) {
                throw InvalidUserForInvite::make($this->userId, $userId);
            }

            $this->userId = $userId;
        }
    }
}
