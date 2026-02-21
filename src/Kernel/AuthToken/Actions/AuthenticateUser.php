<?php
declare(strict_types=1);

namespace Icarus\Kernel\AuthToken\Actions;

use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Domain\AuthToken\DataObjects\AuthenticationResult;
use Icarus\Domain\AuthToken\Device;
use Icarus\Domain\AuthToken\Events\UserAuthenticated;
use Icarus\Domain\AuthToken\Exceptions\InvalidCredentials;
use Icarus\Domain\AuthToken\Exceptions\UnableToAuthenticate;
use Icarus\Domain\AuthToken\Hooks\AuthenticatingUser;
use Icarus\Domain\AuthToken\Hooks\AuthenticationAttempting;
use Icarus\Domain\AuthToken\Hooks\AuthenticationAuthorising;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserRepository;
use Icarus\Kernel\Contracts\EventDispatcher;
use Random\RandomException;
use SensitiveParameter;

/**
 * Authenticate User
 *
 * This action will create an {@see AuthToken} for a {@see User} once they have
 * been identified from their credentials. The handler provides two gating
 * events and one notification event.
 *
 * The {@see AuthenticationAttempting} event is a pre-gate that is fired before
 * the user is resolved, so would be used for user-agent or IP bans, or
 * similar.
 *
 * The {@see AuthenticationAuthorising} event is a post-gate that is fired after
 * the user is resolved, so can be used to do further checks that should
 * prevent a user for logging in.
 *
 * Once the authentication token has been created, the {@see UserAuthenticated}
 * event is fired to notify listeners that the user has been authenticated.
 *
 * @package Icarus\Core\AuthToken
 */
final class AuthenticateUser
{
    /**
     * @var \Icarus\Domain\AuthToken\AuthTokenRepository
     */
    private AuthTokenRepository $authTokenRepository;

    /**
     * @var \Icarus\Domain\User\UserRepository
     */
    private UserRepository $userRepository;

    /**
     * @var \Icarus\Kernel\Contracts\EventDispatcher
     */
    private EventDispatcher $dispatcher;

    /**
     * @param \Icarus\Domain\AuthToken\AuthTokenRepository $authTokenRepository
     * @param \Icarus\Domain\User\UserRepository           $userRepository
     * @param \Icarus\Kernel\Contracts\EventDispatcher     $dispatcher
     */
    public function __construct(
        AuthTokenRepository $authTokenRepository,
        UserRepository      $userRepository,
        EventDispatcher     $dispatcher,
    )
    {
        $this->authTokenRepository = $authTokenRepository;
        $this->userRepository      = $userRepository;
        $this->dispatcher          = $dispatcher;
    }

    /**
     * Authenticate a user by credentials.
     *
     * @param string                                 $email
     * @param string                                 $password
     * @param \Icarus\Domain\Shared\OperatingContext $context
     * @param \Icarus\Domain\AuthToken\Device        $device
     *
     * @return \Icarus\Domain\AuthToken\DataObjects\AuthenticationResult
     *
     * @throws \Icarus\Domain\AuthToken\Exceptions\AuthenticationFailed
     */
    public function execute(
        string                       $email,
        #[SensitiveParameter] string $password,
        OperatingContext             $context,
        Device                       $device = new Device()
    ): AuthenticationResult
    {
        $this->gateAttempt($email, $context, $device);

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            throw InvalidCredentials::make();
        }

        if ($user->password->verify($password) === false) {
            throw InvalidCredentials::make();
        }

        if ($user->isActive() === false) {
            throw UnableToAuthenticate::make('User is inactive', $user->id);
        }

        if ($user->canOperateIn($context) === false) {
            throw UnableToAuthenticate::make('User cannot operate in this context', $user->id);
        }

        $this->gateAuthorisation($user, $context, $device);

        try {
            $token = StoredToken::create($context);
        } catch (RandomException $e) {                                                        // @codeCoverageIgnore
            throw UnableToAuthenticate::make('Unable to generate auth token', $user->id, $e); // @codeCoverageIgnore
        }

        $authToken = AuthToken::create(
            $user->id,
            $context,
            $token->token,
            $device
        );

        if ($this->authTokenRepository->save($authToken) === false) {
            throw UnableToAuthenticate::make('Unable to save auth token', $user->id);
        }

        $this->dispatcher->dispatch(new UserAuthenticated(
            $user->id,
            $authToken->id,
            $device,
            $context
        ));

        return new AuthenticationResult(
            $user->id,
            $token->unhashedToken,
            $authToken->id,
            $context,
            $authToken->expiresAt
        );
    }

    /**
     * Gate the authentication attempt.
     *
     * @param string                                 $email
     * @param \Icarus\Domain\Shared\OperatingContext $context
     * @param \Icarus\Domain\AuthToken\Device|null   $device
     *
     * @return void
     */
    private function gateAttempt(string $email, OperatingContext $context, ?Device $device = null): void
    {
        $gate = new AuthenticationAttempting(
            $email,
            $device,
            $context
        );

        $this->dispatcher->dispatch($gate);

        if ($gate->isCancelled()) {
            throw UnableToAuthenticate::make($gate->getCancelReason() ?? 'Authentication attempt was cancelled');
        }
    }

    /**
     * Gate the user's authorisation.
     *
     * @param \Icarus\Domain\User\User               $user
     * @param \Icarus\Domain\Shared\OperatingContext $operatingContext
     * @param \Icarus\Domain\AuthToken\Device|null   $device
     *
     * @return void
     */
    private function gateAuthorisation(User $user, OperatingContext $operatingContext, ?Device $device = null): void
    {
        $gate = new AuthenticationAuthorising(
            new AuthenticatingUser(
                $user->id,
                $user->name,
                $user->email->email,
                $user->email->verifiedAt,
            ),
            $device,
            $operatingContext
        );

        $this->dispatcher->dispatch($gate);

        if ($gate->isCancelled()) {
            throw UnableToAuthenticate::make($gate->getCancelReason() ?? 'Authentication attempt was cancelled');
        }
    }
}
