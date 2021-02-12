<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Schemas\News\Request\SearchArticlesRequestV1;

class RemoveArticleSlottingHandler implements CommandHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:news:mixin:remove-article-slotting:v1', false);
        $curies[] = 'triniti:news:command:remove-article-slotting';
        return $curies;
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if (!$command->has('slotting')) {
            return;
        }

        $exceptRef = $command->get('except_ref');
        $query = [];
        foreach ($command->get('slotting') as $key => $value) {
            $query[] = "slotting.{$key}:{$value}";
        }

        $request = SearchArticlesRequestV1::create()
            ->set('q', implode(' OR ', $query))
            ->set('status', NodeStatus::PUBLISHED());
        $pbjx->copyContext($command, $request);

        $response = $pbjx->request($request);
        /** @var Message $node */
        foreach ($response->get('nodes', []) as $node) {
            $nodeRef = $node->generateNodeRef();
            if (null !== $exceptRef && $exceptRef->equals($nodeRef)) {
                continue;
            }

            /** @var ArticleAggregate $aggregate */
            $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
            $aggregate->sync(['causator' => $command]);
            $aggregate->removeArticleSlotting($command);
            $aggregate->commit();
        }
    }
}
