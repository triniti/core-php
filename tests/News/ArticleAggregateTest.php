<?php
declare(strict_types=1);

namespace Triniti\Tests\News;

use Acme\Schemas\Canvas\Block\TextBlockV1;
use Acme\Schemas\News\Command\CreateArticleV1;
use Acme\Schemas\News\Command\RemoveArticleSlottingV1;
use Acme\Schemas\News\Command\UpdateArticleV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Pbj\WellKnown\UuidIdentifier;
use Triniti\News\ArticleAggregate;
use Triniti\Tests\AbstractPbjxTest;

final class ArticleAggregateTest extends AbstractPbjxTest
{
    public function testRemoveArticleSlotting(): void
    {
        $node = ArticleV1::create()
            ->addToMap('slotting', 'home', 1)
            ->addToMap('slotting', 'sports', 2);
        $aggregate = ArticleAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateArticleV1::create()->set('node', $node));

        $command = RemoveArticleSlottingV1::create()->addToMap('slotting', 'home', 1);
        $aggregate->removeArticleSlotting($command);

        $aggregateNode = $aggregate->getNode();
        $this->assertFalse($aggregateNode->isInMap('slotting', 'home'));
        $this->assertSame(2, $aggregateNode->getFromMap('slotting', 'sports'));
    }

    public function testUpdateNode(): void
    {
        $node = ArticleV1::create()
            ->set('title', 'original title')
            ->set('apple_news_updated_at', (new \DateTime('2011-01-01T15:03:01.012345Z'))->getTimestamp())
            ->set('apple_news_id', UuidIdentifier::fromString('8c284c8c-339c-4ebf-a467-42bcc71b1b16'))
            ->set('apple_news_revision', 'baz')
            ->set('apple_news_share_url', 'https://apple.news/foo');
        $aggregate = ArticleAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateArticleV1::create()->set('node', $node));
        $aggregate->commit();

        $newNode = (clone $node)
            ->set('title', 'new title')
            ->set('apple_news_updated_at', (new \DateTime('now'))->getTimestamp())
            ->set('apple_news_id', UuidIdentifier::fromString('6a015824-6804-4a37-b556-f4e30741a2ae'))
            ->set('apple_news_revision', 'qux')
            ->set('apple_news_share_url', 'https://apple.news/bar');
        $command = UpdateArticleV1::create()
            ->set('node_ref', $node->generateNodeRef())
            ->set('new_node', $newNode)
            ->addToSet('paths', ['title']);

        $aggregate->updateNode($command);
        $aggregate->commit();

        $aggregateNode = $aggregate->getNode();
        $this->assertSame($aggregateNode->get('apple_news_updated_at'), $node->get('apple_news_updated_at'));
        $this->assertSame($aggregateNode->get('apple_news_id')->toString(), $node->get('apple_news_id')->toString());
        $this->assertSame($aggregateNode->get('apple_news_revision'), $node->get('apple_news_revision'));
        $this->assertSame($aggregateNode->get('apple_news_share_url'), $node->get('apple_news_share_url'));
    }
}
