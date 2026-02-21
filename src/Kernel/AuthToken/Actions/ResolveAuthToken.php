<?php
declare(strict_types=1);

namespace Icarus\Kernel\AuthToken\Actions;

use Carbon\CarbonImmutable;
use Icarus\Domain\AuthToken\AuthTokenId;
use Icarus\Kernel\AuthToken\IlluminateAuthTokenRepository;
use Icarus\Domain\AuthToken\DataObjects\AuthTokenResult;
use Icarus\Domain\AuthToken\StoredToken;
use Icarus\Domain\Shared\OperatingContext;
use Icarus\Domain\User\UserId;
use Illuminate\Database\ConnectionInterface;

final readonly class ResolveAuthToken
{
    /**
     * @var \Illuminate\Database\ConnectionInterface
     */
    private ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function execute(string $selector): ?AuthTokenResult
    {
        $result = $this->connection
            ->table(IlluminateAuthTokenRepository::TABLE)
            ->select([
                'id',
                'user_id',
                'selector',
                'secret',
                'context',
                'expires_at',
                'revoked_at',
            ])
            ->where('selector', '=', $selector)
            ->first();

        if ($result === null) {
            return null;
        }

        $result = (array)$result;

        /**
         * @var array{
         *     id: string,
         *     user_id: string,
         *     selector: string,
         *     secret: string,
         *     context: string,
         *     expires_at: ?string,
         *     revoked_at: ?string,
         * } $result
         */

        return new AuthTokenResult(
            new AuthTokenId($result['id']),
            new UserId($result['user_id']),
            new StoredToken($result['selector'], $result['secret']),
            OperatingContext::from($result['context']),
            $result['expires_at'] ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $result['expires_at']) : null,
            $result['revoked_at'] ? CarbonImmutable::createFromFormat('Y-m-d H:i:s', $result['revoked_at']) : null
        );
    }
}
