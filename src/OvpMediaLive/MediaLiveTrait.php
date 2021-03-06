<?php
declare(strict_types=1);

namespace Triniti\OvpMediaLive;

use Aws\Credentials\CredentialsInterface;
use Aws\MediaLive\MediaLiveClient;
use Aws\MediaPackage\MediaPackageClient;
use Gdbots\Ncr\Exception\InvalidArgumentException;
use Gdbots\Pbj\Message;

trait MediaLiveTrait
{
    protected CredentialsInterface $credentials;

    protected function getChannelData(Message $node): array
    {
        if (!$node->has('medialive_channel_arn')) {
            throw new InvalidArgumentException(sprintf(
                '[%s] does not have a medialive_channel_arn.',
                $node->generateNodeRef()
            ));
        }

        $arn = $node->get('medialive_channel_arn');
        $explodedArn = explode(':', $arn);
        $region = null;

        try {
            $region = $explodedArn[3];
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(sprintf(
                '[%s] has an invalid medialive_channel_arn [%s]',
                $node->generateNodeRef(),
                $arn
            ));
        }

        if (1 !== preg_match('/^[a-z]{2}-[a-z]+-[0-9]{1}$/', $region)) {
            throw new InvalidArgumentException(sprintf(
                '[%s] has an invalid medialive_channel_arn region [%s]',
                $node->generateNodeRef(),
                $region
            ));
        }

        return [
            'region'    => $region,
            'channelId' => $explodedArn[count($explodedArn) - 1],
        ];
    }

    protected function createMediaLiveClient(string $region): MediaLiveClient
    {
        return new MediaLiveClient([
            'region'      => $region,
            'version'     => '2017-10-14',
            'credentials' => $this->credentials,
        ]);
    }

    protected function createMediaPackageClient(string $region): MediaPackageClient
    {
        return new MediaPackageClient([
            'region'      => $region,
            'version'     => '2017-10-12',
            'credentials' => $this->credentials,
        ]);
    }
}
