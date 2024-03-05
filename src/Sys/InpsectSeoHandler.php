<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Aws\Credentials\CredentialsInterface;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\NodeProjectedEvents;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Sys\Command\InspectSeo;


class InspectSeoHandler implements CommandHandler
{
    protected Ncr $ncr;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:news:mixin:article.published' => 'onArticlePublished',
        ];
    }


    public function onArticlePublished(NodeProjectedEvent $pbjxEvent): void
    {
        $pbjx = $pbjxEvent::getPbjx();
        $event = $pbjxEvent->getLastEvent();
        $node = $pbjxEvent->getNode();

        if ($node->get('is_unlisted')) {
            return;
        }

        sleep(5);

        $command = InspectSeo::create()->set('node_ref', $event->get('node_ref'));
        $pbjx->sendAt($command);
    }
}
