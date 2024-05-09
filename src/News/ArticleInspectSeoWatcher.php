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

    const INSPECT_SEO_DISABLED_FLAG_NAME = "inspect_seo_disabled";
    const INSPECT_SEO_INITIAL_DELAY_FLAG_NAME = "inspect_seo_initial_delay";
    const INSPECT_SEO_NO_DELAY_FLAG_NAME = "inspect_seo_no_delay";

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

        if ($this->flags->getBoolean(self::INSPECT_SEO_DISABLED_FLAG_NAME)) {
            return;
        }

        if ($event->isReplay()) {
            return;
        }

        $inspectArticleCommand = InspectSeoV1::create()
            ->set('node_ref', $event->get('node_ref'));

        if (getenv('APP_ENV') === 'prod') {
            $selectedFlag = $this->flags->getBoolean(self::INSPECT_SEO_DISABLED_FLAG_NAME) ? self::INSPECT_SEO_NO_DELAY_FLAG_NAME : self::INSPECT_SEO_INITIAL_DELAY_FLAG_NAME;
            $seoDelay = $this->flags->getInt($selectedFlag);
            $this->pbjx->sendAt($inspectArticleCommand, $seoDelay);
        } else {
            $this->pbjx->send($inspectArticleCommand);
        }
    }
}
