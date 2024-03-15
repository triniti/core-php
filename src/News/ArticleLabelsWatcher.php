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
use Triniti\Sys\Flags;


class ArticleLabelsWatcher implements EventSubscriber
{
    protected Flags $flags;

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

        $seoDelayFlag = $this->flags->getBoolean('seo_delay_disabled');
        $seoInspectDisabledFlag = $this->flags->getBoolean('seo_inspect_disabled');

        $inspectArticleCommand = InspectSeo::create()->set('node_ref', $event->get('node_ref'));

        // Update to sendAt after PR approved
        $pbjx->send($inspectArticleCommand, strtotime($seoDelayFlag ? '+5 minutes': '0 seconds'));
    }
}
