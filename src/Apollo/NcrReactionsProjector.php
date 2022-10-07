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

class NcrReactionsProjector implements EventSubscriber, PbjxProjector
{
    public static function getSubscribedEvents(): array
    {
        return [
            'gdbots:ncr:mixin:node.projected'      => 'onNodeProjected',
            'triniti:apollo:event:reactions-added' => 'onReactionsAdded',
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

    public function onNodeProjected(NodeProjectedEvent $pbjxEvent): void
    {
        if (!$this->enabled) {
            return;
        }

        $node = $pbjxEvent->getNode();
        $nodeRef = $node->generateNodeRef();
        $lastEvent = $pbjxEvent->getLastEvent();

        if (!$this->hasReactions($nodeRef)) {
            return;
        }

        if (NodeStatus::DELETED->value === $node->fget('status')) {
            $context = ['causator' => $lastEvent];
            $reactionsRef = $this->createReactionsRef($nodeRef);
            $this->ncr->deleteNode($reactionsRef, $context);
            $this->ncrSearch->deleteNodes([$reactionsRef], $context);
            return;
        }

        $reactions = $this->getReactions($nodeRef, $lastEvent) ?? $this->createReactions($nodeRef);

        $this->mergeNode($node, $reactions);
        $this->projectNode($reactions, $lastEvent, $pbjxEvent::getPbjx());
    }

    public function onReactionsAdded(Message $event, Pbjx $pbjx): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$event->has('node_ref')) {
            return;
        }

        $nodeRef = $event->get('node_ref');
        if (!$this->hasReactions($nodeRef)) {
            return;
        }

        $reactions = $this->getReactions($nodeRef, $event);
        if (!$reactions) {
            return;
        }

        $this->incrementReactions($reactions, $event);
    }

    protected function incrementReactions(Message $reactions, Message $event): void
    {
        $updateExpression = '';
        $expressionAttributeNames = [];

        $context = [
            'causator' => $event,
            'tenant_id' => $event->get('ctx_tenant_id'),
        ];

        $nodeRef = $event->get('node_ref');
        $reactionsRef = $this->createReactionsRef($nodeRef);
        $tableName = $this->tableManager->getNodeTableName($reactionsRef->getQName(), $context);

        foreach ($event->get('reactions') as $reaction) {
            if ($reactions->isInMap('reactions', $reaction)) {
                $updateExpression .= " reactions.#{$reaction} = reactions.#{$reaction} + :v_incr,";
                $expressionAttributeNames["#{$reaction}"] = $reaction;
            }
        }

        if (empty($expressionAttributeNames)) {
            return;
        }

        $params = [
            'TableName'                 => $tableName,
            'Key'                       => [
                NodeTable::HASH_KEY_NAME => ['S' => $reactionsRef->toString()],
            ],
            'UpdateExpression'          =>  rtrim('set'. $updateExpression, ', '),
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
        $reactions = $this->ncr->getNode($reactionsRef, true, $context);
        $this->ncrSearch->indexNodes([$reactions], $context);
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

    protected function createReactions(NodeRef $nodeRef): Message
    {
        static $class = null;
        if (null === $class) {
            $class = MessageResolver::resolveCurie('*:apollo:node:reactions:v1');
        }

        $reactions = $class::fromArray(['_id' => $nodeRef->getId()]);
        $this->addReactionTypes($reactions);

        return $reactions;
    }

    protected function getReactions(NodeRef $nodeRef, Message $event): ?Message
    {
        try {
            $reactionsRef = $this->createReactionsRef($nodeRef);
            return $this->ncr->getNode($reactionsRef, true, ['causator' => $event]);
        } catch (NodeNotFound $nf) {
            // Node not found do nothing here
        } catch (\Throwable $e) {
            throw $e;
        }

        return null;
    }

    protected function addReactionTypes(Message $reactions): void
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

    protected function hasReactions(NodeRef $nodeRef): bool
    {
        return MessageResolver::resolveQName($nodeRef->getQName())::schema()->hasMixin('triniti:apollo:mixin:has-reactions');
    }
}
