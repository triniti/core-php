<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Command\CreateGalleryV1;
use Acme\Schemas\Curator\Command\PublishGalleryV1;
use Acme\Schemas\Curator\Command\UpdateGalleryV1;
use Acme\Schemas\Curator\Event\GalleryImageCountUpdatedV1;
use Acme\Schemas\Curator\Node\GalleryV1;
use Acme\Schemas\Curator\Request\SearchGalleriesRequestV1;
use Acme\Schemas\Curator\Request\SearchGalleriesResponseV1;
use Acme\Schemas\Dam\Event\AssetCreatedV1;
use Acme\Schemas\Dam\Event\AssetDeletedV1;
use Acme\Schemas\Dam\Event\GalleryAssetReorderedV1;
use Acme\Schemas\Dam\Node\ImageAssetV1;
use Acme\Schemas\Dam\Node\VideoAssetV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Curator\GalleryAggregate;
use Triniti\Curator\NcrGalleryProjector;
use Triniti\Schemas\Curator\Command\UpdateGalleryImageCountV1;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;
use Triniti\Tests\MockPbjx;

final class NcrGalleryProjectorTest extends AbstractPbjxTest
{
    protected NcrGalleryProjector $projector;
    protected MockNcrSearch $ncrSearch;
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncrSearch = new MockNcrSearch();
        $this->ncr = new InMemoryNcr();
        $this->projector = new NcrGalleryProjector($this->ncr, $this->ncrSearch);
        $this->pbjx = new MockPbjx($this->locator);
    }

    public function testOnImageAssetCreated(): void
    {
        $gallery = GalleryV1::create();
        $galleryRef = $gallery->generateMessageRef();

        $image = ImageAssetV1::fromArray([
            '_id'         => AssetId::create('image', 'jpg'),
            'mime_type'   => 'image/jpeg',
            'gallery_ref' => $galleryRef,
            'status'      => NodeStatus::PUBLISHED(),
        ]);

        $event = AssetCreatedV1::create()->set('node', $image);
        $this->projector->onAssetCreated($event, $this->pbjx);
        $sentCommand = $this->pbjx->getSent()[0];
        $this->assertInstanceOf(UpdateGalleryImageCountV1::class, $sentCommand);
        $this->assertTrue($galleryRef->equals($galleryRef));
    }

    public function testOnImageAssetCreatedIsReplay(): void
    {
        $item = ImageAssetV1::create()
            ->set('_id', AssetId::create('image', 'jpg'))
            ->set('mime_type', 'image/jpeg');
        $event = AssetCreatedV1::create()->set('node', $item);
        $event->isReplay(true);

        $this->projector->onAssetCreated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnNonImageAssetCreated()
    {
        $image = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mp4'),
            'mime_type' => 'video/mp4',
            'status'    => NodeStatus::PUBLISHED(),
        ]);

        $event = AssetCreatedV1::create()->set('node', $image);
        $this->projector->onAssetCreated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnAssetCreatedWithoutGalleryRef(): void
    {
        $image = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
            'status'    => NodeStatus::PUBLISHED(),
        ]);

        $event = AssetCreatedV1::create()->set('node', $image);
        $this->projector->onAssetCreated($event, $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnGalleryAssetReordered(): void
    {
        $gallery = GalleryV1::create();
        $galleryRef = $gallery->generateNodeRef();

        $image = ImageAssetV1::fromArray([
            '_id'         => AssetId::create('image', 'jpg'),
            'mime_type'   => 'image/jpeg',
            'gallery_ref' => $galleryRef,
            'status'      => NodeStatus::PUBLISHED(),
        ]);

        $event = GalleryAssetReorderedV1::create()
            ->set('node_ref', $image->generateNodeRef())
            ->set('gallery_ref', $galleryRef);

        $this->projector->onGalleryAssetReordered($event, $this->pbjx);
        $sentCommand = $this->pbjx->getSent()[0];
        $this->assertInstanceOf(UpdateGalleryImageCountV1::class, $sentCommand);
        $this->assertTrue($galleryRef->equals($sentCommand->get('node_ref')));
    }

    public function testOnGalleryUpdated(): void
    {
        $node = GalleryV1::create();
        $nodeRef = $node->generateNodeRef();
        $aggregate = GalleryAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateGalleryV1::create()->set('node', $node));
        $aggregate->commit();

        $newNode = (clone $node)->set('title', 'new-title');
        $command = UpdateGalleryV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', $newNode);
        $aggregate->updateNode($command);
        $events = $aggregate->getUncommittedEvents();
        $aggregate->commit();
        $this->projector->onGalleryUpdated($events[0], $this->pbjx);
        $this->assertSame('new-title', $this->ncr->getNode($nodeRef)->get('title'));
        $response = SearchGalleriesResponseV1::create();
        $this->ncrSearch->searchNodes(SearchGalleriesRequestV1::create(), new ParsedQuery(), $response);
        $this->assertSame('new-title', $response->get('nodes', [])[0]->get('title'));

        $sentCommand = $this->pbjx->getSent()[0];
        $this->assertInstanceOf(UpdateGalleryImageCountV1::class, $sentCommand);
        $this->assertTrue($nodeRef->equals($sentCommand->get('node_ref')));
    }

    public function testOnGalleryUpdatedIsReplay(): void
    {
        $node = GalleryV1::create();
        $nodeRef = $node->generateNodeRef();
        $aggregate = GalleryAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateGalleryV1::create()->set('node', $node));
        $aggregate->commit();

        $newNode = (clone $node)->set('title', 'new-title');
        $command = UpdateGalleryV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', $newNode);
        $aggregate->updateNode($command);
        $events = $aggregate->getUncommittedEvents();
        $event = $events[0];
        $aggregate->commit();
        $event->isReplay(true);
        $this->projector->onGalleryUpdated($event, $this->pbjx);
        $this->assertSame('new-title', $this->ncr->getNode($nodeRef)->get('title'));
        $response = SearchGalleriesResponseV1::create();
        $this->ncrSearch->searchNodes(SearchGalleriesRequestV1::create(), new ParsedQuery(), $response);
        $this->assertSame('new-title', $response->get('nodes', [])[0]->get('title'));

        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnGalleryPublished(): void
    {
        $node = GalleryV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        AggregateResolver::register(['acme:gallery' => 'Triniti\Curator\GalleryAggregate']);
        $aggregate = GalleryAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateGalleryV1::create()->set('node', $node));
        $aggregate->commit();

        $aggregate->publishNode(PublishGalleryV1::create()->set('node_ref', $nodeRef));
        $events = $aggregate->getUncommittedEvents();
        $aggregate->commit();
        $this->projector->onGalleryPublished($events[0], $this->pbjx);
        $ncrNode = $this->ncr->getNode($nodeRef);
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($ncrNode->get('status')));
        $this->assertSame($events[0]->get('published_at')->getTimestamp(), $ncrNode->get('published_at')->getTimestamp());
        $response = SearchGalleriesResponseV1::create();
        $this->ncrSearch->searchNodes(SearchGalleriesRequestV1::create(), new ParsedQuery(), $response);
        $indexedNode = $response->get('nodes', [])[0];
        $this->assertTrue(NodeStatus::PUBLISHED()->equals($indexedNode->get('status')));
        $this->assertSame($events[0]->get('published_at')->getTimestamp(), $indexedNode->get('published_at')->getTimestamp());

        $sentCommand = $this->pbjx->getSent()[0];
        $this->assertInstanceOf(UpdateGalleryImageCountV1::class, $sentCommand);
        $this->assertTrue($nodeRef->equals($sentCommand->get('node_ref')));
    }

    public function testOnGalleryImageCountUpdated(): void
    {
        $node = GalleryV1::create();
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $event = GalleryImageCountUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('image_count', 20);
        $this->projector->onGalleryImageCountUpdated($event, $this->pbjx);

        $this->assertSame(20, $this->ncr->getNode($nodeRef)->get('image_count'));
    }

    public function testOnAssetDeletedOrExpired(): void
    {
        $node = GalleryV1::create();
        $nodeRef = $node->generateNodeRef();
        $image = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
            'status'    => NodeStatus::PUBLISHED(),
            'gallery_ref' => $nodeRef,
        ]);
        $this->ncr->putNode($image);

        $this->projector->onAssetDeletedOrExpired(AssetDeletedV1::create()->set('node_ref', $image->generateNodeRef()), $this->pbjx);

        $sentCommand = $this->pbjx->getSent()[0];
        $this->assertInstanceOf(UpdateGalleryImageCountV1::class, $sentCommand);
        $this->assertTrue($nodeRef->equals($sentCommand->get('node_ref')));
    }

    public function testOnAssetDeletedOrExpiredIsReplay(): void
    {
        $node = GalleryV1::create();
        $nodeRef = $node->generateNodeRef();
        $image = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
            'status'    => NodeStatus::PUBLISHED(),
            'gallery_ref' => $nodeRef,
        ]);
        $this->ncr->putNode($image);

        $event = AssetDeletedV1::create()->set('node_ref', $image->generateNodeRef());
        $event->isReplay(true);
        $this->projector->onAssetDeletedOrExpired($event, $this->pbjx);

        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnAssetDeletedOrExpiredNoNode(): void
    {
        $node = GalleryV1::create();
        $nodeRef = $node->generateNodeRef();
        $image = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
            'status'    => NodeStatus::PUBLISHED(),
            'gallery_ref' => $nodeRef,
        ]);

        $this->projector->onAssetDeletedOrExpired(AssetDeletedV1::create()->set('node_ref', $image->generateNodeRef()), $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnAssetDeletedOrExpiredNoGalleryRef(): void
    {
        $image = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
            'status'    => NodeStatus::PUBLISHED(),
        ]);
        $this->ncr->putNode($image);

        $this->projector->onAssetDeletedOrExpired(AssetDeletedV1::create()->set('node_ref', $image->generateNodeRef()), $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }

    public function testOnAssetDeletedOrExpiredWrongAssetType(): void
    {
        $node = GalleryV1::create();
        $nodeRef = $node->generateNodeRef();
        $image = VideoAssetV1::fromArray([
            '_id'       => AssetId::create('video', 'mxf'),
            'mime_type' => 'image/jpeg',
            'status'    => NodeStatus::PUBLISHED(),
            'gallery_ref' => $nodeRef,
        ]);
        $this->ncr->putNode($image);

        $this->projector->onAssetDeletedOrExpired(AssetDeletedV1::create()->set('node_ref', $image->generateNodeRef()), $this->pbjx);
        $this->assertEmpty($this->pbjx->getSent());
    }
}
