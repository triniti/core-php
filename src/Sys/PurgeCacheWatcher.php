<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Gdbots\UriTemplate\UriTemplateService;
use Triniti\Schemas\Sys\Command\PurgeCacheV1;

class PurgeCacheWatcher implements EventSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'gdbots:ncr:mixin:node-deleted'     => 'onEvent',
            'gdbots:ncr:mixin:node-expired'     => 'onEvent',
            'gdbots:ncr:mixin:node-published'   => 'onEvent',
            'gdbots:ncr:mixin:node-updated'     => 'onEvent',
            'gdbots:ncr:mixin:node-unpublished' => 'onEvent',
        ];
    }

    public function onEvent(Message $event, Pbjx $pbjx): void
    {
        if ($event->isReplay()) {
            return;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $event->get('node_ref');
        if (!$this->isNodeSupported($nodeRef)) {
            return;
        }

        $command = PurgeCacheV1::create()->set('node_ref', $nodeRef);
        $pbjx->copyContext($event, $command);
        $pbjx->sendAt($command, strtotime('+5 minutes'), "{$nodeRef}.purge-cache");
    }

    protected function isNodeSupported(NodeRef $nodeRef): bool
    {
        return UriTemplateService::hasTemplate("{$nodeRef->getQName()}.amp");
    }
}
