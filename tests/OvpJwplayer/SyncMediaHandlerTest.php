<?php
declare(strict_types=1);

namespace Triniti\Tests\OvpJwplayer;

use Acme\Schemas\Ovp\Command\UpdateVideoV1;
use Acme\Schemas\Ovp\Event\VideoUpdatedV1;
use Acme\Schemas\Ovp\Node\VideoV1;
use Acme\Schemas\Sys\Node\FlagsetV1;
use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Scheduler\Scheduler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\StreamId;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Triniti\Dam\UrlProvider as DamUrlProvider;
use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Ovp\VideoAggregate;
use Triniti\OvpJwplayer\SyncMediaHandler;
use Triniti\Schemas\OvpJwplayer\Command\SyncMediaV1;
use Triniti\Schemas\OvpJwplayer\Event\MediaSyncedV1;
use Triniti\Sys\Flags;
use Triniti\Tests\AbstractPbjxTest;

final class SyncMediaHandlerTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;
    protected Scheduler $scheduler;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        AggregateResolver::register(['acme:video' => 'Triniti\Ovp\VideoAggregate']);

        $this->scheduler = new class implements Scheduler {
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

    public function testDontSyncBecauseDisabledFlagIsSet(): void
    {
        $flagset = FlagsetV1::fromArray([
            '_id'      => 'test',
            'booleans' => ['jwplayer_sync_disabled' => true],
        ]);
        $this->ncr->putNode($flagset);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test')
        );
        $node = VideoV1::fromArray([
            '_id'           => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'mezzanine_url' => 'https://dam.test.acme.com/video/63/o/2020/01/23/63b44768ab1946ff99e42593e30816ee.mp4',
            'order_date'    => new \DateTime(),
        ]);
        $this->ncr->putNode($node);
        $command = SyncMediaV1::create()->set('node_ref', $node->generateNodeRef());
        $handler->handleCommand($command, $this->pbjx);
        $eventCount = 0;
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as $yield) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testDontSyncBecauseNoMezzanine(): void
    {
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test')
        );
        $node = VideoV1::fromArray([
            '_id' => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
        ]);
        $this->ncr->putNode($node);
        $command = SyncMediaV1::create()->set('node_ref', $node->generateNodeRef());
        $handler->handleCommand($command, $this->pbjx);
        $eventCount = 0;
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as $yield) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testDontSyncBecauseNotEnabled(): void
    {
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test')
        );
        $node = VideoV1::fromArray([
            '_id'                   => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'mezzanine_url'         => 'https://dam.test.acme.com/video/63/o/2020/01/23/63b44768ab1946ff99e42593e30816ee.mp4',
            'order_date'            => new \DateTime(),
            'jwplayer_sync_enabled' => false,
        ]);
        $this->ncr->putNode($node);
        $command = SyncMediaV1::create()->set('node_ref', $node->generateNodeRef());
        $handler->handleCommand($command, $this->pbjx);
        $eventCount = 0;
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as $yield) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testDontSyncBecauseMezzanineUrlFileTypeIsNotAllowed(): void
    {
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test')
        );
        $node = VideoV1::fromArray([
            '_id'                   => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'mezzanine_url'         => 'https://dam.test.acme.com/video/63/o/2020/01/23/63b44768ab1946ff99e42593e30816ee.wtf',
            'order_date'            => new \DateTime(),
            'jwplayer_sync_enabled' => false,
        ]);
        $this->ncr->putNode($node);
        $command = SyncMediaV1::create()->set('node_ref', $node->generateNodeRef());
        $handler->handleCommand($command, $this->pbjx);
        $eventCount = 0;
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as $yield) {
            $eventCount++;
        }
        $this->assertSame(0, $eventCount);
    }

    public function testCreateWithUrlAndCaptionAndThumbnail(): void
    {
        $jwplayerMediaId = 'foo';
        $createVideoStream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $listCaptionsStream = Utils::streamFor(serialize([
            'tracks' => [['key' => 'foo']],
        ]));
        $captionUploadUrlStream = Utils::streamFor(serialize([
            'link' => [
                'protocol' => 'foo',
                'address'  => 'foo',
                'path'     => 'foo',
                'query'    => [
                    'key'   => 'foo',
                    'token' => 'foo',
                ],
            ],
        ]));
        $newCaptionKey = 'foo';
        $createCaptionResponseStream = Utils::streamFor(serialize([
            'media' => [
                'key' => $newCaptionKey,
            ],
        ]));
        $thumbnailUploadUrlStream = Utils::streamFor(serialize([
            'link' => [
                'protocol' => 'foo',
                'address'  => 'foo',
                'path'     => 'foo',
                'query'    => [
                    'key'   => 'foo',
                    'token' => 'foo',
                ],
            ],
        ]));
        $handlerStack = HandlerStack::create(new MockHandler([
            $response = new Response(200, [], $createVideoStream),
            $response = new Response(200, [], $listCaptionsStream),
            $response = new Response(200, []), // delete caption
            $response = new Response(200, ['Content-Length' => 123]), // get caption file
            $response = new Response(200, [], $captionUploadUrlStream),
            $response = new Response(200, [], $createCaptionResponseStream),
            $response = new Response(200, ['Content-Length' => 123]), // get thumbnail file
            $response = new Response(200, [], $thumbnailUploadUrlStream),
            $response = new Response(200, []), // upload thumbnail
        ]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $captionLanguage = 'en';
        $node = VideoV1::fromArray([
            '_id'           => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'mezzanine_url' => 'https://dam.test.acme.com/video/63/o/2020/01/23/63b44768ab1946ff99e42593e30816ee.mp4',
            'order_date'    => new \DateTime(),
            'image_ref'     => 'acme:image-asset:image_jpg_20200409_4236f555d2f44ee5b165076cf64af34f',
            'caption_urls'  => [
                $captionLanguage => 'https://dam.dev.acme.com/document/bc/o/2020/03/30/bc0415813a9e42b38ef28460a4779417.vtt',
            ],
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('fields', ['captions', 'thumbnail']);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
            $this->assertTrue($event->getFromMap('jwplayer_caption_keys', $captionLanguage) === $newCaptionKey);
            $this->assertTrue($event->get('thumbnail_ref')->equals($node->get('image_ref')));
        }
    }

    public function testCreateWithRef(): void
    {
        $jwplayerMediaId = 'foo';
        $stream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $response = new Response(200, [], $stream);
        $handlerStack = HandlerStack::create(new MockHandler([$response]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'           => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'mezzanine_ref' => NodeRef::fromString('acme:video:video_mxf_20200409_4236f555d2f44ee5b165076cf64af34f'),
            'order_date'    => new \DateTime(),
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()->set('node_ref', $nodeRef);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
        }
    }

    public function testCreateWithKalturaMp4Url(): void
    {
        $jwplayerMediaId = 'foo';
        $stream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $response = new Response(200, [], $stream);
        $handlerStack = HandlerStack::create(new MockHandler([$response]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'             => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'kaltura_mp4_url' => 'https://www.very-cool-place.mp4',
            'order_date'      => new \DateTime(),
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()->set('node_ref', $nodeRef);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
        }
    }

    public function testCreateWithKalturaFlavor(): void
    {
        $jwplayerMediaId = 'foo';
        $stream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $response = new Response(200, [], $stream);
        $handlerStack = HandlerStack::create(new MockHandler([$response]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'             => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'kaltura_flavors' => [[
                '_schema'  => 'pbj:triniti:ovp.kaltura::flavor:1-0-0',
                'file_ext' => 'mp4',
                'url'      => 'https://www.very-cool-place.mp4',
            ]],
            'order_date'      => new \DateTime(),
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()->set('node_ref', $nodeRef);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
        }
    }

    public function testUpdateWithMezzanineUrl(): void
    {
        $jwplayerMediaId = 'udpatewithurl';
        $stream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $handlerStack = HandlerStack::create(new MockHandler([
            new Response(200, [], $stream),
            new Response(200, []),
        ]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'               => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'mezzanine_url'     => 'https://dam.test.acme.com/video/63/o/2020/01/23/63b44768ab1946ff99e42593e30816ee.mp4',
            'order_date'        => new \DateTime(),
            'jwplayer_media_id' => $jwplayerMediaId,
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()->set('node_ref', $nodeRef);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
        }
    }

    public function testUpdateWithLiveM3u8Url(): void
    {
        $jwplayerMediaId = 'foo';
        $getVideoStream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $listCaptionsStream = Utils::streamFor(serialize([
            'tracks' => [['key' => 'fun']],
        ]));
        $captionUploadUrlStream = Utils::streamFor(serialize([
            'link' => [
                'protocol' => 'ok',
                'address'  => 'ok',
                'path'     => 'ok',
                'query'    => [
                    'key'   => 'cool',
                    'token' => 'also-cool',
                ],
            ],
        ]));
        $newCaptionKey = 'daydream';
        $createCaptionResponseStream = Utils::streamFor(serialize([
            'media' => [
                'key' => $newCaptionKey,
            ],
        ]));
        $thumbnailUploadUrlStream = Utils::streamFor(serialize([
            'link' => [
                'protocol' => 'ok',
                'address'  => 'ok',
                'path'     => 'ok',
                'query'    => [
                    'key'   => 'cool',
                    'token' => 'also-cool',
                ],
            ],
        ]));
        $handlerStack = HandlerStack::create(new MockHandler([
            $response = new Response(200, [], $getVideoStream),
            $response = new Response(200, []), // update video
            $response = new Response(200, [], $listCaptionsStream),
            $response = new Response(200, []), // delete caption
            $response = new Response(200, ['Content-Length' => 123]), // get caption file
            $response = new Response(200, [], $captionUploadUrlStream),
            $response = new Response(200, [], $createCaptionResponseStream),
            $response = new Response(200, ['Content-Length' => 123]), // get thumbnail file
            $response = new Response(200, [], $thumbnailUploadUrlStream),
            $response = new Response(200, []), // upload thumbnail
        ]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $captionLanguage = 'fr';
        $node = VideoV1::fromArray([
            '_id'               => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'live_m3u8_url'     => 'https://ovp.acme.com/out/v1/43df4fbd6a714d92a376af832500af62/index.m3u8',
            'order_date'        => new \DateTime(),
            'jwplayer_media_id' => $jwplayerMediaId,
            'image_ref'         => NodeRef::fromString('acme:image:image_jpg_20200409_4236f555d2f44ee5b165076cf64af34f'),
            'caption_urls'      => [
                $captionLanguage => 'https://dam.dev.acme.com/document/bc/o/2020/03/30/bc0415813a9e42b38ef28460a4779417.vtt',
            ],
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('fields', ['captions', 'thumbnail']);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
            $this->assertTrue($event->getFromMap('jwplayer_caption_keys', $captionLanguage) === $newCaptionKey);
            $this->assertTrue($event->get('thumbnail_ref')->equals($node->get('image_ref')));
        }
    }

    public function testUpdateWithMezzanineRef(): void
    {
        $jwplayerMediaId = 'foo';
        $stream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $response1 = new Response(200, [], $stream);
        $response2 = new Response(200, []);
        $handlerStack = HandlerStack::create(new MockHandler([$response1, $response2]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'               => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'mezzanine_ref'     => NodeRef::fromString('acme:video:video_mxf_20200409_4236f555d2f44ee5b165076cf64af34f'),
            'order_date'        => new \DateTime(),
            'jwplayer_media_id' => $jwplayerMediaId,
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()->set('node_ref', $nodeRef);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
        }
    }

    public function testUpdateWithMezzanineRefAndPosterImage(): void
    {
        $jwplayerMediaId = 'foo';
        $getVideoStream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $thumbnailUploadUrlStream = Utils::streamFor(serialize([
            'link' => [
                'protocol' => 'ok',
                'address'  => 'ok',
                'path'     => 'ok',
                'query'    => [
                    'key'   => 'cool',
                    'token' => 'also-cool',
                ],
            ],
        ]));
        $handlerStack = HandlerStack::create(new MockHandler([
            $response = new Response(200, [], $getVideoStream),
            $response = new Response(200, []), // update video
            $response = new Response(200, ['Content-Length' => 123]), // get thumbnail file
            $response = new Response(200, [], $thumbnailUploadUrlStream),
            $response = new Response(200, []), // upload thumbnail
        ]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'               => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'image_ref'         => NodeRef::fromString('acme:image:image_jpg_20200409_4236f555d2f44ee5b165076cf64af34f'),
            'poster_image_ref'  => NodeRef::fromString('acme:image:image_jpg_20200409_12345678901234567890123456789012'),
            'jwplayer_media_id' => $jwplayerMediaId,
            'mezzanine_ref'     => NodeRef::fromString('acme:video:video_mxf_20200409_4236f555d2f44ee5b165076cf64af34f'),
            'order_date'        => new \DateTime(),
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('fields', ['thumbnail']);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
            $this->assertFalse($event->get('thumbnail_ref')->equals($node->get('image_ref')));
            $this->assertTrue($event->get('thumbnail_ref')->equals($node->get('poster_image_ref')));
        }
    }

    public function testUpdateWithStaleNcr(): void
    {
        $jwplayerMediaId = 'foo';
        $getVideoStream = Utils::streamFor(serialize([
            'video' => [
                'key' => $jwplayerMediaId,
            ],
        ]));
        $thumbnailUploadUrlStream = Utils::streamFor(serialize([
            'link' => [
                'protocol' => 'ok',
                'address'  => 'ok',
                'path'     => 'ok',
                'query'    => [
                    'key'   => 'cool',
                    'token' => 'also-cool',
                ],
            ],
        ]));
        $handlerStack = HandlerStack::create(new MockHandler([
            $response = new Response(200, [], $getVideoStream),
            $response = new Response(200, []), // update video
            $response = new Response(200, ['Content-Length' => 123]), // get thumbnail file
            $response = new Response(200, [], $thumbnailUploadUrlStream),
            $response = new Response(200, []), // upload thumbnail
        ]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'               => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'jwplayer_media_id' => $jwplayerMediaId,
            'mezzanine_ref'     => NodeRef::fromString('acme:video:video_mxf_20200409_4236f555d2f44ee5b165076cf64af34f'),
            'order_date'        => new \DateTime(),
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $newNode = (clone $node)->set('image_ref', NodeRef::fromString('acme:image:image_jpg_20200409_4236f555d2f44ee5b165076cf64af34f'));
        $aggregate = VideoAggregate::fromNode($node, $this->pbjx);
        $updateCommand = UpdateVideoV1::create()
            ->set('node_ref', $nodeRef)
            ->set('old_node', $node)
            ->set('new_node', $newNode);
        $aggregate->updateNode($updateCommand);
        $aggregate->commit();
        $command = SyncMediaV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('fields', ['thumbnail']);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeEvents(StreamId::fromNodeRef($nodeRef)) as $event) {
            if ($event instanceof VideoUpdatedV1) {
                continue;
            }
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('jwplayer_media_id') === $jwplayerMediaId);
            $this->assertTrue($event->get('thumbnail_ref')->equals($newNode->get('image_ref')));
        }
    }

    public function testDelete(): void
    {
        $response = new Response(200, []);
        $handlerStack = HandlerStack::create(new MockHandler([$response]));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'               => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'order_date'        => new \DateTime(),
            'mezzanine_url'     => 'https://dam.test.acme.com/video/63/o/2020/01/23/63b44768ab1946ff99e42593e30816ee.mp4',
            'jwplayer_media_id' => 'delete',
            'status'            => NodeStatus::DELETED,
        ]);
        $this->ncr->putNode($node);
        $nodeRef = $node->generateNodeRef();
        $command = SyncMediaV1::create()->set('node_ref', $nodeRef);
        $handler->handleCommand($command, $this->pbjx);
        foreach ($this->pbjx->getEventStore()->pipeAllEvents() as [$event, $streamId]) {
            $this->assertInstanceOf(MediaSyncedV1::class, $event);
            $this->assertTrue($event->get('node_ref')->equals($nodeRef));
            $this->assertNull($event->get('jwplayer_media_id'));
        }
    }

    public function testRetry(): void
    {
        $handlerStack = HandlerStack::create(new MockHandler([
                new Response(429, ['X-RateLimit-Reset' => strtotime(('+30 seconds'))])]
        ));
        $httpClient = new HttpClient(['handler' => $handlerStack]);
        $damUrlProvider = new DamUrlProvider();
        $handler = new SyncMediaHandler(
            'key',
            'secret',
            $this->ncr,
            $damUrlProvider,
            new ArtifactUrlProvider($damUrlProvider),
            new Flags($this->ncr, 'acme:flagset:test'),
            $httpClient
        );
        $node = VideoV1::fromArray([
            '_id'               => '7afcc2f1-9654-46d1-8fc1-b0511df257db',
            'order_date'        => new \DateTime(),
            'mezzanine_url'     => 'https://dam.test.acme.com/video/63/o/2020/01/23/63b44768ab1946ff99e42593e30816ee.mp4',
            'jwplayer_media_id' => 'what',
        ]);
        $nodeRef = $node->generateNodeRef();
        $this->ncr->putNode($node);
        $command = SyncMediaV1::create()->set('node_ref', $nodeRef);
        $handler->handleCommand($command, $this->pbjx);
        $expectedJobId = $nodeRef . '.sync-jwplayer-media';
        $scheduled = $this->scheduler->describeScheduled()[$expectedJobId];
        $this->assertInstanceOf(SyncMediaV1::class, $scheduled);
        $this->assertTrue($scheduled->get('node_ref')->equals($nodeRef));
        $this->assertTrue($scheduled->get('ctx_retries') === 1 + $command->get('ctx_retries'));
    }
}
