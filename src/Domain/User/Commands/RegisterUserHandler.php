<?php
declare(strict_types=1);

namespace Icarus\Domain\User\Commands;

use Icarus\Domain\Shared\EventDispatcher;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserRepository;

final readonly class RegisterUserHandler
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

    public function __invoke(RegisterUser $command): User
    {
        $user = User::register(
            $command->name,
            $command->email,
            $command->password,
            $command->verifiedAt
        );

        $this->repository->save($user);

        $this->dispatcher->dispatchFrom($user);

        return $user;
    }
}
