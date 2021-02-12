<?php
declare(strict_types=1);

namespace Triniti\Tests;

use Acme\Schemas\Canvas\Request\SearchPagesResponseV1;
use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\ParsedQuery;
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
