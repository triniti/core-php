<?php
declare(strict_types=1);

namespace Triniti\Curator\Twig;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\Serializer\PhpArraySerializer;
use Gdbots\Pbj\Serializer\Serializer;
use Gdbots\Pbj\WellKnown\MessageRef;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Triniti\Schemas\Common\RenderContextV1;
use Triniti\Schemas\Curator\Enum\SearchPromotionsSort;
use Triniti\Schemas\Curator\Request\RenderPromotionRequestV1;
use Triniti\Schemas\Curator\Request\RenderWidgetRequestV1;
use Triniti\Schemas\Curator\Request\SearchPromotionsRequestV1;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CuratorExtension extends AbstractExtension
{
    private static ?PhpArraySerializer $serializer = null;
    private Pbjx $pbjx;
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    public function __construct(Pbjx $pbjx, RequestStack $requestStack, ?LoggerInterface $logger = null)
    {
        $this->pbjx = $pbjx;
        $this->requestStack = $requestStack;
        $this->logger = $logger ?: new NullLogger();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'curator_render_widget',
                [$this, 'renderWidget'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),

            new TwigFunction(
                'curator_find_promotion',
                [$this, 'findPromotion'],
                ['needs_environment' => true]
            ),

            new TwigFunction(
                'curator_render_promotion',
                [$this, 'renderPromotion'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),

            new TwigFunction(
                'curator_render_promotion_slots',
                [$this, 'renderPromotionSlots'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    /**
     * @param Environment                  $twig
     * @param Message|NodeRef|array|string $widget
     * @param Message|array                $context
     * @param bool                         $returnResponse For when you want the raw render response and not its html.
     *                                                     This is some next level shit here folks.
     *
     * @return string|null|Message
     */
    public function renderWidget(Environment $twig, $widget, $context = [], bool $returnResponse = false)
    {
        try {
            $request = RenderWidgetRequestV1::create();
            if (!$context instanceof Message) {
                $container = $context['container'] ?? null;
                if ($container instanceof Message) {
                    unset($context['container']);
                }

                $context = RenderContextV1::fromArray($context);
                if ($container instanceof Message) {
                    $context->set('container', $container);
                }
            }

            $this->enrichContext($context);
            $request->set('context', $context);

            if ($widget instanceof Message) {
                $request->set('widget', $widget);
            } elseif ($widget instanceof NodeRef) {
                $request->set('widget_ref', $widget);
            } elseif (is_string($widget)) {
                $request->set('widget_ref', NodeRef::fromString($widget));
            } elseif (is_array($widget)) {
                $widget = self::getSerializer()->deserialize($widget);
                $request->set('widget', $widget);
            } else {
                // no widget?, no problem
                return null;
            }

            // ensures permission check is bypassed
            $request->set('ctx_causator_ref', $request->generateMessageRef());
            $response = $this->pbjx->request($request);
            return $returnResponse ? $response : trim($response->get('html', ''));
        } catch (\Throwable $e) {
            if ($twig->isDebug()) {
                throw $e;
            }

            $widgetRef = $widget instanceof Message ? NodeRef::fromNode($widget) : $widget;
            $this->logger->warning('curator_render_widget failed to render [{widget_ref}].', [
                'exception'      => $e,
                'widget_ref'     => (string)$widgetRef,
                'widget'         => $widget instanceof Message ? $widget->toArray() : $widget,
                'render_context' => $context instanceof Message ? $context->toArray() : $context,
            ]);
        }

        return null;
    }

    public function findPromotion(Environment $twig, string $slot): ?Message
    {
        try {
            $searchRequest = SearchPromotionsRequestV1::create()
                ->set('count', 1)
                ->set('status', NodeStatus::PUBLISHED())
                ->set('sort', SearchPromotionsSort::PRIORITY_DESC())
                ->set('slot', $slot);

            // ensures permission check is bypassed
            $searchRequest->set('ctx_causator_ref', $searchRequest->generateMessageRef());
            $response = $this->pbjx->request($searchRequest);
            if (!$response->has('nodes')) {
                return null;
            }

            return $response->getFromListAt('nodes', 0);
        } catch (\Throwable $e) {
            if ($twig->isDebug()) {
                throw $e;
            }

            $this->logger->warning('curator_find_promotion failed to find promotion for [{slot}].', [
                'exception' => $e,
                'slot'      => $slot,
            ]);
        }

        return null;
    }

    /**
     * @param Environment   $twig
     * @param string        $slot
     * @param Message|array $context
     * @param bool          $returnResponse For when you want the raw render response.
     *
     * @return string|null|Message
     */
    public function renderPromotion(Environment $twig, string $slot, $context = [], bool $returnResponse = false)
    {
        try {
            $request = RenderPromotionRequestV1::create();

            if (!$context instanceof Message) {
                $container = $context['container'] ?? null;
                if ($container instanceof Message) {
                    unset($context['container']);
                }

                $context = RenderContextV1::fromArray($context);
                if ($container instanceof Message) {
                    $context->set('container', $container);
                }
            }

            $this->enrichContext($context);
            if (!$context->isFrozen() && !$context->has('promotion_slot')) {
                $context->set('promotion_slot', $slot);
            }

            $request->set('context', $context);
            $request->set('slot', $slot);

            // ensures permission check is bypassed
            $request->set('ctx_causator_ref', $request->generateMessageRef());
            $response = $this->pbjx->request($request);
            if ($returnResponse) {
                return $response;
            }

            $html = ["<!-- start: promotion-slot ${slot} -->"];

            /** @var Message $promotion */
            $promotion = $response->get('promotion');
            if (null !== $promotion) {
                $html[] = trim($promotion->get('pre_render_code', ''));
            }

            /** @var Message $renderWidgetResponse */
            foreach ($response->get('widgets', []) as $renderWidgetResponse) {
                $html[] = trim($renderWidgetResponse->get('html', ''));
            }

            if (null !== $promotion) {
                $html[] = trim($promotion->get('post_render_code', ''));
            }

            $html[] = "<!-- end: promotion-slot ${slot} -->";

            return trim(implode(PHP_EOL, $html));
        } catch (\Throwable $e) {
            if ($twig->isDebug()) {
                throw $e;
            }

            $this->logger->warning('curator_render_promotion failed to render slot [{slot}].', [
                'exception'      => $e,
                'slot'           => $slot,
                'render_context' => $context instanceof Message ? $context->toArray() : $context,
            ]);
        }

        return null;
    }

    /**
     * @param Environment            $twig
     * @param Message|NodeRef|string $promotionOrRef
     * @param Message|array          $context
     * @param bool                   $returnResponse For when you want the raw render response.
     *
     * @return string|null|Message
     */
    public function renderPromotionSlots(Environment $twig, $promotionOrRef, $context, bool $returnResponse = false)
    {
        $promotionRef = $this->toNodeRef($promotionOrRef);
        $slot = null;
        if (null === $promotionRef) {
            return null;
        }

        try {
            $request = RenderPromotionRequestV1::create();
            if (!$context instanceof Message) {
                $container = $context['container'] ?? null;
                if ($container instanceof Message) {
                    unset($context['container']);
                }

                $context = RenderContextV1::fromArray($context);
                if ($container instanceof Message) {
                    $context->set('container', $container);
                }
            }

            $this->enrichContext($context);
            $request->set('context', $context);
            if (!$context->has('promotion_slot')) {
                return null;
            }

            $request->set('promotion_ref', $promotionRef);

            // ensures permission check is bypassed
            $request->set('ctx_causator_ref', $request->generateMessageRef());
            $response = $this->pbjx->request($request);
            if ($returnResponse) {
                return $response;
            }

            $slot = $context->get('promotion_slot');
            $html = ["<!-- start: ${promotionRef}#${slot} -->"];

            /** @var Message $renderWidgetResponse */
            foreach ($response->get('widgets', []) as $renderWidgetResponse) {
                $html[] = trim($renderWidgetResponse->get('html', ''));
            }

            $html[] = "<!-- end: ${promotionRef}#${slot} -->";

            return trim(implode(PHP_EOL, $html));
        } catch (\Throwable $e) {
            if ($twig->isDebug()) {
                throw $e;
            }

            $this->logger->warning('curator_render_promotion_slots failed to render [{promotion_ref}#{slot}].', [
                'exception'      => $e,
                'promotion_ref'  => $promotionRef,
                'slot'           => $slot,
                'render_context' => $context instanceof Message ? $context->toArray() : $context,
            ]);
        }

        return null;
    }

    private function enrichContext(Message $context): void
    {
        if ($context->isFrozen()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        foreach (['device_view', 'viewer_country'] as $key) {
            if (!$context->has($key) && $request->attributes->has($key)) {
                $context->set($key, $request->attributes->get($key));
            }
        }
    }

    private function toNodeRef($val): ?NodeRef
    {
        if ($val instanceof NodeRef) {
            return $val;
        } else if (empty($val)) {
            return null;
        } else if ($val instanceof MessageRef) {
            return NodeRef::fromMessageRef($val);
        } else if ($val instanceof Message) {
            return NodeRef::fromNode($val);
        }

        try {
            return NodeRef::fromString((string)$val);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getSerializer(): Serializer
    {
        if (null === self::$serializer) {
            self::$serializer = new PhpArraySerializer();
        }

        return self::$serializer;
    }
}
