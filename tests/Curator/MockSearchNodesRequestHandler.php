<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Request\SearchPromotionsResponseV1;
use Acme\Schemas\Curator\Request\SearchTeasersResponseV1;
use Acme\Schemas\Dam\Request\SearchAssetsResponseV1;
use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\ParsedQuery;

final class MockSearchNodesRequestHandler extends AbstractSearchNodesRequestHandler
{
    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        switch ($request::schema()->getCurie()->getMessage()){
            case 'search-teasers-request':
                $response = SearchTeasersResponseV1::create();
                break;
            case 'search-assets-request':
                $response = SearchAssetsResponseV1::create();
                break;
            case 'search-promotions-request':
                $response = SearchPromotionsResponseV1::create();
                break;
            default:
                var_dump('unhandled case in MockSearchNodesRequestHandler:');
                var_dump($request::schema()->getCurie()->getMessage());
                die();
        }
        $this->ncrSearch->searchNodes($request, new ParsedQuery(), $response);
        return $response;
    }

    public static function handlesCuries(): array
    {
        return [];
    }
}
