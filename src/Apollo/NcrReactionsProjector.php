<?php
declare(strict_types=1);

namespace Triniti\Apollo;

use Gdbots\Ncr\Aggregate;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\Microtime;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

class NcrReactionsProjector implements EventSubscriber, PbjxProjector
{
    protected Ncr $ncr;
    protected NcrSearch $ncrSearch;
    protected bool $enabled;

    public static function getSubscribedEvents(): array
    {
        return [
            'gdbots:ncr:mixin:node.projected'       => 'onNodeProjected',
            'triniti:apollo:event:reactions-added'  => 'onReactionsAdded',
        ];
    }

    public function __construct(Ncr $ncr, NcrSearch $ncrSearch, bool $enabled = true)
    {
        $this->ncr = $ncr;
        $this->ncrSearch = $ncrSearch;
        $this->enabled = $enabled;
    }

    public function onNodeProjected(NodeProjectedEvent $pbjxEvent): void
    {
        if (!$this->enabled) {
            return;
        }

        $node = $pbjxEvent->getNode();
        $nodeRef = $node->generateNodeRef();
        $lastEvent = $pbjxEvent->getLastEvent();

        if (!$this->shouldHaveReactions($nodeRef)){
            return;
        }

        if (NodeStatus::DELETED->value === $node->fget('status')) {
            $context = ['causator' => $lastEvent];
            $reactionsRef = $this->createReactionsRef($nodeRef);
            $this->ncr->deleteNode($reactionsRef, $context);
            $this->ncrSearch->deleteNodes([$reactionsRef], $context);
            return;
        }

        $reactions = $this->getOrCreateReactions($nodeRef, $lastEvent);
        $this->mergeNode($node, $reactions);
        $this->projectNode($reactions, $lastEvent, $pbjxEvent::getPbjx());
    }

    public function onReactionsAdded(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->shouldHaveReactions($event->get('node_ref'))){
            return;
        }

        $reactions = $this->getOrCreateReactions($event->get('node_ref'), $event);

        foreach ($event->get('reactions') as $reaction) {
            if ($reactions->isInMap('reactions', $reaction)) {
                $reactions->addToMap('reactions', $reaction, $reactions->getFromMap('reactions', $reaction) + 1);
            }
        }

        $this->projectNode($reactions, $event, $pbjx);
    }

    protected function projectNode(Message $reactions, Message $event, Pbjx $pbjx): void
    {
        $context = ['causator' => $event];
        $reactions
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('etag', Aggregate::generateEtag($reactions));

        $this->ncr->putNode($reactions, null, $context);
        $this->ncrSearch->indexNodes([$reactions], $context);
        $pbjx->trigger($reactions, 'projected', new NodeProjectedEvent($reactions, $event), false, false);
    }

    protected function getOrCreateReactions(NodeRef $nodeRef, Message $event): Message
    {
        static $class = null;
        if (null === $class) {
            $class = MessageResolver::resolveCurie('*:apollo:node:reactions:v1');
        }

        try {
            $reactionsRef = $this->createReactionsRef($nodeRef);
            $reactions = $this->ncr->getNode($reactionsRef, true, ['causator' => $event]);
        } catch (NodeNotFound $nf) {
            $reactions = $class::fromArray(['_id' => $nodeRef->getId()]);
            $this->addReactions($reactions);
        } catch (\Throwable $e) {
            throw $e;
        }

        return $reactions;
    }

    protected function addReactions(Message $reactions): void
    {
        foreach ([
                    'love',
                    'haha',
                    'wow',
                    'wtf',
                    'trash',
                    'sad',
                ] as $reactionType) {
            $reactions->addToMap('reactions', $reactionType, 0);
        }
    }

    protected function mergeNode(Message $node, Message $reactions): void
    {
        $reactions
            ->set('_id', $node->get('_id'))
            ->set('status', $node->get('status'))
            ->set('title', $node->get('title'))
            ->set('target', $node::schema()->getQName()->getMessage());

        if ($node->has('published_at')) {
            $createdAt = Microtime::fromDateTime($node->get('published_at'));
        } else {
            $createdAt = $node->get('created_at');
        }

        $reactions->set('created_at', $createdAt);
    }

    protected function createReactionsRef(NodeRef $nodeRef): NodeRef
    {
        return NodeRef::fromString(str_replace($nodeRef->getLabel() . ':', 'reactions:', $nodeRef->toString()));
    }

    protected function shouldHaveReactions(NodeRef $nodeRef): bool
    {
        // override to implement other nodes that should have reactions, by default, only articles have reactions.
        $types = [
            'article' => true,
        ];

        return $types[$nodeRef->getLabel()] ?? false;
    }
}

