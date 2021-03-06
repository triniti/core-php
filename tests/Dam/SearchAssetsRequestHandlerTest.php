<?php
declare(strict_types=1);

namespace Triniti\Tests\Dam;

use Acme\Schemas\Dam\Node\ImageAssetV1;
use Acme\Schemas\Dam\Request\SearchAssetsRequestV1;
use Triniti\Dam\SearchAssetsRequestHandler;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Tests\AbstractPbjxTest;
use Triniti\Tests\MockNcrSearch;

final class SearchAssetsRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequest(): void
    {
        $node = ImageAssetV1::fromArray([
            '_id'       => AssetId::create('image', 'jpg'),
            'mime_type' => 'image/jpeg',
        ]);
        $ncrSearch = new MockNcrSearch();
        $ncrSearch->indexNodes([$node]);
        $request = SearchAssetsRequestV1::create();
        $handler = new SearchAssetsRequestHandler($ncrSearch);
        $this->locator->registerRequestHandler(SearchAssetsRequestV1::schema()->getCurie(), $handler);
        $response = $handler->handleRequest($request, $this->pbjx);
        $this->assertTrue(in_array($node, $response->get('nodes', [])));
    }
}
