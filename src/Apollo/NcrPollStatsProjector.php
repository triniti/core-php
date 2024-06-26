<?php
declare(strict_types=1);

namespace Triniti\Apollo;

use Aws\DynamoDb\DynamoDbClient;
use Gdbots\Ncr\Aggregate;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Ncr;
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
        protected TableManager   $tableManager,
        protected Ncr            $ncr,
        protected bool           $enabled = true
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
        $context = ['causator' => $lastEvent];

        if (NodeStatus::DELETED->value === $poll->fget('status')) {
            $statsRef = $this->createStatsRef($pollRef);
            $this->ncr->deleteNode($statsRef, $context);
            return;
        }

        if (!$this->ncr->hasNode($this->createStatsRef($pollRef), true, $context)) {
            $stats = $this->createStats($pollRef, $lastEvent);
            $this->mergePoll($poll, $stats);
            $this->projectNode($stats, $lastEvent, $pbjxEvent::getPbjx());
        }
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
            'causator'  => $event,
            'tenant_id' => $event->fget('ctx_tenant_id'),
        ];

        $nodeRef = $event->get('poll_ref');
        $statsRef = $this->createStatsRef($nodeRef);
        $tableName = $this->tableManager->getNodeTableName($statsRef->getQName(), $context);
        $updateExpression = 'add answer_votes.#answer_id :v_incr, votes :v_incr';
        $expressionAttributeNames['#answer_id'] = $event->fget('answer_id');

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
            ->set('etag', Aggregate::generateEtag($stats))
            ->set('status', NodeStatus::PUBLISHED);

        $this->ncr->putNode($stats, $expectedEtag, $context);

        // Add empty answer_votes map to node
        $statsRef = $stats->generateNodeRef();
        $tableName = $this->tableManager->getNodeTableName($statsRef->getQName(), $context);
        $params = [
            'TableName'                 => $tableName,
            'Key'                       => [
                NodeTable::HASH_KEY_NAME => ['S' => $statsRef->toString()],
            ],
            'UpdateExpression'          => 'set answer_votes = :answer_votes_map',
            'ExpressionAttributeValues' => [
                ':answer_votes_map' => ['M' => []],
            ],
            'ReturnValues'              => 'NONE',
        ];

        $this->client->updateItem($params);
        $pbjx->trigger($stats, 'projected', new NodeProjectedEvent($stats, $event), false, false);
    }

    protected function createStats(NodeRef $pollRef, Message $event): Message
    {
        static $class = null;
        if (null === $class) {
            $class = MessageResolver::resolveCurie('*:apollo:node:poll-stats:v1');
        }

        return $class::fromArray(['_id' => $pollRef->getId()]);
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
