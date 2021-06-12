<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\News\Event\ArticleMarkedAsDraftV1;
use Acme\Schemas\News\Event\ArticlePublishedV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Event\PbjxEvent;
use Triniti\Curator\TeaserableEnricher;
use Triniti\Tests\AbstractPbjxTest;

final class TeaserableEnricherTest extends AbstractPbjxTest
{
    public function testEnrichWithOrderDateWhenNotPublished(): void
    {
        $node = ArticleV1::create()->set('published_at', new \DateTime('+2 weeks'));
        $event = ArticleMarkedAsDraftV1::create()
            ->set('node_ref', NodeRef::fromNode($node));

        $pbjxEvent = (new PbjxEvent($event))->createChildEvent($node);
        $enricher = new TeaserableEnricher();
        $enricher->enrichWithOrderDate($pbjxEvent);

        $expected = $node->get('created_at')->toDateTime()->format('c');
        $actual = $node->get('order_date')->format('c');

        $this->assertSame($expected, $actual, 'order_date should match created_at');
    }
}
