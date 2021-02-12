<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Node\DocumentAssetV1;
use Acme\Schemas\Dam\Node\VideoAssetV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\Dam\NcrAssetProjector;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;
use Triniti\Schemas\Ovp\Event\TranscodingCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscodingFailedV1;
use Triniti\Schemas\Ovp\Event\TranscodingStartedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionCompletedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionFailedV1;
use Triniti\Schemas\Ovp\Event\TranscriptionStartedV1;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;

final class NcrAssetProjectorTest extends AbstractPbjxTest
{
    protected NcrAssetProjector $projector;
    protected MockNcrSearch $ncrSearch;

    public function setup(): void
    {
        parent::setup();
        $this->ncrSearch = new MockNcrSearch();
        $this->projector = new NcrAssetProjector($this->ncr, $this->ncrSearch);
    }

    public function testOnTranscodingCompleted(): void
    {
        $node = VideoAssetV1::create()
            ->set('_id', AssetId::create('video', 'mxf'))
            ->set('mime_type', 'application/mxf');

        $nodeRef = $node->generateNodeRef();
        $this->ncr->putNode($node);

        $mediaconvertQueueArn = 'arn:aws:mediaconvert:us-west-2:123456789012:queues/acme-dev-ovp-default';
        $mediaconvertJobArn = 'arn:aws:mediaconvert:us-west-2:123456789012:jobs/1594926059502-8tzaix';
        $event = TranscodingCompletedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('mediaconvert_queue_arn', $mediaconvertQueueArn)
            ->set('mediaconvert_job_arn', $mediaconvertJobArn);

        $this->projector->onTranscodingCompleted($event, $this->pbjx);
        $this->assertTrue($this->ncrSearch->hasIndexedNode($nodeRef));
        $actualNode = $this->ncr->getNode($nodeRef);
        $this->assertEquals(TranscodingStatus::COMPLETED(), $actualNode->get('transcoding_status'));
        $this->assertEquals($mediaconvertQueueArn, $actualNode->getFromMap('tags', 'mediaconvert_queue_arn'));
        $this->assertEquals($mediaconvertJobArn, $actualNode->getFromMap('tags', 'mediaconvert_job_arn'));
    }

    public function testOnTranscodingFailed(): void
    {
        $node = VideoAssetV1::create()
            ->set('_id', AssetId::create('video', 'mxf'))
            ->set('mime_type', 'application/mxf');

        $nodeRef = $node->generateNodeRef();
        $this->ncr->putNode($node);

        $mediaconvertQueueArn = 'arn:aws:mediaconvert:us-west-2:123456789012:queues/acme-dev-ovp-default';
        $mediaconvertJobArn = 'arn:aws:mediaconvert:us-west-2:123456789012:jobs/1594926059502-8tzaix';
        $code = Code::CANCELLED();
        $event = TranscodingFailedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('mediaconvert_queue_arn', $mediaconvertQueueArn)
            ->set('mediaconvert_job_arn', $mediaconvertJobArn)
            ->set('error_code', $code);

        $this->projector->onTranscodingFailed($event, $this->pbjx);
        $this->assertTrue($this->ncrSearch->hasIndexedNode($nodeRef));
        $actualNode = $this->ncr->getNode($nodeRef);
        $this->assertEquals(TranscodingStatus::FAILED(), $actualNode->get('transcoding_status'));
        $this->assertEquals($mediaconvertQueueArn, $actualNode->getFromMap('tags', 'mediaconvert_queue_arn'));
        $this->assertEquals($mediaconvertJobArn, $actualNode->getFromMap('tags', 'mediaconvert_job_arn'));
        $this->assertEquals($code->getName(), $actualNode->getFromMap('tags', 'transcode_error_name'));
        $this->assertEquals((string)$code->getValue(), $actualNode->getFromMap('tags', 'transcode_error_code'));
    }

    public function testOnTranscodingStarted(): void
    {
        $node = VideoAssetV1::create()
            ->set('_id', AssetId::create('video', 'mxf'))
            ->set('mime_type', 'application/mxf');

        $nodeRef = NodeRef::fromNode($node);
        $this->ncr->putNode($node);

        $mediaconvertQueueArn = 'arn:aws:mediaconvert:us-west-2:123456789012:queues/acme-dev-ovp-default';
        $mediaconvertJobArn = 'arn:aws:mediaconvert:us-west-2:123456789012:jobs/1594926059502-8tzaix';
        $event = TranscodingStartedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('mediaconvert_queue_arn', $mediaconvertQueueArn)
            ->set('mediaconvert_job_arn', $mediaconvertJobArn);

        $this->projector->onTranscodingStarted($event, $this->pbjx);
        $this->assertTrue($this->ncrSearch->hasIndexedNode($nodeRef));
        $actualNode = $this->ncr->getNode($nodeRef);
        $this->assertEquals(TranscodingStatus::PROCESSING(), $actualNode->get('transcoding_status'));
        $this->assertEquals($mediaconvertQueueArn, $actualNode->getFromMap('tags', 'mediaconvert_queue_arn'));
        $this->assertEquals($mediaconvertJobArn, $actualNode->getFromMap('tags', 'mediaconvert_job_arn'));
    }

