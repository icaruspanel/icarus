<?php
declare(strict_types=1);

namespace Icarus\Domain\User;

use Carbon\CarbonImmutable;
use Icarus\Domain\Shared\HasEvents;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\Shared\RecordsEvents;
use Icarus\Domain\User\Events\UserEmailChanged;
use Icarus\Domain\User\Events\UserPasswordChanged;
use Icarus\Domain\User\Events\UserRegistered;
use SensitiveParameter;

final class User implements RecordsEvents
{
    use HasEvents;

    public static function register(
        string                       $name,
        string                       $email,
        #[SensitiveParameter] string $password,
        ?CarbonImmutable             $verifiedAt = null
    ): self
    {
        $user = new self(
            UserId::generate(),
            $name,
            UserEmail::create($email, $verifiedAt),
            HashedPassword::from($password)
        );

        $user->recordEvent(new UserRegistered($user->id, $name, $user->email->email));

        return $user;
    }

    public readonly UserId $id;

    private(set) string $name;

    private(set) UserEmail $email;

    private(set) HashedPassword $password;

    private(set) bool $active;

    /**
     * @var list<\Icarus\Domain\Shared\OperatingContext>
     */
    private(set) array $operatesIn = [];

    /**
     * @param \Icarus\Domain\User\UserId                   $id
     * @param string                                       $name
     * @param \Icarus\Domain\User\UserEmail                $email
     * @param \Icarus\Domain\User\HashedPassword           $password
     * @param list<\Icarus\Domain\Shared\OperatingContext> $operatesIn
     * @param bool                                         $active
     */
    public function __construct(
        UserId         $id,
        string         $name,
        UserEmail      $email,
        HashedPassword $password,
        array          $operatesIn = [],
        bool           $active = true
    )
    {
        $this->id         = $id;
        $this->name       = $name;
        $this->email      = $email;
        $this->password   = $password;
        $this->operatesIn = $operatesIn;
        $this->active     = $active;
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

        $this->password = HashedPassword::from($password);

        $this->recordEvent(new UserPasswordChanged($this->id));

        return $this;
    }

    /**
     * @param \Icarus\Domain\Shared\OperatingContext ...$context
     *
     * @return bool
     */
    public function canOperateIn(OperatingContext ...$context): bool
    {
        return array_all(
            $context,
            fn (OperatingContext $operatingContext) => in_array(
                $operatingContext,
                $this->operatesIn,
                true
            )
        );
    }

    /**
     * Add operating contexts the user operates in.
     *
     * @param \Icarus\Domain\Shared\OperatingContext ...$context
     *
     * @return $this
     */
    public function addOperatesIn(OperatingContext ...$context): self
    {
        $this->operatesIn = array_values(array_unique(array_merge($this->operatesIn, $context), SORT_REGULAR));

        return $this;
    }

    /**
     * Remove operating contexts the user operates in.
     *
     * @param \Icarus\Domain\Shared\OperatingContext ...$context
     *
     * @return $this
     */
    public function removeOperatesIn(OperatingContext ...$context): self
    {
        $this->operatesIn = array_values(array_filter(
            $this->operatesIn,
            static fn (OperatingContext $existing) => ! in_array($existing, $context, true),
        ));

        return $this;
    }

    /**
     * Activate the user.
     *
     * @return $this
     */
    public function activate(): self
    {
        $this->active = true;

        return $this;
    }

    /**
     * Deactivate the user.
     *
     * @return $this
     */
    public function deactivate(): self
    {
        $this->active = false;

        return $this;
    }

    /**
     * Check if the user is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }
}
