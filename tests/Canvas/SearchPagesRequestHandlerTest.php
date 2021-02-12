<?php
declare(strict_types=1);

namespace Triniti\Tests\Canvas;

use Acme\Schemas\Canvas\Node\PageV1;
use Acme\Schemas\Canvas\Request\SearchPagesRequestV1;
use Triniti\Canvas\SearchPagesRequestHandler;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;

final class SearchPagesRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $node = PageV1::create()->set('title', 'test-page');
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$node]);
        $handler = new SearchPagesRequestHandler($ncrSearch);
        $this->locator->registerRequestHandler(SearchPagesRequestV1::schema()->getCurie(), $handler);
        $response = $handler->handleRequest(SearchPagesRequestV1::create(), $this->pbjx);
        $this->assertTrue(in_array($node, $response->get('nodes', [])));
    }
}
