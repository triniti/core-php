<?php
declare(strict_types=1);

namespace Triniti\Tests\Apollo;

use Acme\Schemas\Apollo\Node\PollV1;
use Acme\Schemas\Apollo\Request\SearchPollsRequestV1;
use Triniti\Apollo\SearchPollsRequestHandler;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;

final class SearchPollsRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $node = PollV1::create()->set('title', 'test-poll');
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$node]);
        $request = SearchPollsRequestV1::create();
        $handler = new SearchPollsRequestHandler($ncrSearch);
        $this->locator->registerRequestHandler(SearchPollsRequestV1::schema()->getCurie(), $handler);
        $response = $handler->handleRequest($request, $this->pbjx);
        $this->assertTrue(in_array($node, $response->get('nodes', [])));
    }
}
