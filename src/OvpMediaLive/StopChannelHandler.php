<?php
declare(strict_types=1);

namespace Triniti\OvpMediaLive;

use Aws\Credentials\CredentialsInterface;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\OvpMediaLive\Exception\MediaLiveChannelNotStopped;
use Triniti\Schemas\OvpMedialive\Enum\ChannelState;
use Triniti\Schemas\OvpMedialive\Event\ChannelStoppedV1;

class StopChannelHandler implements CommandHandler
{
    use MediaLiveTrait;

    protected Ncr $ncr;

    public function __construct(Ncr $ncr, CredentialsInterface $credentials)
    {
        $this->ncr = $ncr;
        $this->credentials = $credentials;
    }

    public static function handlesCuries(): array
    {
        return [
            'triniti:ovp.medialive:command:start-channel'
        ];
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $nodeRef = $command->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true);

        $channelData = $this->getChannelData($node);
        $client = $this->createMediaLiveClient($channelData['region']);
        $description = $client->describeChannel(['ChannelId' => $channelData['channelId']]);
        $retries = $command->get('ctx_retries');
        $pbjx->cancelJobs(["{$nodeRef}.start-medialive-channel"]);

        if (ChannelState::IDLE === $description->get('State')) {
            if ($retries < 1) {
                // if this is the first attempt to stop the channel and it's
                // already stopped then no event is needed.
                return;
            }

            $event = ChannelStoppedV1::create()->set('node_ref', $nodeRef);
            $pbjx->copyContext($command, $event);
            $streamId = StreamId::fromString(sprintf('%s.medialive-history:%s', $nodeRef->getLabel(), $nodeRef->getId()));
            $pbjx->getEventStore()->putEvents($streamId, [$event]);
            return;
        }

        if (ChannelState::STOPPING !== $description->get('State')) {
            try {
                $client->stopChannel(['ChannelId' => $channelData['channelId']]);
            } catch (\Throwable $e) {
                // exceptions thrown below
            }
        }

        if ($retries < 5) {
            $newCommand = clone $command;
            ++$retries;
            $timestamp = strtotime('+' . (30 * $retries) . ' seconds');
            $newCommand->set('ctx_retries', $retries);
            $pbjx->copyContext($command, $newCommand);
            $pbjx->sendAt($newCommand, $timestamp, "{$nodeRef}.stop-medialive-channel");
            return;
        }

        throw new MediaLiveChannelNotStopped(sprintf(
            '[%s] After trying five times, MediaLive Channel [%s] was not stopped.',
            $nodeRef,
            $node->get('medialive_channel_arn')
        ));
    }
}
