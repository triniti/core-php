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

class NcrPollStatsProjector implements EventSubscriber, PbjxProjector
{
    protected Ncr $ncr;
    protected NcrSearch $ncrSearch;
    protected bool $enabled;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:apollo:mixin:poll.projected' => 'onPollProjected',
            'triniti:apollo:event:vote-casted'    => 'onVoteCasted',

            // deprecated mixins, will be removed in 3.x
            'triniti:apollo:mixin:vote-casted'    => 'onVoteCasted',
        ];
    }

    public function __construct(Ncr $ncr, NcrSearch $ncrSearch, bool $enabled = true)
    {
        $this->ncr = $ncr;
        $this->ncrSearch = $ncrSearch;
        $this->enabled = $enabled;
    }

    public function onPollProjected(NodeProjectedEvent $pbjxEvent): void
    {
        if (!$this->enabled) {
            return;
        }

        $poll = $pbjxEvent->getNode();
        $pollRef = $poll->generateNodeRef();
        $lastEvent = $pbjxEvent->getLastEvent();

        if (NodeStatus::DELETED->value === $poll->fget('status')) {
            $context = ['causator' => $lastEvent];
            $statsRef = $this->createStatsRef($pollRef);
            $this->ncr->deleteNode($statsRef, $context);
            $this->ncrSearch->deleteNodes([$statsRef], $context);
            return;
        }

        $stats = $this->getOrCreateStats($pollRef, $lastEvent);
        $this->mergePoll($poll, $stats);
        $this->projectNode($stats, $lastEvent, $pbjxEvent::getPbjx());
    }

    public function onVoteCasted(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        $stats = $this->getOrCreateStats($event->get('poll_ref'), $event);
        $stats->set('votes', $stats->get('votes') + 1);
        $answerId = (string)$event->get('answer_id');
        $answerVotes = $stats->getFromMap('answer_votes', $answerId, 0) + 1;
        $stats->addToMap('answer_votes', $answerId, $answerVotes);
        $this->projectNode($stats, $event, $pbjx);
    }

    protected function projectNode(Message $stats, Message $event, Pbjx $pbjx): void
    {
        $context = ['causator' => $event];
        // todo: convert stats projection to dynamodb atomic operation
        $expectedEtag = null; // $event->isReplay() ? null : $stats->get('etag');
        $stats
            ->set('updated_at', $event->get('occurred_at'))
            ->set('updater_ref', $event->get('ctx_user_ref'))
            ->set('last_event_ref', $event->generateMessageRef())
            ->set('etag', Aggregate::generateEtag($stats));

        $this->ncr->putNode($stats, $expectedEtag, $context);
        $this->ncrSearch->indexNodes([$stats], $context);
        $pbjx->trigger($stats, 'projected', new NodeProjectedEvent($stats, $event), false, false);
    }

    protected function getOrCreateStats(NodeRef $pollRef, Message $event): Message
    {
        static $class = null;
        if (null === $class) {
            $class = MessageResolver::resolveCurie('*:apollo:node:poll-stats:v1');
        }

        try {
            $statsRef = $this->createStatsRef($pollRef);
            $stats = $this->ncr->getNode($statsRef, true, ['causator' => $event]);
        } catch (NodeNotFound $nf) {
            $stats = $class::fromArray(['_id' => $pollRef->getId()]);
        } catch (\Throwable $e) {
            throw $e;
        }

        return $stats;
    }

    protected function createStatsRef(NodeRef $pollRef): NodeRef
    {
        return NodeRef::fromString(str_replace('poll:', 'poll-stats:', $pollRef->toString()));
    }

    protected function mergePoll(Message $poll, Message $stats): void
    {
        $stats
            ->set('_id', $poll->get('_id'))
            ->set('status', $poll->get('status'))
            ->set('title', $poll->get('title'));

        if ($poll->has('published_at')) {
            $createdAt = Microtime::fromDateTime($poll->get('published_at'));
        } else {
            $createdAt = $poll->get('created_at');
        }

        $stats->set('created_at', $createdAt);
    }
}
