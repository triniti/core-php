<?php
declare(strict_types=1);

namespace Triniti\Ovp;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Triniti\Dam\AssetAggregate;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Ovp\Enum\TranscriptionStatus;

class UpdateTranscriptionStatusHandler implements CommandHandler
{
    protected Ncr $ncr;

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public static function handlesCuries(): array
    {
        return [
            'triniti:ovp:command:update-transcription-status',
        ];
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        $videoAssetRef = $command->get('node_ref');
        if (!$this->isNodeRefSupported($videoAssetRef)) {
            return;
        }

        if (
            !($command->has('transcription_status')
            || $command->has('transcribe_job_name')
            || $command->has('transcribe_job_region')
            || $command->has('language_code')
            || $command->has('error_code')
            || $command->has('error_message'))
        ) {
            return;
        }

        $asset = $this->ncr->getNode($videoAssetRef);
        /** @var AssetAggregate $videoAssetAggregate */
        $videoAssetAggregate = AggregateResolver::resolve($videoAssetRef->getQName())::fromNode($asset, $pbjx);
        $videoAssetAggregate->sync();
        $videoAssetAggregate->updateTranscriptionStatus($command);
        $videoAssetAggregate->commit();

        if (!TranscriptionStatus::COMPLETED()->equals($command->get('transcription_status'))) {
            return; // we only update the document and video if transcription completed
        }

        $videoAssetId = AssetId::fromString($videoAssetRef->getId());
        $documentRef = NodeRef::fromString(sprintf(
            '%s:document-asset:document_vtt_%s_%s',
            $videoAssetRef->getVendor(),
            $videoAssetId->getDate(),
            $videoAssetId->getUuid()
        ));
        $document = $this->ncr->getNode($documentRef);
        /** @var AssetAggregate $documentAssetAggregate */
        $documentAssetAggregate = AggregateResolver::resolve($documentRef->getQName())::fromNode($document, $pbjx);
        $documentAssetAggregate->sync();
        $documentAssetAggregate->updateTranscriptionStatus($command, $videoAssetAggregate->getNode()->get('title'));
        $documentAssetAggregate->commit();

        /** @var NodeRef $linkedRef */
        foreach ($videoAssetAggregate->getNode()->get('linked_refs', []) as $linkedRef) {
            if ('video' !== $linkedRef->getLabel()) {
                continue;
            }

            $video = $this->ncr->getNode($linkedRef);
            /** @var VideoAggregate $videoAggregate */
            $videoAggregate = AggregateResolver::resolve($linkedRef->getQName())::fromNode($video, $pbjx);
            $videoAggregate->sync();
            $videoAggregate->updateTranscriptionStatus($command);
            $videoAggregate->commit();
        }
    }

    protected function isNodeRefSupported(NodeRef $nodeRef): bool
    {
        return 'video-asset' === $nodeRef->getLabel();
    }
}
