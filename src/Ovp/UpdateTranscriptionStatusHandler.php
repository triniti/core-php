<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Triniti\Dam\DocumentAssetAggregate;
use Triniti\Dam\VideoAssetAggregate;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;

class UpdateTranscriptionStatusHandler implements CommandHandler
{
    protected Ncr $ncr;

    public static function handlesCuries(): array
    {
        return ['triniti:ovp:command:update-transcription-status'];
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if (!(
            $command->has('transcription_status')
            || $command->has('transcribe_job_name')
            || $command->has('transcribe_job_region')
            || $command->has('language_code')
            || $command->has('error_code')
            || $command->has('error_message')
        )) {
            return;
        }

        /** @var NodeRef $videoAssetRef */
        $videoAssetRef = $command->get('node_ref');
        $context = ['causator' => $command];
        $videoAsset = $this->ncr->getNode($videoAssetRef, true, $context);

        /** @var VideoAssetAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($videoAssetRef->getQName())::fromNode($videoAsset, $pbjx);
        $aggregate->sync($context);
        $aggregate->updateTranscriptionStatus($command);
        $aggregate->commit($context);
        $videoAsset = $aggregate->getNode();

        if (TranscriptionStatus::COMPLETED->value !== $command->fget('transcription_status')) {
            // we only update the document and video if transcription completed
            return;
        }

        /** @var AssetId $videoAssetId */
        $videoAssetId = $videoAsset->get('_id');
        $documentRef = NodeRef::fromString(sprintf(
            '%s:document-asset:document_vtt_%s_%s',
            $videoAssetRef->getVendor(),
            $videoAssetId->getDate(),
            $videoAssetId->getUuid()
        ));

        /** @var NodeRef[] $linkedRefs */
        $linkedRefs = array_merge($videoAsset->get('linked_refs', []), [$documentRef]);

        foreach ($linkedRefs as $linkedRef) {
            $method = 'update' . StringUtil::toCamelFromSlug($linkedRef->getLabel());
            if (!method_exists($this, $method)) {
                continue;
            }

            $node = $this->ncr->getNode($linkedRef, true, $context);
            $this->$method($node, $videoAsset, $command, $pbjx);
        }
    }

    protected function updateDocumentAsset(Message $documentAsset, Message $videoAsset, Message $command, Pbjx $pbjx): void
    {
        $nodeRef = $documentAsset->generateNodeRef();
        $context = ['causator' => $command];

        /** @var DocumentAssetAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($documentAsset, $pbjx);
        $aggregate->sync($context);
        $aggregate->updateTranscriptionStatus($command, $videoAsset);
        $aggregate->commit($context);
    }

    protected function updateVideo(Message $video, Message $videoAsset, Message $command, Pbjx $pbjx): void
    {
        $nodeRef = $video->generateNodeRef();
        $context = ['causator' => $command];

        /** @var VideoAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($video, $pbjx);
        $aggregate->sync($context);
        $aggregate->updateTranscriptionStatus($command, $videoAsset);
        $aggregate->commit($context);
    }
}
