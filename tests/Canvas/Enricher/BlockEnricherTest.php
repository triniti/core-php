<?php
declare(strict_types=1);

namespace Triniti\Tests\Canvas\Enricher;

use Acme\Schemas\Canvas\Block\TextBlockV1;
use Gdbots\Pbjx\Event\PbjxEvent;
use Triniti\Canvas\Enricher\BlockEnricher;
use Triniti\Tests\AbstractPbjxTest;

final class BlockEnricherTest extends AbstractPbjxTest
{
    public function testEnrichWithEtag(): void
    {
        $block = TextBlockV1::create()
            // using time() ensures etag itself is never apart of final etag
            ->set('etag', 'invalid' . time())
            ->set('text', 'test');
        $pbjxEvent = new PbjxEvent($block);

        (new BlockEnricher())->enrichWithEtag($pbjxEvent);
        $actual = $block->get('etag');
        $expected = '0a06c2e530fa4038b14808509ed070bb';

        $this->assertSame($expected, $actual, 'Enriched etag should match.');
    }
}
