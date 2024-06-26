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
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;

class NcrReactionsProjector implements EventSubscriber, PbjxProjector
{
    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:apollo:mixin:has-reactions.created' => 'onNodeCreated',
            'triniti:apollo:mixin:has-reactions.deleted' => 'onNodeDeleted',
            'triniti:apollo:event:reactions-added'       => 'onReactionsAdded',
        ];
    }

    public function __construct(
        protected DynamoDbClient $client,
        protected TableManager   $tableManager,
        protected Ncr            $ncr,
        protected bool           $enabled = true
    ) {
    }

    public function onNodeCreated(NodeProjectedEvent $pbjxEvent): void
    {
        if (!$this->enabled) {
            return;
        }

        $node = $pbjxEvent->getNode();
        $nodeRef = $node->generateNodeRef();
        $lastEvent = $pbjxEvent->getLastEvent();

        $reactions = $this->createReactions($nodeRef);
        $this->mergeNode($node, $reactions);
        $this->projectNode($reactions, $lastEvent, $pbjxEvent::getPbjx());
    }

    public function onNodeDeleted(NodeProjectedEvent $pbjxEvent): void
    {
        if (!$this->enabled) {
            return;
        }

        $node = $pbjxEvent->getMessage();
        $nodeRef = $node->generateNodeRef();
        $lastEvent = $pbjxEvent->getLastEvent();

        $context = ['causator' => $lastEvent];
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $this->ncr->deleteNode($reactionsRef, $context);
    }

    public function onReactionsAdded(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$event->has('node_ref')) {
            return;
        }

        if (!$this->ncr->hasNode($this->createReactionsRef($event->get('node_ref')), true, ['causator' => $event])) {
            return;
        }

        $this->incrementReactions($event);
    }

    protected function incrementReactions(Message $event): void
    {
        $updateExpression = '';
        $expressionAttributeNames = [];

        $context = [
            'causator'  => $event,
            'tenant_id' => $event->get('ctx_tenant_id'),
        ];

        $nodeRef = $event->get('node_ref');
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $tableName = $this->tableManager->getNodeTableName($reactionsRef->getQName(), $context);

        foreach ($event->get('reactions') as $reaction) {
            $updateExpression .= " reactions.#{$reaction} :v_incr,";
            $expressionAttributeNames["#{$reaction}"] = $reaction;
        }

        $params = [
            'TableName'                 => $tableName,
            'Key'                       => [
                NodeTable::HASH_KEY_NAME => ['S' => $reactionsRef->toString()],
            ],
            'UpdateExpression'          => rtrim('add' . $updateExpression, ', '),
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
        $this->ncr->getNode($reactionsRef, true, $context);
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

        // Add empty reactions map to node
        $reactionsRef = NodeRef::fromNode($reactions);
        $tableName = $this->tableManager->getNodeTableName($reactionsRef->getQName(), $context);
        $params = [
            'TableName'                 => $tableName,
            'Key'                       => [
                NodeTable::HASH_KEY_NAME => ['S' => $reactionsRef->toString()],
            ],
            'UpdateExpression'          => 'set reactions = :reactions_map',
            'ExpressionAttributeValues' => [
                ':reactions_map' => ['M' => []],
            ],
            'ReturnValues'              => 'NONE',
        ];

        $this->client->updateItem($params);
        $pbjx->trigger($reactions, 'projected', new NodeProjectedEvent($reactions, $event), false, false);
    }

    protected function createReactions(NodeRef $nodeRef): Message
    {
        static $class = null;
        if (null === $class) {
            $class = MessageResolver::resolveCurie('*:apollo:node:reactions:v1');
        }

        return $class::fromArray(['_id' => $nodeRef->getId()]);
    }

    protected function mergeNode(Message $node, Message $reactions): void
    {
        $reactions
            ->set('_id', $node->get('_id'))
            ->set('status', NodeStatus::PUBLISHED)
            ->set('title', $node->get('title'))
            ->set('target', $node::schema()->getQName()->getMessage())
            ->set('created_at', $node->get('created_at'));
    }

    protected function createReactionsRef(NodeRef $nodeRef): NodeRef
    {
        return NodeRef::fromString(str_replace($nodeRef->getLabel() . ':', 'reactions:', $nodeRef->toString()));
    }
}
