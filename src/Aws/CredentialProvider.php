<?php
declare(strict_types=1);

namespace Triniti\Aws;

use Aws\Credentials\CredentialProvider as AwsCredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Credentials\CredentialsInterface;
use Aws\PsrCacheAdapter;
use Psr\Cache\CacheItemPoolInterface;

final class CredentialProvider
{
    /**
     * Unwraps the promise for credentials so services expecting
     * a ready to use CredentialsInterface still work.
     *
     * @param CacheItemPoolInterface $pool
     *
     * @return CredentialsInterface
     */
    public static function create(?CacheItemPoolInterface $pool = null): CredentialsInterface
    {
        if ('true' === ($_SERVER['BUILDING_IMAGE'] ?? 'false')) {
            return new Credentials('key', 'secret');
        }

        $cloudProvider = $_SERVER['CLOUD_PROVIDER'] ?? 'private';

        if ('private' === $cloudProvider || null === $pool) {
            $provider = AwsCredentialProvider::defaultProvider([
                'credentials' => $pool ? new PsrCacheAdapter($pool) : null,
            ]);
        } else {
            $provider = AwsCredentialProvider::cache(
                AwsCredentialProvider::ecsCredentials(),
                new PsrCacheAdapter($pool),
                'aws_cached_ecs_credentials'
            );
        }

        return $provider()->wait();
    }
}
