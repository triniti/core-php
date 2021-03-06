<?php
declare(strict_types=1);

namespace Triniti\Tests\OvpJwplayer;

use Acme\Schemas\Dam\Node\VideoAssetV1;
use Acme\Schemas\Ovp\Event\VideoCreatedV1;
use Acme\Schemas\Ovp\Event\VideoDeletedV1;
use Acme\Schemas\Ovp\Event\VideoUpdatedV1;
use Acme\Schemas\Ovp\Node\VideoV1;
use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Scheduler\Scheduler;
use Triniti\OvpJwplayer\JwplayerWatcher;
use Triniti\Schemas\Ovp\Event\TranscodingCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;
use Triniti\Schemas\OvpJwplayer\Command\SyncMediaV1;
use Triniti\Tests\AbstractPbjxTest;

final class JwplayerWatcherTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;
    protected Scheduler $scheduler;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();

        $this->scheduler = new class implements Scheduler
        {
            private array $lastSendAt;
            private array $lastCancelJobs;
            private array $scheduled = [];

            public function createStorage(array $context = []): void
            {
            }

            public function describeStorage(array $context = []): string
            {
                return '';
            }

            public function sendAt(Message $command, int $timestamp, ?string $jobId = null, array $context = []): string
            {
                $this->lastSendAt = [
                    'command'   => $command,
                    'timestamp' => $timestamp,
                    'job_id'    => $jobId,
                ];

                $id = $jobId ?: 'jobid';
                $this->scheduled[$id] = $command;
                return $id;
            }

            public function cancelJobs(array $jobIds, array $context = []): void
            {
                $this->lastCancelJobs = $jobIds;
            }

            public function describeScheduled(): array
            {
                return $this->scheduled;
            }
        };

        $this->locator->setScheduler($this->scheduler);
    }

    public function testOnVideoCreated(): void
    {
        $node = VideoV1::fromArray([
            '_id'  => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
        ]);
        $nodeRef = NodeRef::fromNode($node);
        $event = VideoCreatedV1::create()->set('node', $node);
        $watcher = new JwplayerWatcher();
        $watcher->onVideoCreated(new NodeProjectedEvent($node, $event));
        $expectedJobId = 'acme:video:7afcc2f1-9654-46d1-8fc1-b0511df257db.sync-jwplayer-media';
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertTrue($scheduled instanceof SyncMediaV1, 'The JwplayerWatcher should schedule a SyncMedia command when a video is created.');
        $this->assertTrue($scheduled->get('node_ref')->equals($nodeRef), 'The SyncMedia command should have the correct node_ref.');
        $this->assertTrue($scheduled->isInSet('fields', 'captions'), 'The SyncMedia command should have captions in its fields set.');
        $this->assertTrue($scheduled->isInSet('fields', 'thumbnail'), 'The SyncMedia command should have thumbnails in its fields set.');
    }

    public function testOnVideoDeleted(): void
    {
        $node = VideoV1::fromArray([
            '_id'  => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
        ]);
        $nodeRef = NodeRef::fromNode($node);
        $event = VideoDeletedV1::create()->set('node_ref', $nodeRef);
        $watcher = new JwplayerWatcher();
        $watcher->onVideoEvent(new NodeProjectedEvent($node, $event));
        $expectedJobId = 'acme:video:7afcc2f1-9654-46d1-8fc1-b0511df257db.sync-jwplayer-media';
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertTrue($scheduled instanceof SyncMediaV1, 'The JwplayerWatcher should schedule a SyncMedia command when a video is deleted.');
        $this->assertTrue($scheduled->get('node_ref')->equals($nodeRef), 'The SyncMedia command should have the correct node_ref');
    }

    public function testOnVideoUpdated(): void
    {
        $node = VideoV1::fromArray([
            '_id'       => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'image_ref' => NodeRef::fromString('acme:image:image_jpg_20200409_4236f555d2f44ee5b165076cf64af34f'),
            'caption_urls' => [
                'en' => 'https://dam.dev.acme.com/document/bc/o/2020/03/30/bc0415813a9e42b38ef28460a4779417.vtt'
            ]
        ]);
        $this->ncr->putNode($node);
        $newNode = (clone $node);
        $nodeRef = NodeRef::fromNode($node);
        $event = VideoUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('new_node', $newNode);
        $watcher = new JwplayerWatcher();
        $watcher->onVideoUpdated(new NodeProjectedEvent($newNode, $event));
        $expectedJobId = 'acme:video:7afcc2f1-9654-46d1-8fc1-b0511df257db.sync-jwplayer-media';
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertTrue($scheduled instanceof SyncMediaV1, 'The JwplayerWatcher should schedule a SyncMedia command when a video is updated.');
        $this->assertTrue($scheduled->get('node_ref')->equals($nodeRef), 'The SyncMedia command should have the correct node_ref');
        $this->assertFalse($scheduled->isInSet('fields', 'captions'), 'The SyncMedia command should not have captions in its fields set.');
        $this->assertFalse($scheduled->isInSet('fields', 'thumbnail'), 'The SyncMedia command should not have thumbnail in its fields set.');

        $newNode = (clone $node)->set('image_ref', NodeRef::fromString('acme:image:image_jpg_20200409_8e2d2e3c09074959ab1f794fcbf901cc'));
        $event = VideoUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('old_node', $node)
            ->set('new_node', $newNode);
        $watcher = new JwplayerWatcher();
        $watcher->onVideoUpdated(new NodeProjectedEvent($newNode, $event));
        $expectedJobId = 'acme:video:7afcc2f1-9654-46d1-8fc1-b0511df257db.sync-jwplayer-media';
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertTrue($scheduled instanceof SyncMediaV1, 'The JwplayerWatcher should schedule a SyncMedia command when a video is updated.');
        $this->assertTrue($scheduled->get('node_ref')->equals($nodeRef), 'The SyncMedia command should have the correct node_ref');
        $this->assertFalse($scheduled->isInSet('fields', 'captions'), 'The SyncMedia command should not have captions in its fields set.');
        $this->assertTrue($scheduled->isInSet('fields', 'thumbnail'), 'The SyncMedia command should have thumbnail in its fields set.');

        $newNode = (clone $node)->addToMap('caption_urls', 'en', 'https://dam.dev.acme.com/document/11/o/2020/03/28/116565cba0404c43b9ee760e88b513b7.vtt');
        $event = VideoUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('old_node', $node)
            ->set('new_node', $newNode);
        $watcher = new JwplayerWatcher();
        $watcher->onVideoUpdated(new NodeProjectedEvent($newNode, $event));
        $expectedJobId = 'acme:video:7afcc2f1-9654-46d1-8fc1-b0511df257db.sync-jwplayer-media';
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertTrue($scheduled instanceof SyncMediaV1, 'The JwplayerWatcher should schedule a SyncMedia command when a video is updated.');
        $this->assertTrue($scheduled->get('node_ref')->equals($nodeRef), 'The SyncMedia command should have the correct node_ref');
        $this->assertTrue($scheduled->isInSet('fields', 'captions'), 'The SyncMedia command should have captions in its fields set.');
        $this->assertFalse($scheduled->isInSet('fields', 'thumbnail'), 'The SyncMedia command should not have thumbnail in its fields set.');

        $newNode = (clone $node)
            ->set('image_ref', NodeRef::fromString('acme:image:image_jpg_20200409_611b6301906d401bb0c76889ca0b6817'))
            ->addToMap('caption_urls', 'en', 'https://dam.dev.acme.com/document/11/o/2020/03/28/116565cba0404c43b9ee760e88b513b7.vtt');
        $event = VideoUpdatedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('old_node', $node)
            ->set('new_node', $newNode);
        $watcher = new JwplayerWatcher();
        $watcher->onVideoUpdated(new NodeProjectedEvent($newNode, $event));
        $expectedJobId = 'acme:video:7afcc2f1-9654-46d1-8fc1-b0511df257db.sync-jwplayer-media';
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertTrue($scheduled instanceof SyncMediaV1, 'The JwplayerWatcher should schedule a SyncMedia command when a video is updated.');
        $this->assertTrue($scheduled->get('node_ref')->equals($nodeRef), 'The SyncMedia command should have the correct node_ref');
        $this->assertTrue($scheduled->isInSet('fields', 'captions'), 'The SyncMedia command should have captions in its fields set.');
        $this->assertTrue($scheduled->isInSet('fields', 'thumbnail'), 'The SyncMedia command should have thumbnail in its fields set.');
    }

    public function testOnVideoTranscodingCompleted(): void
    {
        $video = VideoV1::fromArray([
            '_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
        ]);
        $videoRef = NodeRef::fromNode($video);
        $event = TranscodingCompletedV1::create()
            ->set('node_ref', $videoRef)
            ->addToMap('tags', 'image_asset_ref', 'acme:image-asset:image_jpeg_20200701_5ede1a15996e46bd85250949195e7301');
        $watcher = new JwplayerWatcher();
        $expectedJobId = 'acme:video:7afcc2f1-9654-46d1-8fc1-b0511df257db.sync-jwplayer-media';
        $watcher->onTranscodingCompleted(new NodeProjectedEvent($video, $event));
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertTrue($scheduled instanceof SyncMediaV1);
        $this->assertTrue($scheduled->isInSet('fields', 'thumbnail'));
    }

    public function testOnVideoTranscriptionCompleted(): void
    {
        $video = VideoV1::fromArray([
            '_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
        ]);
        $videoRef = NodeRef::fromNode($video);
        $event = TranscriptionCompletedV1::create()->set('node_ref', $videoRef);
        $watcher = new JwplayerWatcher();
        $expectedJobId = 'acme:video:7afcc2f1-9654-46d1-8fc1-b0511df257db.sync-jwplayer-media';
        $watcher->onTranscriptionCompleted(new NodeProjectedEvent($video, $event));
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertTrue($scheduled instanceof SyncMediaV1);
        $this->assertTrue($scheduled->isInSet('fields', 'captions'));
    }
}
