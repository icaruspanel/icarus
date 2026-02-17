<?php
declare(strict_types=1);

namespace Icarus\Domain\User\Commands;

use Icarus\Domain\User\User;
use Icarus\Domain\User\UserRepository;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $repository
    )
    {
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

        return $user;
    }
}
