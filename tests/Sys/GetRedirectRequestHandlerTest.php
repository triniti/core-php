<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\Canvas\Node\PageV1;
use Acme\Schemas\Sys\Node\RedirectV1;
use Acme\Schemas\Sys\Request\GetRedirectRequestV1;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\UriTemplate\UriTemplateService;
use Triniti\Schemas\Sys\RedirectId;
use Triniti\Sys\GetRedirectRequestHandler;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;
use Triniti\Tests\MockSearchNodesRequestHandler;

final class GetRedirectRequestHandlerTest extends AbstractPbjxTest
{
    protected MockNcrSearch $ncrSearch;
    private InMemoryNcr $ncr;

    protected function setup(): void
    {
        parent::setup();
        $this->ncrSearch = new MockNcrSearch();
        $this->ncr = new InMemoryNcr();
    }

    public function testGetByNodeRefThatExists(): void
    {
        $testRequestUri = '/jobs';
        $testRedirectUri = 'something/test';

        $node = RedirectV1::fromArray([
            '_id'         => RedirectId::fromUri($testRequestUri),
            'redirect_to' => $testRedirectUri,
            'is_vanity'   => true,
        ]);
        $this->ncr->putNode($node);

        $request = GetRedirectRequestV1::create()->set('node_ref', NodeRef::fromNode($node));
        $handler = new GetRedirectRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        /** @var Message $actualNode */
        $actualNode = $response->get('node');
        $this->assertTrue($actualNode->equals($node));
    }

    public function testResolveVanityUrl(): void
    {
        $testRequestUri = '/jobs';
        $testRedirectUri = 'something/test';

        $node = RedirectV1::fromArray([
            '_id'         => RedirectId::fromUri($testRequestUri),
            'redirect_to' => $testRedirectUri,
            'is_vanity'   => true,
        ]);
        $this->ncr->putNode($node);

        $ncrSearch = new MockNcrSearch();
        // could beef up this test to require the redirect_ref on the page
        $page = PageV1::create()->set('slug', 'foo-bar');
        $ncrSearch->indexNodes([$page]);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('triniti:canvas:request:search-pages-request'),
            new MockSearchNodesRequestHandler($ncrSearch)
        );

        UriTemplateService::registerTemplate('acme:page.canonical', 'https://www.test.com/pages/{slug}/');

        $request = GetRedirectRequestV1::create()->set('node_ref', NodeRef::fromNode($node));
        $handler = new GetRedirectRequestHandler($this->ncr);
        $response = $handler->handleRequest($request, $this->pbjx);
        /** @var Message $actualNode */
        $actualNode = $response->get('node');
        $this->assertTrue($actualNode->equals($node));

        $this->assertSame('https://www.test.com/pages/foo-bar/', $response->get('resolves_to'));
    }

    public function testGetByNodeRefThatDoesNotExists(): void
    {
        $nodeRef = NodeRef::fromString('triniti:sys:idontexist');
        $request = GetRedirectRequestV1::create()->set('node_ref', $nodeRef);
        $handler = new GetRedirectRequestHandler($this->ncr);
        $this->expectException(NodeNotFound::class);
        $handler->handleRequest($request, $this->pbjx);
        $this->locator->registerRequestHandler(
            SchemaCurie::fromString('acme:canvas:request:search-pages-request'),
            new MockSearchNodesRequestHandler(new MockNcrSearch())
        );
    }

    public function testGetByNothing(): void
    {
        $request = GetRedirectRequestV1::create();
        $handler = new GetRedirectRequestHandler($this->ncr);
        $this->expectException(NodeNotFound::class);
        $handler->handleRequest($request, $this->pbjx);
    }
}
