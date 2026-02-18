<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Icarus\Domain\User\HashedPassword;
use Icarus\Domain\User\User;
use Icarus\Domain\User\UserEmail;
use Icarus\Domain\User\UserId;
use Icarus\Domain\User\UserRepository;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * @var \Icarus\Domain\User\UserRepository
     */
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->repository->save(
            $user = new User(
                UserId::generate(),
                'Test User',
                UserEmail::create('test@example.com', CarbonImmutable::create(1988, 6, 24, 2, 0, 0)),
                HashedPassword::from('password'),
            )
        );

        $this->command->info('Test user created: ' . $user->email->email . ':' . $user->id->id);
    }
}
