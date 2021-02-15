<?php
declare(strict_types=1);

namespace Triniti\Tests;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\ParsedQuery;
use Triniti\Schemas\Canvas\Request\SearchPagesResponseV1;
use Triniti\Schemas\Curator\Request\SearchPromotionsResponseV1;
use Triniti\Schemas\Curator\Request\SearchTeasersResponseV1;
use Triniti\Schemas\Dam\Request\SearchAssetsResponseV1;
use Triniti\Schemas\News\Request\SearchArticlesResponseV1;
use Triniti\Schemas\Sys\Request\SearchFlagsetsResponseV1;
use Triniti\Schemas\Sys\Request\SearchPicklistsResponseV1;

class MockSearchNodesRequestHandler extends AbstractSearchNodesRequestHandler
{
    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        switch ($request::schema()->getQName()->getMessage()) {
            case 'search-pages-request':
                $response = SearchPagesResponseV1::create();
                break;
            case 'search-flagsets-request':
                $response = SearchFlagsetsResponseV1::create();
                break;
            case 'search-picklists-request':
                $response = SearchPicklistsResponseV1::create();
                break;
            case 'search-articles-request':
                $response = SearchArticlesResponseV1::create();
                break;
            case 'search-promotions-request':
                $response = SearchPromotionsResponseV1::create();
                break;
            case 'search-assets-request':
                $response = SearchAssetsResponseV1::create();
                break;
            case 'search-teasers-request':
                $response = SearchTeasersResponseV1::create();
                break;
            default:
                var_dump('need to update switch in MockSearchNodesRequestHandler');
                var_dump($request::schema()->getQName()->getMessage());
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
