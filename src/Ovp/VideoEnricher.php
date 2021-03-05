<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Pbj\Message;
use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Triniti\Dam\UrlProvider as DamUrlProvider;
use Triniti\Schemas\Dam\AssetId;

class VideoEnricher implements EventSubscriber, PbjxEnricher
{
    protected DamUrlProvider $damUrlProvider;
    protected ArtifactUrlProvider $artifactUrlProvider;

    public static function getSubscribedEvents()
    {
        return [
            'triniti:ovp:mixin:video.enrich' => 'enrich',
        ];
    }

    public function __construct(DamUrlProvider $damUrlProvider, ArtifactUrlProvider $artifactUrlProvider)
    {
        $this->damUrlProvider = $damUrlProvider;
        $this->artifactUrlProvider = $artifactUrlProvider;
    }

    public function enrich(PbjxEvent $pbjxEvent): void
    {
        $node = $pbjxEvent->getMessage();
        if ($node->isFrozen()) {
            return;
        }

        if ($pbjxEvent->hasParentEvent()) {
            $parentEvent = $pbjxEvent->getParentEvent()->getMessage();
            if (!$parentEvent::schema()->hasMixin('gdbots:pbjx:mixin:event')) {
                return;
            }
        }

        $this->enrichWithCaptionUrls($node);
        $this->enrichWithMezzanineUrls($node);
    }

    protected function enrichWithCaptionUrls(Message $node): void
    {
        if (!$node->has('caption_ref')) {
            return;
        }

        $assetId = AssetId::fromString($node->get('caption_ref')->getId());
        $node->addToMap('caption_urls', 'en', $this->damUrlProvider->getUrl($assetId));
    }

    protected function enrichWithMezzanineUrls(Message $node): void
    {
        if (!$node->has('mezzanine_ref')) {
            return;
        }

        $assetId = AssetId::fromString($node->get('mezzanine_ref')->getId());
        $node
            ->set('mezzanine_url', $this->artifactUrlProvider->getManifest($assetId))
            ->set('kaltura_mp4_url', $this->artifactUrlProvider->getVideo($assetId));
    }
}
