<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Command\PublishTeaserV1;
use Acme\Schemas\Curator\Node\ArticleTeaserV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\MessageRef;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Curator\Exception\TargetNotPublished;
use Triniti\Curator\TeaserValidator;
use Triniti\Tests\AbstractPbjxTest;

final class TeaserValidatorTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;

    protected function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
    }

    public function testValidatePublishNodeWithTargetNotPublished(): void
    {
        $this->expectException(TargetNotPublished::class);

        $article = ArticleV1::create();
        $this->ncr->putNode($article);

        $teaser = ArticleTeaserV1::create()->set('target_ref', $article->generateNodeRef());
        $this->ncr->putNode($teaser);

        $validator = new TeaserValidator($this->ncr);
        $command = PublishTeaserV1::create()->set('node_ref', $teaser->generateNodeRef());
        $pbjxEvent = new PbjxEvent($command);
        $validator->validatePublishNode($pbjxEvent);
    }

    public function testValidatePublishNodeWithTargetPublished(): void
    {
        $article = ArticleV1::create()->set('status', NodeStatus::PUBLISHED);
        $this->ncr->putNode($article);

        $teaser = ArticleTeaserV1::create()->set('target_ref', $article->generateNodeRef());
        $this->ncr->putNode($teaser);

        $validator = new TeaserValidator($this->ncr);
        $command = PublishTeaserV1::create()->set('node_ref', $teaser->generateNodeRef());
        $pbjxEvent = new PbjxEvent($command);
        $validator->validatePublishNode($pbjxEvent);
        $this->assertTrue(true, 'Teaser can be published.');
    }

    public function testValidatePublishNodeWithTargetPublishedCausator(): void
    {
        $article = ArticleV1::create();
        $this->ncr->putNode($article);

        $teaser = ArticleTeaserV1::create()->set('target_ref', $article->generateNodeRef());
        $this->ncr->putNode($teaser);

        $validator = new TeaserValidator($this->ncr);
        $command = PublishTeaserV1::create()->set('node_ref', $teaser->generateNodeRef());

        $command->set('ctx_causator_ref', MessageRef::fromString('acme:news:event:article-published:123'));
        $pbjxEvent = new PbjxEvent($command);
        $validator->validatePublishNode($pbjxEvent);
        $this->assertTrue(true, 'Teaser can be published.');
    }

    public function testValidatePublishNodeWithScheduledTeaser(): void
    {
        $article = ArticleV1::create();
        $this->ncr->putNode($article);

        $teaser = ArticleTeaserV1::create()->set('target_ref', $article->generateNodeRef());
        $this->ncr->putNode($teaser);

        $validator = new TeaserValidator($this->ncr);
        $command = PublishTeaserV1::create()
            ->set('node_ref', $teaser->generateNodeRef())
            ->set('publish_at', new \DateTime('+1 hour'));

        $pbjxEvent = new PbjxEvent($command);
        $validator->validatePublishNode($pbjxEvent);
        $this->assertTrue(true, 'Teaser can be scheduled.');
    }

    public function testValidatePublishNodeWithTargetDeletedCausator(): void
    {
        $this->expectException(TargetNotPublished::class);

        $article = ArticleV1::create();
        $this->ncr->putNode($article);

        $teaser = ArticleTeaserV1::create()->set('target_ref', $article->generateNodeRef());
        $this->ncr->putNode($teaser);

        $validator = new TeaserValidator($this->ncr);
        $command = PublishTeaserV1::create()->set('node_ref', $teaser->generateNodeRef());

        $command->set('ctx_causator_ref', MessageRef::fromString('acme:news:event:article-deleted:123'));
        $pbjxEvent = new PbjxEvent($command);
        $validator->validatePublishNode($pbjxEvent);
    }

    public function testValidatePublishNodeWithMissingTeaser(): void
    {
        $this->expectException(NodeNotFound::class);

        $article = ArticleV1::create();
        $this->ncr->putNode($article);

        $teaser = ArticleTeaserV1::create()->set('target_ref', $article->generateNodeRef());

        $validator = new TeaserValidator($this->ncr);
        $command = PublishTeaserV1::create()->set('node_ref', $teaser->generateNodeRef());
        $pbjxEvent = new PbjxEvent($command);
        $validator->validatePublishNode($pbjxEvent);
    }

    public function testValidatePublishNodeWithMissingTarget(): void
    {
        $this->expectException(NodeNotFound::class);

        $article = ArticleV1::create();

        $teaser = ArticleTeaserV1::create()->set('target_ref', $article->generateNodeRef());
        $this->ncr->putNode($teaser);

        $validator = new TeaserValidator($this->ncr);
        $command = PublishTeaserV1::create()->set('node_ref', $teaser->generateNodeRef());
        $pbjxEvent = new PbjxEvent($command);
        $validator->validatePublishNode($pbjxEvent);
    }
}
