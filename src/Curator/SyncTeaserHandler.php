<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Command\CreateNodeV1;
use Gdbots\Schemas\Ncr\Command\UpdateNodeV1;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Sys\Flags;

class SyncTeaserHandler implements CommandHandler
{
    const DISABLED_FLAG_NAME = 'teaser_sync_disabled';
    const AUTOCREATE_DISABLED_FLAG_NAME = 'teaser_autocreate_disabled';
    const AUTOCREATE_TYPE_DISABLED_FLAG_NAME = 'teaser_autocreate_%s_disabled';

    use SyncTeaserTrait;

    protected Flags $flags;
    protected TeaserTransformer $transformer;

    public static function handlesCuries(): array
    {
        $curies = MessageResolver::findAllUsingMixin('triniti:curator:mixin:sync-teaser:v1', false);
        $curies[] = 'triniti:curator:command:sync-teaser';
        return $curies;
    }

    public function __construct(Ncr $ncr, Flags $flags, TeaserTransformer $transformer)
    {
        $this->ncr = $ncr;
        $this->flags = $flags;
        $this->transformer = $transformer;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if ($this->flags->getBoolean(self::DISABLED_FLAG_NAME)) {
            return;
        }

        if ($command->has('target_ref')) {
            $this->handleSyncByTargetRef($command, $pbjx);
            return;
        }

        if ($command->has('teaser_ref')) {
            $this->handleSyncByTeaserRef($command, $pbjx);
        }
    }

    protected function isNodeSupported(Message $node): bool
    {
        return $node::schema()->hasMixin('triniti:curator:mixin:teaser-has-target');
    }

    /**
     * When syncing by target_ref, any existing teasers for that target that
     * are set to sync_with_target will be synced.
     *
     * If no teasers exist for that target, one will be created.
     *
     * @param Message $causator
     * @param Pbjx    $pbjx
     */
    protected function handleSyncByTargetRef(Message $causator, Pbjx $pbjx): void
    {
        /** @var NodeRef $targetRef */
        $targetRef = $causator->get('target_ref');
        $teasers = $this->getTeasers($targetRef);
        $context = ['causator' => $causator];

        $target = $this->ncr->getNode($targetRef, true, $context);
        if (count($teasers['all']) > 0) {
            $this->updateTeasers($causator, $pbjx, $teasers['sync'], $target);
            return;
        }

        if ($this->flags->getBoolean(self::AUTOCREATE_DISABLED_FLAG_NAME)) {
            return;
        }

        $typeDisabledFlag = sprintf(
            self::AUTOCREATE_TYPE_DISABLED_FLAG_NAME,
            StringUtil::toSnakeFromSlug($target::schema()->getQName()->getMessage())
        );

        if ($this->flags->getBoolean($typeDisabledFlag)) {
            return;
        }

        if (!$this->shouldAutoCreateTeaser($target)) {
            return;
        }

        if (NodeStatus::PUBLISHED === $target->fget('status')) {
            /*
             * for now we don't create teasers for already published targets
             * simply because we are not yet handling the auto publishing
             * of the teaser when that scenario occurs.
             *
             * todo: solve auto publishing on newly created teasers.
             */
            return;
        }

        $teaser = $this->transformer::transform($target);
        $command = CreateNodeV1::create()->set('node', $teaser);
        $pbjx->copyContext($causator, $command);
        $command->set('ctx_user_ref', $causator->get('ctx_user_ref', $target->get('creator_ref')));

        $aggregate = AggregateResolver::resolve($teaser->generateNodeRef()->getQName())::fromNode($teaser, $pbjx);
        $aggregate->createNode($command);
        $aggregate->commit($context);
    }

    /**
     * When syncing by teaser_ref, the teaser will always be synced regardless
     * of the value of sync_with_target.
     *
     * @param Message $causator
     * @param Pbjx    $pbjx
     */
    protected function handleSyncByTeaserRef(Message $causator, Pbjx $pbjx): void
    {
        $context = ['causator' => $causator];

        $teaser = $this->ncr->getNode($causator->get('teaser_ref'), true, $context);
        if (!$this->isNodeSupported($teaser)) {
            return;
        }

        $target = $this->ncr->getNode($teaser->get('target_ref'), true, $context);
        $this->updateTeasers($causator, $pbjx, [$teaser], $target);
    }

    protected function updateTeasers(Message $causator, Pbjx $pbjx, array $teasers, Message $target): void
    {
        $context = ['causator' => $causator];

        /** @var Message $teaser */
        foreach ($teasers as $teaser) {
            $teaserRef = $teaser->generateNodeRef();
            $aggregate = AggregateResolver::resolve($teaserRef->getQName())::fromNode($teaser, $pbjx);
            $aggregate->sync($context);
            $newTeaser = $this->transformer::transform($target, $aggregate->getNode());

            $command = UpdateNodeV1::create()
                ->set('node_ref', $teaserRef)
                ->set('new_node', $newTeaser);

            $pbjx->copyContext($causator, $command);
            $command->set('ctx_user_ref', $causator->get('ctx_user_ref', $target->get('updater_ref')));

            $aggregate->updateNode($command);
            $aggregate->commit($context);
        }
    }

    protected function shouldAutoCreateTeaser(Message $target): bool
    {
        $types = [
            'article' => true,
            'gallery' => true,
            'video'   => true,
        ];

        return $types[$target::schema()->getQName()->getMessage()] ?? false;
    }
}
