<?php
declare(strict_types=1);

namespace Triniti\Apollo;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\Util\ShardUtil;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Schemas\Apollo\Event\ReactionsAddedV1;

class AddReactionsHandler implements CommandHandler
{
    public static function handlesCuries(): array
    {
        return ['triniti:apollo:command:add-reactions'];
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if (!$command->has('node_ref') || !$command->has('reactions')) {
            return;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $commandId = $command->fget('command_id');

        $s256 = ShardUtil::determineShard($commandId, 256);
        $s32 = ShardUtil::determineShard($commandId, 32);
        $s16 = ShardUtil::determineShard($commandId, 16);
        $streamId = StreamId::fromString(
            sprintf('%s:%s.reactions:%s:%s', $nodeRef->getVendor(), $nodeRef->getLabel(), $nodeRef->getId(), $s256)
        );

        $event = ReactionsAddedV1::create();
        $pbjx->copyContext($command, $event);
        $event
            ->set('node_ref', $nodeRef)
            ->addToSet('reactions', $command->get('reactions'))
            ->set('s256', $s256)
            ->set('s32', $s32)
            ->set('s16', $s16);

        $context = ['causator' => $command];
        $pbjx->getEventStore()->putEvents($streamId, [$event], null, $context);
    }
}
