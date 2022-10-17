<?php
declare(strict_types=1);

namespace Triniti\Apollo;

use Gdbots\Pbj\Assertion;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

class ReactionsValidator implements EventSubscriber, PbjxValidator
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:apollo:command:add-reactions.validate' => 'validateAddReactions',
        ];
    }

    public function validateAddReactions(PbjxEvent $pbjxEvent): void
    {
        $reactions = [];

        $command = $pbjxEvent->getMessage();
        Assertion::true($this->hasReactions($command->get('node_ref')), 'Node does not support reactions.');
        Assertion::true($command->has('node_ref'), 'Field "node_ref" is required.');
        Assertion::true($command->has('reactions'), 'Field "reactions" is required.');

        $validReactions = $this->getReactions();
        foreach ($command->get('reactions') as $reaction) {
            Assertion::inArray($reaction, $validReactions, 'Invalid reaction type.');
        }

        $command
            ->clear('reactions')
            ->addToSet('reactions', $reactions);
    }

    public function getReactions(): array
    {
        // override to implement your own reaction types
        return [
            'love',
            'haha',
            'wow',
            'wtf',
            'trash',
            'sad',
        ];
    }

    protected function hasReactions(NodeRef $nodeRef): bool
    {
        return MessageResolver::resolveQName($nodeRef->getQName())::schema()->hasMixin('triniti:apollo:mixin:has-reactions');
    }
}
