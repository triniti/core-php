<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\Pbjx\Pbjx;
use Triniti\Schemas\Sys\Command\InspectSeoV1;
use Triniti\Sys\Flags;

class ArticleInspectSeoWatcher implements EventSubscriber
{
    protected Flags $flags;
    protected Pbjx $pbjx;

    public function __construct(Flags $flags, Pbjx $pbjx) {
        $this->flags = $flags;
        $this->pbjx = $pbjx;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:news:mixin:article.published' => 'onArticlePublished',
        ];
    }

    public function onArticlePublished(NodeProjectedEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getLastEvent();

        if ($this->flags->getBoolean('subscriber_disabled')){
            return;
        }

        if ($event->isReplay()) {
            return;
        }

        $inspectArticleCommand = InspectSeoV1::create()->set('node_ref', $event->get('node_ref'));
        $seoDelay = $this->flags->getBoolean('seo_delay_disabled') ? 'now' : "+5 minutes";

        $this->pbjx->sendAt($inspectArticleCommand, strtotime($seoDelay));
    }
}
