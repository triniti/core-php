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
use Triniti\Dam\VideoAssetAggregate;
use Triniti\Schemas\Ovp\Enum\TranscodingStatus;

class UpdateTranscodingStatusHandler implements CommandHandler
{
    protected Ncr $ncr;

    public static function handlesCuries(): array
    {
        return ['triniti:ovp:command:update-transcoding-status'];
    }

    public function __construct(Ncr $ncr)
    {
        $this->ncr = $ncr;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if (!(
            $command->has('transcoding_status')
            || $command->has('mediaconvert_job_arn')
            || $command->has('mediaconvert_queue_arn')
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
        $aggregate->updateTranscodingStatus($command);
        $aggregate->commit($context);
        $videoAsset = $aggregate->getNode();

        if (TranscodingStatus::COMPLETED !== $command->fget('transcoding_status')) {
            // we only update the linked_refs (just videos atm) if transcoding completed
            return;
        }

        /** @var NodeRef $linkedRef */
        foreach ($videoAsset->get('linked_refs', []) as $linkedRef) {
            $method = 'update' . StringUtil::toCamelFromSlug($linkedRef->getLabel());
            if (!method_exists($this, $method)) {
                continue;
            }

            $node = $this->ncr->getNode($linkedRef, true, $context);
            $this->$method($node, $videoAsset, $command, $pbjx);
        }
    }

    protected function updateVideo(Message $video, Message $videoAsset, Message $command, Pbjx $pbjx): void
    {
        $nodeRef = $video->generateNodeRef();
        $context = ['causator' => $command];

        /** @var VideoAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($video, $pbjx);
        $aggregate->sync($context);
        $aggregate->updateTranscodingStatus($command, $videoAsset);
        $aggregate->commit($context);
    }
}
