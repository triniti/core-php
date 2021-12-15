<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Command\CreateAssetV1;
use Acme\Schemas\Dam\Command\DeleteAssetV1;
use Acme\Schemas\Dam\Command\LinkAssetsV1;
use Acme\Schemas\Dam\Command\PatchAssetsV1;
use Acme\Schemas\Dam\Command\ReorderGalleryAssetsV1;
use Acme\Schemas\Dam\Command\UpdateAssetV1;
use Acme\Schemas\Dam\Node\ImageAssetV1;
use Brick\Math\BigInteger;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Dam\AssetAggregate;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Dam\Command\UnlinkAssetsV1;
use Triniti\Tests\AbstractPbjxTest;

final class AssetAggregateTest extends AbstractPbjxTest
{
    public function testCreateAggregate(): void
    {
        $node = ImageAssetV1::create();
        $aggregate = AssetAggregate::fromNode($node, $this->pbjx);
        $this->assertTrue(NodeStatus::PUBLISHED === $aggregate->getNode()->get('status'));
    }

    public function testCreateNode(): void
    {
        $node = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
        ]);
        $aggregate = AssetAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateAssetV1::create()->set('node', $node));
        $events = $aggregate->getUncommittedEvents();
        $this->assertTrue(NodeStatus::PUBLISHED === $events[0]->get('node')->get('status'));
    }

    public function testUpdateNode(): void
    {
        $fileSize = BigInteger::fromBase('0', 10);
        $node = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
            'file_size' => $fileSize,
        ]);
        $nodeRef = $node->generateNodeRef();
        $aggregate = AssetAggregate::fromNode($node, $this->pbjx);
        $aggregate->createNode(CreateAssetV1::create()->set('node', $node));
        $aggregate->deleteNode(DeleteAssetV1::create()->set('node_ref', $nodeRef));
        $aggregate->commit();
        $this->assertTrue(NodeStatus::DELETED === $aggregate->getNode()->get('status'));
        $newNode = (clone $node)
            ->set('mime_type', 'video/mp4')
            ->set('file_size', BigInteger::fromBase('1', 10));
        $command = UpdateAssetV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', $newNode);
        $aggregate->updateNode($command);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();
        $this->assertTrue(NodeStatus::PUBLISHED === $aggregateNode->get('status'));
        $this->assertSame('image/jpeg', $aggregateNode->get('mime_type'));
        $this->assertEquals($fileSize, $aggregateNode->get('file_size'));
    }

    public function testLinkAsset(): void
    {
        $asset = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
        ]);
        $articleRef = NodeRef::fromString('acme:article:123');
        $aggregate = AssetAggregate::fromNode($asset, $this->pbjx);
        $command = LinkAssetsV1::create()
            ->set('node_ref', $articleRef)
            ->addToSet('asset_refs', [$asset->generateNodeRef()]);
        $aggregate->linkAsset($command);
        $aggregate->commit();
        $this->assertTrue(in_array($articleRef, $aggregate->getNode()->get('linked_refs')));
    }

    public function testUnlinkAsset(): void
    {
        $articleRef = NodeRef::fromString('acme:article:123');
        $asset = ImageAssetV1::fromArray([
            '_id'         => AssetId::create('image', 'jpg'),
            'mime_type'   => 'image/jpeg',
            'linked_refs' => [$articleRef],
        ]);
        $aggregate = AssetAggregate::fromNode($asset, $this->pbjx);
        $command = UnlinkAssetsV1::create()
            ->set('node_ref', $articleRef)
            ->addToSet('asset_refs', [$asset->generateNodeRef()]);
        $aggregate->unlinkAsset($command);
        $aggregate->commit();
        $this->assertEmpty($aggregate->getNode()->get('linked_refs'));
    }

    public function testPatchAsset(): void
    {
        $asset1 = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
        ]);

        $paths = ['title', 'expires_at', 'credit', 'description'];
        $title = 'Possible Title';
        $credit = 'Possible Vendor';
        $expiresAt = new \DateTime();
        $description = 'Possible Description';

        $command = PatchAssetsV1::create()
            ->addToSet('node_refs', [$asset1->generateNodeRef()])
            ->addToSet('paths', $paths)
            ->set('title', $title)
            ->set('credit', $credit)
            ->set('expires_at', $expiresAt)
            ->set('description', $description);
        $aggregate = AssetAggregate::fromNode($asset1, $this->pbjx);
        $aggregate->patchAsset($command);
        $aggregate->commit();

        $aggregateNode = $aggregate->getNode();
        $this->assertSame($title, $aggregateNode->get('title'));
        $this->assertSame($credit, $aggregateNode->get('credit'));
        $this->assertEquals($expiresAt, $aggregateNode->get('expires_at'));
        $this->assertSame($description, $aggregateNode->get('description'));
    }

    public function testReorderGalleryAsset(): void
    {
        $galleryNodeRef = NodeRef::fromString('acme:gallery:56eac630-d499-4865-90c9-7018299cd2aa');

        $asset = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
        ]);

        $sequenceNumber = 500;

        $command = ReorderGalleryAssetsV1::create()
            ->set('gallery_ref', $galleryNodeRef)
            ->addToMap('gallery_seqs', $asset->get('_id')->toString(), $sequenceNumber);
        $aggregate = AssetAggregate::fromNode($asset, $this->pbjx);
        $aggregate->sync();
        $aggregate->reorderGalleryAsset($command, $sequenceNumber);
        $aggregate->commit();
        $aggregateNode = $aggregate->getNode();
        $this->assertSame($sequenceNumber, $aggregateNode->get('gallery_seq'));
        $this->assertTrue($galleryNodeRef->equals($aggregateNode->get('gallery_ref')));
    }
}
