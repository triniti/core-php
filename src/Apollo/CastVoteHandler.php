<?php
declare(strict_types=1);

namespace Triniti\Apollo;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\Util\ShardUtil;
use Gdbots\Schemas\Pbjx\StreamId;

class CastVoteHandler implements CommandHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 4.x
        $curies = MessageResolver::findAllUsingMixin('triniti:apollo:mixin:cast-vote:v1', false);
        $curies[] = 'triniti:apollo:command:cast-vote';
        return $curies;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if (!$command->has('poll_ref') || !$command->has('answer_id')) {
            return;
        }

        /** @var NodeRef $pollRef */
        $pollRef = $command->get('poll_ref');
        $commandId = $command->fget('command_id');

        $s256 = ShardUtil::determineShard($commandId, 256);
        $s32 = ShardUtil::determineShard($commandId, 32);
        $s16 = ShardUtil::determineShard($commandId, 16);
        $streamId = StreamId::fromString(
            sprintf('%s:%s.votes:%s:%s', $pollRef->getVendor(), $pollRef->getLabel(), $pollRef->getId(), $s256)
        );

        $event = $this->createVoteCasted($command, $pbjx);
        $pbjx->copyContext($command, $event);
        $event
            ->set('poll_ref', $pollRef)
            ->set('answer_id', $command->get('answer_id'))
            ->set('s256', $s256)
            ->set('s32', $s32)
            ->set('s16', $s16);

        $context = ['causator' => $command];
        $pbjx->getEventStore()->putEvents($streamId, [$event], null, $context);
    }

    protected function createVoteCasted(Message $command, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:apollo:event:vote-casted:v1')::create();
    }
}
