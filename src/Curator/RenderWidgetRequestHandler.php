<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Triniti\Schemas\Curator\Enum\SlotRendering;
use Triniti\Schemas\Curator\Request\RenderWidgetResponseV1;
use Twig\Environment;

class RenderWidgetRequestHandler implements RequestHandler
{
    protected Ncr $ncr;
    protected Environment $twig;
    protected LoggerInterface $logger;

    public function __construct(Ncr $ncr, Environment $twig, ?LoggerInterface $logger = null)
    {
        $this->ncr = $ncr;
        $this->twig = $twig;
        $this->logger = $logger ?: new NullLogger();
    }

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:curator:mixin:render-widget-request', false);
        $curies[] = 'triniti:curator:request:render-widget-request';
        return $curies;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = RenderWidgetResponseV1::create();
        $widget = $this->getWidget($request, $pbjx);

        if (null === $widget) {
            return $response;
        }

        /** @var Message $context */
        $context = $request->get('context');
        $rendering = $context->getFromMap('strings', 'rendering', SlotRendering::SERVER);

        $searchResponse = $rendering === SlotRendering::SERVER
            ? $this->runWidgetSearchRequest($widget, $request, $pbjx)
            : null;

        if ('json' === $context->get('format')) {
            return $response->set('search_response', $searchResponse);
        }

        $curie = $widget::schema()->getCurie();
        $widgetName = str_replace('-', '_', $curie->getMessage());
        $template = $this->findTemplate($context, $widgetName);
        $hasNodes = null !== $searchResponse ? $searchResponse->has('nodes') : false;
        try {
            $html = $this->twig->render($template, [
                'pbj'             => $widget,
                'pbj_name'        => $widgetName,
                'context'         => $context,
                'render_request'  => $request,
                'search_response' => $searchResponse,
                'has_nodes'       => $hasNodes,
                'device_view'     => $context->get('device_view'),
                'viewer_country'  => $context->get('viewer_country'),
            ]);
        } catch (\Throwable $e) {
            if ($this->twig->isDebug()) {
                throw $e;
            }

            $this->logger->warning(
                'Unable to render [{curie}] with template [{twig_template}].',
                [
                    'exception'      => $e,
                    'curie'          => $curie->toString(),
                    'twig_template'  => $template,
                    'pbj'            => $widget->toArray(),
                    'render_context' => $context->toArray(),
                ]
            );

            $html = null;
        }

        return $response->set('html', $html);
    }

    protected function findTemplate(Message $context, string $widgetName): string
    {
        $platform = $context->get('platform', 'web');
        $deviceView = $context->get('device_view', '');
        $format = $context->has('format') ? ".{$context->get('format')}" : '';
        $rendering = $context->getFromMap('strings', 'rendering', SlotRendering::SERVER);
        $rendering = $rendering !== SlotRendering::SERVER ? ".{$rendering}" : '';
        $templates = [];

        if ($context->has('section')) {
            $section = strtolower(str_replace('-', '_', $context->get('section')));
            if ($context->has('device_view')) {
                $templates[] = "{$section}/{$widgetName}/{$widgetName}{$rendering}.{$deviceView}";
            }
            $templates[] = "{$section}/{$widgetName}/{$widgetName}{$rendering}";
        }

        if ($context->has('device_view')) {
            $templates[] = "{$widgetName}/{$widgetName}{$rendering}.{$deviceView}";
        }
        $templates[] = "{$widgetName}/{$widgetName}{$rendering}";

        if ($rendering !== SlotRendering::SERVER) {
            if ($context->has('device_view')) {
                $templates[] = "widget{$rendering}.{$deviceView}";
            }
            $templates[] = "widget{$rendering}";
        }

        $loader = $this->twig->getLoader();
        foreach ($templates as $template) {
            $name = "@curator_widgets/{$platform}/{$template}{$format}.twig";
            if ($loader->exists($name)) {
                return $name;
            }
        }

        return "@curator_widgets/{$platform}/missing_widget{$format}.twig";
    }

    protected function getWidget(Message $request, Pbjx $pbjx): ?Message
    {
        if ($request->has('widget')) {
            return $request->get('widget');
        }

        if (!$request->has('widget_ref')) {
            return null;
        }

        try {
            return $this->ncr->getNode($request->get('widget_ref'));
        } catch (\Throwable $e) {
            if ($this->twig->isDebug()) {
                throw $e;
            }

            $this->logger->warning(
                'Unable to getWidget for request [{pbj_schema}]',
                [
                    'exception'  => $e,
                    'pbj_schema' => $request->schema()->getId()->toString(),
                    'pbj'        => $request->toArray(),
                ]
            );
        }

        return null;
    }

    protected function runWidgetSearchRequest(Message $widget, Message $request, Pbjx $pbjx): ?Message
    {
        if (!$widget->has('search_request')) {
            return null;
        }

        /** @var Message $searchRequest */
        $searchRequest = clone $widget->get('search_request');

        /*
         * a search request is stored with the widget so these fields
         * need to be reset so they are correct for when the request
         * is actually running, which is now.  not now now, as of me
         * writing this comment right now, but the now when the now
         * is at runtime.
         */
        foreach ($searchRequest::schema()->getMixin('gdbots:pbjx:mixin:request')->getFields() as $field) {
            $searchRequest->clear($field->getName());
        }

        /*
         * widgets are, at the time of writing this, for the consumers so we only
         * want to include published content when running the search request.
         */
        if ($searchRequest::schema()->hasMixin('gdbots:ncr:mixin:search-nodes-request')) {
            $searchRequest->set('status', NodeStatus::PUBLISHED());
        }

        try {
            return $pbjx->copyContext($request, $searchRequest)->request($searchRequest);
        } catch (\Throwable $e) {
            if ($this->twig->isDebug()) {
                throw $e;
            }

            $this->logger->warning(
                'Unable to run widget search request [{pbj_schema}]',
                [
                    'exception'  => $e,
                    'pbj_schema' => $searchRequest->schema()->getId()->toString(),
                    'pbj'        => $searchRequest->toArray(),
                ]
            );
        }

        return null;
    }
}
