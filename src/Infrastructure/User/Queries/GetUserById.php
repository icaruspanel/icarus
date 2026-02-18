<?php
declare(strict_types=1);

namespace Icarus\Infrastructure\User\Queries;

use Carbon\CarbonImmutable;
use Icarus\Domain\User\ReadModels\UserResult;
use Icarus\Domain\User\UserId;
use Icarus\Infrastructure\User\IlluminateUserRepository;
use Illuminate\Database\ConnectionInterface;

final readonly class GetUserById
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function execute(UserId $userId): ?UserResult
    {
        $result = $this->connection
            ->table(IlluminateUserRepository::TABLE)
            ->select([
                'name',
                'email',
                'verified_at',
            ])
            ->where('id', $userId)
            ->first();

        if ($result === null) {
            return null;
        }

        $result = (array)$result;

        /**
         * @var array{
         *     name: string,
         *     email: string,
         *     verified_at: ?string,
         * } $result
         */

        return new UserResult(
            $userId,
            $result['name'],
            $result['email'],
            $result['verified_at'] !== null ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $result['verified_at']) : null
        );
    }
}
