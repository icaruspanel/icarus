<?php
declare(strict_types=1);

namespace Icarus\Kernel\User\Actions;

use Carbon\CarbonImmutable;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserRepository;
use Icarus\Kernel\Contracts\EventDispatcher;
use SensitiveParameter;

final readonly class RegisterUser
{
    private UserRepository $repository;

    private EventDispatcher $dispatcher;

    public function __construct(
        UserRepository  $repository,
        EventDispatcher $dispatcher
    )
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(
        string                       $name,
        string                       $email,
        #[SensitiveParameter] string $password,
        ?CarbonImmutable             $verifiedAt = null
    ): User
    {
        $user = User::register(
            $name,
            $email,
            $password,
            $verifiedAt
        );

        $this->repository->save($user);

        $this->dispatcher->dispatchFrom($user);

        return $user;
    }
}
