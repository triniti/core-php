<?php
declare(strict_types=1);

namespace Triniti\Tests\Ovp;

use Acme\Schemas\Dam\Node\DocumentAssetV1;
use Acme\Schemas\Dam\Node\VideoAssetV1;
use Acme\Schemas\Ovp\Event\VideoUpdatedV1;
use Acme\Schemas\Ovp\Node\VideoV1;
use Gdbots\Pbjx\Event\PbjxEvent;
use PHPUnit\Framework\TestCase;
use Triniti\Dam\UrlProvider as DamUrlProvider;
use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Ovp\VideoEnricher;
use Triniti\Schemas\Dam\AssetId;

final class VideoEnricherTest extends TestCase
{
    public function testEnrichVideoUpdated(): void
    {
        $videoAssetId = AssetId::create('video', 'mxf');
        $videoAsset = VideoAssetV1::fromArray(['_id' => $videoAssetId]);
        $documentAssetId = AssetId::create('document', 'vtt');
        $documentAsset = DocumentAssetV1::fromArray(['_id' => $documentAssetId]);
        $oldNode = VideoV1::create();
        $newNode = (clone $oldNode)
            ->set('mezzanine_ref', $videoAsset->generateNodeRef())
            ->set('caption_ref', $documentAsset->generateNodeRef());
        $event = VideoUpdatedV1::create()
            ->set('node_ref', $oldNode->generateNodeRef())
            ->set('old_node', $oldNode)
            ->set('new_node', $newNode);
        $pbjxEvent = new PbjxEvent($event);

        $damUrlProvider = new DamUrlProvider(['default' => 'https://dam.acme.com/']);
        $artifactUrlProvider = new ArtifactUrlProvider($damUrlProvider);
        (new VideoEnricher($damUrlProvider, $artifactUrlProvider))->enrichVideoUpdated($pbjxEvent);

        $actual = $event->get('new_node')->get('mezzanine_url');
        $expected = $artifactUrlProvider->getManifest($videoAssetId);
        $this->assertSame($actual, $expected);

        $actual = $event->get('new_node')->get('kaltura_mp4_url');
        $expected = $artifactUrlProvider->getVideo($videoAssetId);
        $this->assertSame($actual, $expected);

        $actual = $event->get('new_node')->getFromMap('caption_urls', 'en');
        $expected = $damUrlProvider->getUrl($documentAssetId);
        $this->assertSame($actual, $expected);
    }
}
