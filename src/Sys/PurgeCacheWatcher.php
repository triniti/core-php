<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\UriTemplate\UriTemplateService;
use Triniti\Schemas\Sys\Command\PurgeCacheV1;

class PurgeCacheWatcher implements EventSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'gdbots:ncr:mixin:node.deleted'     => 'onNodeProjected',
            'gdbots:ncr:mixin:node.expired'     => 'onNodeProjected',
            'gdbots:ncr:mixin:node.published'   => 'onNodeProjected',
            'gdbots:ncr:mixin:node.updated'     => 'onNodeProjected',
            'gdbots:ncr:mixin:node.unpublished' => 'onNodeProjected',
        ];
    }

    public function onNodeProjected(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();
        if ($event->isReplay()) {
            return;
        }

        $pbjx = $pbjxEvent::getPbjx();
        $node = $pbjxEvent->getNode();
        $nodeRef = $node->generateNodeRef();
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