    public function testOnDocumentTranscriptionCompleted(): void
    {
        $title = 'thylacine daydream';
        $video = VideoAssetV1::create()
            ->set('title', $title)
            ->set('_id', AssetId::create('video', 'mxf'))
            ->set('mime_type', 'application/mxf');

        $videoRef = $video->generateNodeRef();
        $this->ncr->putNode($video);

        $document = DocumentAssetV1::create()
            ->set('_id', AssetId::create('document', 'vtt'))
            ->set('mime_type', 'text/vtt')
            ->addToSet('linked_refs', [$videoRef]);
        $documentRef = NodeRef::fromNode($document);
        $this->ncr->putNode($document);

        $event = TranscriptionCompletedV1::create()
            ->set('node_ref', $documentRef);

        $this->projector->onTranscriptionCompleted($event, $this->pbjx);
        $this->assertTrue($this->ncrSearch->hasIndexedNode($documentRef));
        $actualDocument = $this->ncr->getNode($documentRef);
        $this->assertEquals($title, $actualDocument->get('title'));
    }

    public function testOnVideoTranscriptionCompleted(): void
    {
        $node = VideoAssetV1::create()
            ->set('_id', AssetId::create('video', 'mxf'))
            ->set('mime_type', 'application/mxf');
        $nodeRef = $node->generateNodeRef();
        $this->ncr->putNode($node);

        $transcribeJobName = 'video_mxf_20200716_19cc701d95a04dba8f3183f4cae2463e-transcribed';
        $transcribeJobRegion = 'us-west-2';
        $languageCode = 'en-US';
        $event = TranscriptionCompletedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('transcribe_job_name', $transcribeJobName)
            ->set('transcribe_job_region', $transcribeJobRegion)
            ->set('language_code', $languageCode);

        $this->projector->onTranscriptionCompleted($event, $this->pbjx);
        $this->assertTrue($this->ncrSearch->hasIndexedNode($nodeRef));
        $actualNode = $this->ncr->getNode($nodeRef);
        $this->assertEquals(TranscriptionStatus::COMPLETED(), $actualNode->get('transcription_status'));
        $this->assertEquals($transcribeJobName, $actualNode->getFromMap('tags', 'transcribe_job_name'));
        $this->assertEquals($transcribeJobRegion, $actualNode->getFromMap('tags', 'transcribe_job_region'));
        $this->assertEquals($languageCode, $actualNode->getFromMap('tags', 'language_code'));
    }

    public function testOnTranscriptionFailed(): void
    {
        $node = VideoAssetV1::create()
            ->set('_id', AssetId::create('video', 'mxf'))
            ->set('mime_type', 'application/mxf');

        $nodeRef = NodeRef::fromNode($node);
        $this->ncr->putNode($node);

        $transcribeJobName = 'video_mxf_20200716_19cc701d95a04dba8f3183f4cae2463e-transcribed';
        $transcribeJobRegion = 'us-west-2';
        $languageCode = 'en-US';
        $code = Code::CANCELLED();
        $event = TranscriptionFailedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('transcribe_job_name', $transcribeJobName)
            ->set('transcribe_job_region', $transcribeJobRegion)
            ->set('language_code', $languageCode)
            ->set('error_code', $code);

        $this->projector->onTranscriptionFailed($event, $this->pbjx);
        $transcodingCompletedVideo = $this->ncr->getNode($nodeRef);
        $this->assertEquals(TranscriptionStatus::FAILED(), $transcodingCompletedVideo->get('transcription_status'));
        $this->assertEquals($transcribeJobName, $transcodingCompletedVideo->getFromMap('tags', 'transcribe_job_name'));
        $this->assertEquals($transcribeJobRegion, $transcodingCompletedVideo->getFromMap('tags', 'transcribe_job_region'));
        $this->assertEquals($languageCode, $transcodingCompletedVideo->getFromMap('tags', 'language_code'));
        $this->assertEquals($code->getName(), $transcodingCompletedVideo->getFromMap('tags', 'transcribe_error_name'));
        $this->assertEquals((string)$code->getValue(), $transcodingCompletedVideo->getFromMap('tags', 'transcribe_error_code'));
    }

    public function testOnTranscriptionStarted(): void
    {
        $node = VideoAssetV1::create()
            ->set('_id', AssetId::create('video', 'mxf'))
            ->set('mime_type', 'application/mxf');

        $nodeRef = NodeRef::fromNode($node);
        $this->ncr->putNode($node);

        $transcribeJobName = 'video_mxf_20200716_19cc701d95a04dba8f3183f4cae2463e-transcribed';
        $transcribeJobRegion = 'us-west-2';
        $languageCode = 'en-US';
        $event = TranscriptionStartedV1::create()
            ->set('node_ref', $nodeRef)
            ->set('transcribe_job_name', $transcribeJobName)
            ->set('transcribe_job_region', $transcribeJobRegion)
            ->set('language_code', $languageCode);

        $this->projector->onTranscriptionStarted($event, $this->pbjx);
        $this->assertTrue($this->ncrSearch->hasIndexedNode($nodeRef));
        $transcodingCompletedVideo = $this->ncr->getNode($nodeRef);
        $this->assertEquals(TranscriptionStatus::PROCESSING(), $transcodingCompletedVideo->get('transcription_status'));
        $this->assertEquals($transcribeJobName, $transcodingCompletedVideo->getFromMap('tags', 'transcribe_job_name'));
        $this->assertEquals($transcribeJobRegion, $transcodingCompletedVideo->getFromMap('tags', 'transcribe_job_region'));
        $this->assertEquals($languageCode, $transcodingCompletedVideo->getFromMap('tags', 'language_code'));
    }
}
