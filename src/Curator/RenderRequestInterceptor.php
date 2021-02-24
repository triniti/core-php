<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Event\GetResponseEvent;
use Gdbots\Pbjx\Event\ResponseCreatedEvent;
use Gdbots\Pbjx\EventSubscriber;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class RenderRequestInterceptor implements EventSubscriber
{
    protected CacheItemPoolInterface $cache;

    /**
     * If a render request that is cacheable occurs we'll store
     * the CacheItem here to eliminate a double lookup since Psr6
     * doesn't provide a factory method to create a cache item
     * without a lookup.
     *
     * @var CacheItemInterface[]
     */
    protected array $cacheItems = [];

    public static function getSubscribedEvents()
    {
        return [
            'triniti:curator:mixin:render-promotion-request.before_handle'   => 'onRenderPromotionRequestBeforeHandle', // deprecated
            'triniti:curator:mixin:render-promotion-response.created'        => 'onRenderResponseCreated', // deprecated
            'triniti:curator:mixin:render-widget-request.before_handle'      => 'onRenderWidgetRequestBeforeHandle', // deprecated
            'triniti:curator:mixin:render-widget-response.created'           => 'onRenderResponseCreated', // deprecated
            'triniti:curator:request:render-promotion-request.before_handle' => 'onRenderPromotionRequestBeforeHandle',
            'triniti:curator:request:render-promotion-response.created'      => 'onRenderResponseCreated',
            'triniti:curator:request:render-widget-request.before_handle'    => 'onRenderWidgetRequestBeforeHandle',
            'triniti:curator:request:render-widget-response.created'         => 'onRenderResponseCreated',
        ];
    }

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function onRenderPromotionRequestBeforeHandle(GetResponseEvent $pbjxEvent): void
    {
        $request = $pbjxEvent->getRequest();
        if (!$request->has('context')) {
            return;
        }

        if (!$request->has('slot') && !$request->has('promotion_ref')) {
            return;
        }

        $id = $request->get('slot') ?: (string)$request->get('promotion_ref');
        if ($request->has('render_at')) {
            $id .= (string)$request->get('render_at')->getTimestamp();
        }

        $this->beforeHandle($pbjxEvent, $id);
    }

    public function onRenderWidgetRequestBeforeHandle(GetResponseEvent $pbjxEvent): void
    {
        $request = $pbjxEvent->getRequest();
        if (!$request->has('context')) {
            return;
        }

        if (!$request->has('widget_ref') && !$request->has('widget')) {
            return;
        }

        $widgetRef = $request->get('widget_ref') ?: NodeRef::fromNode($request->get('widget'));
        $this->beforeHandle($pbjxEvent, (string)$widgetRef);
    }

    public function onRenderResponseCreated(ResponseCreatedEvent $pbjxEvent): void
    {
        $response = $pbjxEvent->getResponse();
        $requestId = $response->get('ctx_request_ref')->getId();
        if (!isset($this->cacheItems[$requestId])) {
            return;
        }

        $cacheItem = $this->cacheItems[$requestId];
        unset($this->cacheItems[$requestId]);

        $request = $response->get('ctx_request');
        /** @var Message $context */
        $context = $request->get('context');

        if (!$context->get('cache_enabled')) {
            return;
        }

        $cacheItem->set($response)->expiresAfter($context->get('cache_expiry'));
        $this->cache->saveDeferred($cacheItem);
    }

    protected function beforeHandle(GetResponseEvent $pbjxEvent, string $id): void
    {
        $request = $pbjxEvent->getRequest();
        $context = $request->get('context');

        if (!$context->get('cache_enabled')) {
            return;
        }

        $cacheItem = $this->cache->getItem($this->getCacheKey($id, $context));
        if ($cacheItem->isHit()) {
            $response = $cacheItem->get();
            if ($response instanceof Message) {
                /*
                if ($response->isFrozen()) {
                    $response = clone $response;
                }

                $response->set('from_cache', true);
                */
                $pbjxEvent->setResponse($response);
                return;
            }
        }

        $this->cacheItems[(string)$request->get('request_id')] = $cacheItem;
    }

    /**
     * Returns the cache key to use for the provided request.
     * This must be compliant with psr6 "Key" definition.
     *
     * @link http://www.php-fig.org/psr/psr-6/#definitions
     *
     * @param string  $id
     * @param Message $context
     *
     * @return string
     */
    protected function getCacheKey(string $id, Message $context): string
    {
        $contextEtag = $context->generateEtag(['container']);
        $containerEtag = '';

        if ($context->has('container')) {
            /** @var Message $container */
            $container = $context->get('container');
            $containerEtag = $container->get('etag') ?: $container->generateEtag([
                'etag',
                'updated_at',
                'updater_ref',
                'last_event_ref',
            ]);
        }

        // crr (curator render request) prefix is to avoid collision
        return sprintf('crr.%s.php', md5("{$id}{$contextEtag}{$containerEtag}"));
    }
}
