<?php
declare(strict_types=1);

namespace Icarus\Domain\AuthToken\Commands;

use Icarus\Domain\AuthToken\AuthToken;
use Icarus\Domain\AuthToken\AuthTokenRepository;
use Icarus\Domain\AuthToken\Events\UserAuthenticated;
use Icarus\Domain\AuthToken\Exceptions\InvalidCredentials;
use Icarus\Domain\AuthToken\Exceptions\UnableToAuthenticate;
use Icarus\Domain\AuthToken\Hooks\AuthenticatingUser;
use Icarus\Domain\AuthToken\Hooks\AuthenticationAttempting;
use Icarus\Domain\AuthToken\Hooks\AuthenticationAuthorising;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserRepository;
use Random\RandomException;

/**
 * Authenticate User Handler
 *
 * This is a handler for the {@see \Icarus\Domain\AuthToken\Commands\AuthenticateUser} command.
 *
 * This handler will create an {@see AuthToken} for a {@see User} once they have
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
final class AuthenticateUserHandler
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
     * @var \Icarus\Domain\Shared\EventDispatcher
     */
    private EventDispatcher $dispatcher;

    /**
     * @param \Icarus\Domain\AuthToken\AuthTokenRepository $authTokenRepository
     * @param \Icarus\Domain\User\UserRepository           $userRepository
     * @param \Icarus\Domain\Shared\EventDispatcher        $dispatcher
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
     * @param \Icarus\Domain\AuthToken\Commands\AuthenticateUser $command
     *
     * @return \Icarus\Domain\AuthToken\Commands\AuthenticationResult
     *
     * @throws \Icarus\Domain\AuthToken\Exceptions\AuthenticationFailed
     */
    public function handle(AuthenticateUser $command): AuthenticationResult
    {
        $this->gateAttempt($command);

        $user = $this->userRepository->findByEmail($command->email);

        if ($user === null) {
            throw InvalidCredentials::make();
        }

        if ($user->password->verify($command->password) === false) {
            throw InvalidCredentials::make();
        }

        if ($user->isActive() === false) {
            throw UnableToAuthenticate::make('User is inactive', $user->id);
        }

        if ($user->canOperateIn($command->context) === false) {
            throw UnableToAuthenticate::make('User cannot operate in this context', $user->id);
        }

        $this->gateAuthorisation($command, $user);

        try {
            $token = StoredToken::create($command->context);
        } catch (RandomException $e) {                                                        // @codeCoverageIgnore
            throw UnableToAuthenticate::make('Unable to generate auth token', $user->id, $e); // @codeCoverageIgnore
        }

        $authToken = AuthToken::create(
            $user->id,
            $command->context,
            $token->token,
            $command->device
        );

        if ($this->authTokenRepository->save($authToken) === false) {
            throw UnableToAuthenticate::make('Unable to save auth token', $user->id);
        }

        $this->dispatcher->dispatch(new UserAuthenticated(
            $user->id,
            $authToken->id,
            $command->device,
            $command->context
        ));

        return new AuthenticationResult(
            $user->id,
            $token->unhashedToken,
            $authToken->id,
            $command->context,
            $authToken->expiresAt
        );
    }

    /**
     * Gate the authentication attempt.
     *
     * @param \Icarus\Domain\AuthToken\Commands\AuthenticateUser $command
     *
     * @return void
     */
    private function gateAttempt(AuthenticateUser $command): void
    {
        $gate = new AuthenticationAttempting(
            $command->email,
            $command->device,
            $command->context
        );

        $this->dispatcher->dispatch($gate);

        if ($gate->isCancelled()) {
            throw UnableToAuthenticate::make($gate->getCancelReason() ?? 'Authentication attempt was cancelled');
        }
    }

    /**
     * Gate the user's authorisation.
     *
     * @param \Icarus\Domain\AuthToken\Commands\AuthenticateUser $command
     * @param \Icarus\Domain\User\User                           $user
     *
     * @return void
     */
    private function gateAuthorisation(AuthenticateUser $command, User $user): void
    {
        $gate = new AuthenticationAuthorising(
            new AuthenticatingUser(
                $user->id,
                $user->name,
                $user->email->email,
                $user->email->verifiedAt,
            ),
            $command->device,
            $command->context
        );

        $this->dispatcher->dispatch($gate);

        if ($gate->isCancelled()) {
            throw UnableToAuthenticate::make($gate->getCancelReason() ?? 'Authentication attempt was cancelled');
        }
    }
}
