<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\Sys\Node\FlagsetV1;
use Acme\Schemas\Sys\Request\ListAllFlagsetsRequestV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Triniti\Schemas\Sys\FlagsetId;
use Triniti\Sys\ListAllFlagsetsRequestHandler;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;
use Triniti\Tests\MockSearchNodesRequestHandler;

final class ListAllFlagsetsRequestHandlerTest extends AbstractPbjxTest
{
    private InMemoryNcr $ncr;

    protected function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
    }

    public function testWithResultsExpected()
    {
        $node1 = $this->createFlagset('super-flagset');
        $node2 = $this->createFlagset('another-flagset');

        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$node1, $node2]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:sys:request:search-flagsets-request'),
            new MockSearchNodesRequestHandler($ncrSearch),
        );

        $handler = new ListAllFlagsetsRequestHandler($this->ncr);
        $response = $handler->handleRequest(ListAllFlagsetsRequestV1::create(), $this->pbjx);

        $flagsets = $response->get('flagsets');

        $this->assertCount(2, $flagsets);
        $this->assertTrue(NodeRef::fromNode($node1)->equals($flagsets[0]));
        $this->assertTrue(NodeRef::fromNode($node2)->equals($flagsets[1]));
    }

    public function testNoResultsExpected()
    {
        $handler = new ListAllFlagsetsRequestHandler($this->ncr);
        $response = $handler->handleRequest(ListAllFlagsetsRequestV1::create(), $this->pbjx);
        $this->assertNull($response->get('flagsets'));
    }

    private function createFlagset(string $id): Message
    {
        $node = FlagsetV1::create()
            ->set('_id', FlagsetId::fromString($id));
        $this->ncr->putNode($node);

        return $node;
    }
}
