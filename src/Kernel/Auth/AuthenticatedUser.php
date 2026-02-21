<?php
declare(strict_types=1);

namespace Icarus\Kernel\Auth;

use Icarus\Domain\Shared\AuthContext;
use Icarus\Domain\User\DataObjects\UserResult;
use Illuminate\Contracts\Auth\Authenticatable;

final readonly class AuthenticatedUser implements Authenticatable
{
    public static function create(UserResult $user, AuthContext $context): self
    {
        return new self($context, $user->name, $user->email);
    }

    public function __construct(
        public AuthContext $authContext,
        public string      $name,
        public string      $email
    )
    {
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifier(): string
    {
        return $this->authContext->userId->id;
    }

    /**
     * Get the name of the password attribute for the user.
     *
     * @return string
     */
    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return '';
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken(): string
    {
        return '';
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value
     *
     * @return void
     */
    public function setRememberToken($value): void
    {
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return '';
    }
}
