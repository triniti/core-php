<?php
declare(strict_types=1);

namespace Triniti\Apollo;

use Aws\DynamoDb\DynamoDbClient;
use Gdbots\Ncr\Aggregate;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Ncr\Repository\DynamoDb\NodeTable;
use Gdbots\Ncr\Repository\DynamoDb\TableManager;
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
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:apollo:mixin:poll.projected' => 'onPollProjected',
            'triniti:apollo:event:vote-casted'    => 'onVoteCasted',

            // deprecated mixins, will be removed in 3.x
            'triniti:apollo:mixin:vote-casted'    => 'onVoteCasted',
        ];
    }
    public function __construct(
        protected DynamoDbClient $client,
        protected TableManager $tableManager,
        protected Ncr $ncr,
        protected NcrSearch $ncrSearch,
        protected bool $enabled = true
    ) {
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

        if (!$this->ncr->hasNode($this->createStatsRef($event->get('poll_ref')), true, ['causator' => $event])) {
            return;
        }

        $this->incrementVote($event);
    }

    protected function incrementVote(Message $event): void
    {
        $expressionAttributeNames = [];

        $context = [
            'causator' => $event,
            'tenant_id' => $event->get('ctx_tenant_id'),
        ];

        $nodeRef = $event->get('poll_ref');
        $statsRef = $this->createStatsRef($nodeRef);
        $tableName = $this->tableManager->getNodeTableName($statsRef->getQName(), $context);
        $updateExpression = "add answer_votes.#answer_id :v_incr, votes :v_incr";
        $expressionAttributeNames["#answer_id"] = $event->get('answer_id');

        $params = [
            'TableName'                 => $tableName,
            'Key'                       => [
                NodeTable::HASH_KEY_NAME => ['S' => $statsRef->toString()],
            ],
            'UpdateExpression'          => $updateExpression,
            'ExpressionAttributeNames'  => $expressionAttributeNames,
            'ExpressionAttributeValues' => [
                ':v_incr' => ['N' => '1'],
            ],
            'ReturnValues'              => 'NONE',
        ];

        $this->client->updateItem($params);

        // this ensures the ncr cache is current
        // note that we don't put a new node as that would
        // overwrite the atomic counting above.
        $this->ncr->getNode($statsRef, true, $context);
    }

    protected function projectNode(Message $stats, Message $event, Pbjx $pbjx): void
    {
        $context = ['causator' => $event];
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
            // Before atomic counters can work need answer_votes map added to poll stats. Add a placeholder key that
            // will not be used for the poll but needed to add the answer_votes map to node.
            $stats->addToMap('answer_votes', $pollRef->getId(), 0);
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
