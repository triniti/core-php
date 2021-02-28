<?php
declare(strict_types=1);

namespace Triniti\OvpMediaLive;

use Aws\Credentials\CredentialsInterface;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\OvpMediaLive\Exception\MediaLiveChannelNotStarted;
use Triniti\Schemas\OvpMedialive\Enum\ChannelState;
use Triniti\Schemas\OvpMedialive\Event\ChannelStartedV1;

class StartChannelHandler implements CommandHandler
{
    use MediaLiveTrait;

    protected Ncr $ncr;

    public static function handlesCuries(): array
    {
        return [
            'triniti:ovp.medialive:command:start-channel',
        ];
    }

    public function __construct(Ncr $ncr, CredentialsInterface $credentials)
    {
        $this->ncr = $ncr;
        $this->credentials = $credentials;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $context = ['causator' => $command];
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $node = $this->ncr->getNode($nodeRef, true, $context);

        $channelData = $this->getChannelData($node);
        $client = $this->createMediaLiveClient($channelData['region']);
        $description = $client->describeChannel(['ChannelId' => $channelData['channelId']]);
        $retries = $command->get('ctx_retries');
        $pbjx->cancelJobs(["{$nodeRef}.stop-medialive-channel"], $context);

        if (ChannelState::RUNNING === $description->get('State')) {
            if ($retries < 1) {
                // if this is the first attempt to start the channel and it's
                // already running then no event is needed.
                return;
            }

            $event = ChannelStartedV1::create()->set('node_ref', $nodeRef);
            $pbjx->copyContext($command, $event);
            $streamId = StreamId::fromString(sprintf(
                '%s:%s.medialive-history:%s',
                $nodeRef->getVendor(),
                $nodeRef->getLabel(),
                $nodeRef->getId()
            ));
            $pbjx->getEventStore()->putEvents($streamId, [$event], null, $context);
            return;
        }

        if (ChannelState::STARTING !== $description->get('State')) {
            try {
                $client->startChannel(['ChannelId' => $channelData['channelId']]);
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
            $pbjx->sendAt($newCommand, $timestamp, "{$nodeRef}.start-medialive-channel", $context);
            return;
        }

        throw new MediaLiveChannelNotStarted(sprintf(
            '[%s] After trying five times, MediaLive Channel [%s] was not started.',
            $nodeRef,
            $node->get('medialive_channel_arn')
        ));
    }
}
