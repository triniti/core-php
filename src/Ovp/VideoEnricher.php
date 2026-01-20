<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\DependencyInjection\PbjxEnricher;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Triniti\Dam\UrlProvider as DamUrlProvider;
use Triniti\Schemas\Dam\AssetId;

class VideoEnricher implements EventSubscriber, PbjxEnricher
{
    protected Ncr $ncr;
    protected DamUrlProvider $damUrlProvider;
    protected ArtifactUrlProvider $artifactUrlProvider;

    public static function getSubscribedEvents(): array
    {
        return [
            'triniti:ovp:mixin:video.enrich' => 'enrich',
        ];
    }

    public function __construct(Ncr $ncr, DamUrlProvider $damUrlProvider, ArtifactUrlProvider $artifactUrlProvider)
    {
        $this->ncr = $ncr;
        $this->damUrlProvider = $damUrlProvider;
        $this->artifactUrlProvider = $artifactUrlProvider;
    }

    public function enrich(PbjxEvent $pbjxEvent): void
    {
        $node = $pbjxEvent->getMessage();
        if ($node->isFrozen()) {
            return;
        }

        if (!$pbjxEvent->hasParentEvent()) {
            $this->enrichWithCaptionUrls($node);
            $this->enrichWithMezzanineUrls($node);
            return;
        }

        $parentEvent = $pbjxEvent->getParentEvent()->getMessage();
        if (!$parentEvent::schema()->hasMixin('gdbots:pbjx:mixin:event')) {
            return;
        }

        $this->enrichWithCaptionUrls($node);
        $this->enrichWithMezzanineUrls($node);

        // Sync is_vertical when mezzanine_ref changes during video update
        if ($parentEvent->has('old_node') && $parentEvent->has('new_node')) {
            $oldMezzanineRef = $parentEvent->get('old_node')->fget('mezzanine_ref');
            $newMezzanineRef = $parentEvent->get('new_node')->fget('mezzanine_ref');

            if ($newMezzanineRef !== null && $newMezzanineRef !== $oldMezzanineRef) {
                $this->syncIsVerticalFromMezzanine($node, $parentEvent);
            }
        }
    }

    // hasField checks provide backwards compatibility for repos that haven't yet updated
    // to schema versions containing the is_vertical field.
    protected function syncIsVerticalFromMezzanine(Message $node, Message $event): void
    {
        if (!$node->has('mezzanine_ref') || !$node::schema()->hasField('is_vertical')) {
            return;
        }

        $mezzanineRef = $node->get('mezzanine_ref');
        $videoAsset = $this->ncr->getNode($mezzanineRef, false, ['causator' => $event]);

        if ($videoAsset::schema()->hasField('is_vertical')) {
            $node->set('is_vertical', $videoAsset->get('is_vertical', false));
        }
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
