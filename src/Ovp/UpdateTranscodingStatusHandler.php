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
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;

class UpdateTranscodingStatusHandler implements CommandHandler
{
    protected Ncr $ncr;
    protected ArtifactUrlProvider $artifactUrlProvider;

    public function __construct(Ncr $ncr, ArtifactUrlProvider $artifactUrlProvider)
    {
        $this->ncr = $ncr;
        $this->artifactUrlProvider = $artifactUrlProvider;
    }

    public static function handlesCuries(): array
    {
        return [
            'triniti:ovp:command:update-transcoding-status',
        ];
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        /** @var NodeRef $videoAssetRef */
        $videoAssetRef = $command->get('node_ref');
        if (!$this->isNodeRefSupported($videoAssetRef)) {
            return;
        }

        if (
            !($command->has('transcoding_status')
            || $command->has('mediaconvert_job_arn')
            || $command->has('mediaconvert_queue_arn')
            || $command->has('error_code')
            || $command->has('error_message'))
        ) {
            return;
        }

        $asset = $this->ncr->getNode($videoAssetRef);
        /** @var AssetAggregate $assetAggregate */
        $assetAggregate = AggregateResolver::resolve($videoAssetRef->getQName())::fromNode($asset, $pbjx);
        $assetAggregate->sync();
        $assetAggregate->updateTranscodingStatus($command);
        $assetAggregate->commit();

        if (!TranscodingStatus::COMPLETED()->equals($command->get('transcoding_status'))) {
            return; // we only update the video if transcoding completed
        }

        /** @var NodeRef $linkedRef */
        foreach ($assetAggregate->getNode()->get('linked_refs', []) as $linkedRef) {
            if ('video' !== $linkedRef->getLabel()) {
                continue;
            }
            $video = $this->ncr->getNode($linkedRef);
            /** @var VideoAggregate $videoAggregate */
            $videoAggregate = AggregateResolver::resolve($linkedRef->getQName())::fromNode($video, $pbjx);
            $videoAggregate->sync();
            $videoAggregate->updateTranscodingStatus($command);
            $videoAggregate->commit();
        }
    }

    protected function isNodeRefSupported(NodeRef $nodeRef): bool
    {
        return 'video-asset' === $nodeRef->getLabel();
    }
}
