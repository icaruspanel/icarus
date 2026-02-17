<?php
declare(strict_types=1);

namespace Icarus\Domain\User;

use Icarus\Domain\Shared\HasEvents;
use Icarus\Domain\Shared\RecordsEvents;
use Icarus\Domain\User\Events\UserEmailChanged;
use Icarus\Domain\User\Events\UserPasswordChanged;
use SensitiveParameter;

final class User implements RecordsEvents
{
    use HasEvents;

    public readonly UserId $id;

    private(set) string $name;

    private(set) UserEmail $email;

    private(set) HashedPassword $password;

    public function __construct(
        UserId         $id,
        string         $name,
        UserEmail      $email,
        HashedPassword $password
    )
    {
        $this->id       = $id;
        $this->name     = $name;
        $this->email    = $email;
        $this->password = $password;
    }

    /**
     * Change the users email address.
     *
     * @param string $email
     *
     * @return $this
     */
    public function changeEmail(string $email): self
    {
        if ($email === $this->email->email) {
            return $this;
        }

        $oldEmail    = $this->email;
        $this->email = UserEmail::unverified($email);

        $this->recordEvent(new UserEmailChanged(
                $this->id,
                $oldEmail->email,
                $this->email->email)
        );

        return $this;
    }

    /**
     * Change the users' password.
     *
     * @param string $password
     *
     * @return $this
     */
    public function changePassword(#[SensitiveParameter] string $password): self
    {
        if ($this->password->verify($password)) {
            return $this;
        }

        $oldPassword    = $this->password;
        $this->password = HashedPassword::from($password);

        $this->recordEvent(new UserPasswordChanged(
            $this->id,
            $oldPassword->hash,
            $this->password->hash
        ));

        return $this;
    }
}
