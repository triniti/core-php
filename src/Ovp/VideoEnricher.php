<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Triniti\Dam\UrlProvider as DamUrlProvider;
use Triniti\Schemas\Dam\AssetId;

class VideoEnricher implements EventSubscriber, PbjxEnricher
{
    protected DamUrlProvider $damUrlProvider;
    protected ArtifactUrlProvider $artifactUrlProvider;

    public function __construct(DamUrlProvider $damUrlProvider, ArtifactUrlProvider $artifactUrlProvider)
    {
        $this->damUrlProvider = $damUrlProvider;
        $this->artifactUrlProvider = $artifactUrlProvider;
    }

    public static function getSubscribedEvents()
    {
        $vendor = MessageResolver::getDefaultVendor();
        return [
            "{$vendor}:ovp:event:video-updated.enrich" => 'enrichVideoUpdated'
        ];
    }

    public function enrichVideoUpdated(PbjxEvent $pbjxEvent): void
    {
        $event = $pbjxEvent->getMessage();
        if ($event->isFrozen()) {
            return;
        }

        if (!$event->has('new_node')) {
            return;
        }

        $node = $event->get('new_node');
        if ($node->has('mezzanine_ref')) {
            $mezzanineAssetId = AssetId::fromString($node->get('mezzanine_ref')->getId());
            $node
                ->set('mezzanine_url', $this->artifactUrlProvider->getManifest($mezzanineAssetId))
                ->set('kaltura_mp4_url', $this->artifactUrlProvider->getVideo($mezzanineAssetId));
        }

        if ($node->has('caption_ref')) {
            $documentAssetId = AssetId::fromString($node->get('caption_ref')->getId());
            $node->addToMap('caption_urls', 'en', $this->damUrlProvider->getUrl($documentAssetId));
        }
    }
}
