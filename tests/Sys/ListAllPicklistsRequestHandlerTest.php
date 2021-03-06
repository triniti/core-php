<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\Sys\Node\PicklistV1;
use Acme\Schemas\Sys\Request\ListAllPicklistsRequestV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Schemas\Sys\PicklistId;
use Triniti\Sys\ListAllPicklistsRequestHandler;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;
use Triniti\Tests\MockSearchNodesRequestHandler;

final class ListAllPicklistsRequestHandlerTest extends AbstractPbjxTest
{
    private InMemoryNcr $ncr;

    protected function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
    }

    public function testWithResultsExpected()
    {
        $node1 = $this->createPicklist('super-picklist');
        $node2 = $this->createPicklist('another-picklist');

        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$node1, $node2]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:sys:request:search-picklists-request'),
            new MockSearchNodesRequestHandler($ncrSearch)
        );

        $handler = new ListAllPicklistsRequestHandler($this->ncr);
        $response = $handler->handleRequest(ListAllPicklistsRequestV1::create(), $this->pbjx);

        $picklists = $response->get('picklists');

        $this->assertCount(2, $picklists);
        $this->assertTrue(NodeRef::fromNode($node1)->equals($picklists[0]));
        $this->assertTrue(NodeRef::fromNode($node2)->equals($picklists[1]));
    }

    public function testNoResultsExpected()
    {
        $handler = new ListAllPicklistsRequestHandler($this->ncr);
        $response = $handler->handleRequest(ListAllPicklistsRequestV1::create(), $this->pbjx);
        $this->assertNull($response->get('picklists'));
    }

    private function createPicklist(string $id): Message
    {
        $node = PicklistV1::create()
            ->set('_id', PicklistId::fromString($id));
        $this->ncr->putNode($node);

        return $node;
    }
}
