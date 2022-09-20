<?php
declare(strict_types=1);

namespace Eme\Marketing;

use Aws\DynamoDb\DynamoDbClient;
use Eme\Schemas\Marketing\Node\CampaignStatsV1;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\Repository\DynamoDb\NodeTable;
use Gdbots\Ncr\Repository\DynamoDb\TableManager;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxProjector;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;

class NcrCampaignStatsProjector implements EventSubscriber, PbjxProjector
{
    public static function getSubscribedEvents(): array
    {
        return [
            'eme:marketing:node:campaign.created'          => 'onCreatedOrStarted',
            'eme:marketing:node:campaign.campaign-started' => 'onCreatedOrStarted',
            'eme:marketing:event:email-failed'             => 'onEmailFailed',
            'eme:marketing:event:email-opened'             => 'onEmailOpened',
            'eme:marketing:event:email-sent'               => 'onEmailSent',
            'eme:marketing:event:unsubscribed-from-groups' => 'onUnsubscribedFromGroups',
        ];
    }

    public function __construct(
        private DynamoDbClient $client,
        private TableManager   $tableManager,
        private Ncr            $ncr,
        private bool           $enabled = true
    ) {
    }

    public function onCreatedOrStarted(NodeProjectedEvent $pbjxEvent): void
    {
        if (!$this->enabled) {
            return;
        }

        $event = $pbjxEvent->getLastEvent();
        if ($event->isReplay()) {
            return;
        }

        $campaign = $pbjxEvent->getNode();
        $stats = $this->createStatsNode($campaign);
        $context = ['causator' => $event];
        $this->ncr->putNode($stats, null, $context);
    }

    public function onEmailFailed(Message $event, Pbjx $pbjx): void
    {
        $field = 'bounced';
        if ($event->get('is_complaint')) {
            $field = 'complaints';
        }

        $this->incrementField($event, $field);
    }

    public function onEmailOpened(Message $event, Pbjx $pbjx): void
    {
        $this->incrementField($event, 'opened');
    }

    public function onEmailSent(Message $event, Pbjx $pbjx): void
    {
        $this->incrementField($event, 'delivered');
    }

    public function onUnsubscribedFromGroups(Message $event, Pbjx $pbjx): void
    {
        $this->incrementField($event, 'unsubscribed');
    }

    private function incrementField(Message $event, string $field): void
    {
        if (!$this->enabled || $event->isReplay()) {
            return;
        }

        if (!$event->has('campaign_ref')) {
            return;
        }

        $context = [
            'causator'  => $event,
            'tenant_id' => $event->get('ctx_tenant_id'),
        ];
        /** @var NodeRef $campaignRef */
        $campaignRef = $event->get('campaign_ref');
        $statsRef = $this->createStatsRef($campaignRef);
        $tableName = $this->tableManager->getNodeTableName($statsRef->getQName(), $context);

        $params = [
            'TableName'                 => $tableName,
            'Key'                       => [
                NodeTable::HASH_KEY_NAME => ['S' => $statsRef->toString()],
            ],
            'UpdateExpression'          => 'set #field = #field + :v_incr',
            'ExpressionAttributeNames'  => [
                '#field' => $field,
            ],
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

    private function createStatsNode(Message $campaign): Message
    {
        return CampaignStatsV1::fromArray([
            'tenant_id'   => $campaign->fget('tenant_id'),
            '_id'         => $campaign->fget('_id'),
            'created_at'  => $campaign->fget('created_at'),
            'creator_ref' => $campaign->fget('creator_ref'),
            'etag'        => $campaign->fget('etag'),
            'title'       => $campaign->fget('title'),
            'status'      => $campaign->fget('status'),
        ]);
    }

    private function createStatsRef(NodeRef $nodeRef): NodeRef
    {
        $label = $nodeRef->getLabel();
        return NodeRef::fromString(str_replace("{$label}:", "{$label}-stats:", $nodeRef->toString()));
    }
}
