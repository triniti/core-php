<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Node\BlogrollWidgetV1;
use Acme\Schemas\Curator\Node\PromotionV1;
use Acme\Schemas\Curator\Request\RenderPromotionRequestV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Curator\RenderPromotionRequestHandler;
use Triniti\Schemas\Common\RenderContextV1;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;
use Triniti\Tests\MockSearchNodesRequestHandler;

final class RenderPromotionRequestHandlerTest extends AbstractPbjxTest
{
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
    }

    public function testHandleRequest(): void
    {
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:curator:request:render-widget-request'),
            new MockRenderWidgetRequestHandler()
        );

        $widget = BlogrollWidgetV1::create();
        $promotion = PromotionV1::create()
            ->addToList('widget_refs', [$widget->generateNodeRef()]);
        $this->ncr->putNode($promotion);
        $request = RenderPromotionRequestV1::create()
            ->set('promotion_ref', $promotion->generateNodeRef())
            ->set('context', RenderContextV1::create());

        $handler = new RenderPromotionRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $this->assertSame(1, count($response->get('widgets')));
    }

    public function testHandleRequestNoPromotionRef(): void
    {
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:curator:request:render-widget-request'),
            new MockRenderWidgetRequestHandler()
        );

        $ncrSearch = new MockNcrSearch();
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:curator:request:search-promotions-request'),
            new MockSearchNodesRequestHandler($ncrSearch)
        );

        $widget = BlogrollWidgetV1::create();
        $promotion = PromotionV1::create()
            ->set('slot', 'cool-slot')
            ->addToList('widget_refs', [$widget->generateNodeRef()]);
        $ncrSearch->indexNodes([$promotion]);
        $this->ncr->putNode($promotion);
        $request = RenderPromotionRequestV1::create()
            ->set('slot', 'cool-slot')
            ->set('context', RenderContextV1::create());

        $handler = new RenderPromotionRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $this->assertSame(1, count($response->get('widgets')));
    }

    public function testHandleRequestDeletedPromotion(): void
    {
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:curator:request:render-widget-request'),
            new MockRenderWidgetRequestHandler()
        );

        $widget = BlogrollWidgetV1::create();
        $promotion = PromotionV1::create()
            ->set('status', NodeStatus::DELETED())
            ->addToList('widget_refs', [$widget->generateNodeRef()]);
        $this->ncr->putNode($promotion);
        $request = RenderPromotionRequestV1::create()
            ->set('promotion_ref', $promotion->generateNodeRef());

        $handler = new RenderPromotionRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        $this->assertEmpty($response->get('widgets', []));
    }
}
