<?php
declare(strict_types=1);

namespace Triniti\Tests\Boost;

use Acme\Schemas\Boost\Node\SponsorV1;
use Acme\Schemas\Boost\Request\SearchSponsorsRequestV1;
use Triniti\Boost\SearchSponsorsRequestHandler;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;

final class SearchSponsorsRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequest(): void
    {
        $node = SponsorV1::create()->set('title', 'test-sponsor');
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$node]);
        $request = SearchSponsorsRequestV1::create();
        $handler = new SearchSponsorsRequestHandler($ncrSearch);
        $this->locator->registerRequestHandler(SearchSponsorsRequestV1::schema()->getCurie(), $handler);
        $response = $handler->handleRequest($request, $this->pbjx);
        $this->assertTrue(in_array($node, $response->get('nodes', [])));
    }
}
