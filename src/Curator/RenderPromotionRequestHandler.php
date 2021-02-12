<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Triniti\Schemas\Curator\Enum\SearchPromotionsSort;
use Triniti\Schemas\Curator\Request\RenderPromotionResponseV1;
use Triniti\Schemas\Curator\Request\RenderWidgetRequestV1;
use Triniti\Schemas\Curator\Request\SearchPromotionsRequestV1;

class RenderPromotionRequestHandler implements RequestHandler
{
    protected Ncr $ncr;
    protected LoggerInterface $logger;

    public function __construct(Ncr $ncr, ?LoggerInterface $logger = null)
    {
        $this->ncr = $ncr;
        $this->logger = $logger ?: new NullLogger();
    }

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:curator:mixin:render-promotion-request', false);
        $curies[] = 'triniti:curator:request:render-promotion-request';
        return $curies;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = RenderPromotionResponseV1::create();
        $promotion = $this->getPromotion($request, $pbjx);
        if (null === $promotion) {
            return $response;
        }

        $response->set('promotion', $promotion);

        if (NodeStatus::DELETED()->equals($promotion->get('status'))) {
            // a deleted promotion cannot promote
            return $response;
        }

        /** @var Message $context */
        $context = $request->get('context');
        $widgets = [];

        foreach ($promotion->get('widget_refs', []) as $widgetRef) {
            $widget = $this->renderWidget($widgetRef, $request, $context, $pbjx);
            if (null !== $widget) {
                $widgets[] = $widget;
            }
        }

        $slotName = $context->get('promotion_slot');
        /** @var Message $slot */
        foreach ($promotion->get('slots', []) as $slot) {
            if (!$slot->has('widget_ref') || $slotName !== $slot->get('name')) {
                continue;
            }

            $context = clone $context;
            $context->addToMap('strings', 'rendering', (string)$slot->get('rendering'));
            $widget = $this->renderWidget($slot->get('widget_ref'), $request, $context, $pbjx);
            if (null !== $widget) {
                $widgets[] = $widget;
            }
        }

        return $response->addToList('widgets', $widgets);
    }

    /**
     * Gets a promotion by its NodeRef if available or falls back to findPromotion.
     *
     * @param Message $request
     * @param Pbjx    $pbjx
     *
     * @return Message|null
     */
    protected function getPromotion(Message $request, Pbjx $pbjx): ?Message
    {
        if (!$request->has('promotion_ref')) {
            return $this->findPromotion($request, $pbjx);
        }

        try {
            return $this->ncr->getNode($request->get('promotion_ref'));
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Unable to getPromotion for request [{pbj_schema}]',
                [
                    'exception'  => $e,
                    'pbj_schema' => $request->schema()->getId()->toString(),
                    'pbj'        => $request->toArray(),
                ]
            );
        }

        return null;
    }

    /**
     * Finds the promotion that should render for a given slot/time/etc.
     *
     * @param Message $request
     * @param Pbjx $pbjx
     *
     * @return Message|null
     */
    protected function findPromotion(Message $request, Pbjx $pbjx): ?Message
    {
        if (!$request->has('slot')) {
            return null;
        }

        try {
            $searchRequest = SearchPromotionsRequestV1::create()
                ->set('count', 1)
                ->set('status', NodeStatus::PUBLISHED())
                ->set('sort', SearchPromotionsSort::PRIORITY_DESC())
                ->set('slot', $request->get('slot'))
                ->set('render_at', $request->get('render_at') ?: $request->get('occurred_at')->toDateTime());

            /** @var Message $response */
            $response = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
            if (!$response->has('nodes')) {
                return null;
            }

            return $response->getFromListAt('nodes', 0);
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Unable to findPromotion for request [{pbj_schema}]',
                [
                    'exception'  => $e,
                    'pbj_schema' => $request->schema()->getId()->toString(),
                    'pbj'        => $request->toArray(),
                ]
            );
        }

        return null;
    }

    protected function renderWidget(NodeRef $widgetRef, Message $request, Message $context, Pbjx $pbjx): ?Message
    {
        try {
            $renderRequest = RenderWidgetRequestV1::create()
                ->set('widget_ref', $widgetRef)
                ->set('context', $context);
            return $pbjx->copyContext($request, $renderRequest)->request($renderRequest);
        } catch (\Throwable $e) {
            $this->logger->warning(
                'Unable to renderWidget for request [{pbj_schema}]',
                [
                    'exception'      => $e,
                    'pbj_schema'     => $request->schema()->getId()->toString(),
                    'pbj'            => $request->toArray(),
                    'render_context' => $context->toArray(),
                ]
            );
        }

        return null;
    }
}
