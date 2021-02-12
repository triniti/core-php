<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Ncr\GetNodeRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\UriTemplate\UriTemplateService;
use Throwable;
use Triniti\Schemas\Sys\Request\GetRedirectResponseV1;

class GetRedirectRequestHandler extends GetNodeRequestHandler
{
    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 3.x
        $curies = MessageResolver::findAllUsingMixin('triniti:sys:mixin:get-redirect-request:v1', false);
        $curies[] = 'triniti:sys:request:get-redirect-request';
        return $curies;
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        $response = parent::handleRequest($request, $pbjx);
        if (!$response->has('node')) {
            return $response;
        }

        /** @var Message $redirect */
        $redirect = $response->get('node');

        if (!$redirect->get('is_vanity')) {
            return $response;
        }

        foreach (MessageResolver::findAllUsingMixin('triniti:sys:mixin:vanity-urlable:v1', false) as $curie) {
            $resolvesTo = $this->resolveVanityUrl(SchemaCurie::fromString($curie), $redirect, $request, $pbjx);
            if (null !== $resolvesTo) {
                $response->set('resolves_to', $resolvesTo);
                break;
            }
        }

        return $response;
    }

    protected function resolveVanityUrl(SchemaCurie $schemaCurie, Message $redirect, Message $request, Pbjx $pbjx): ?string
    {
        // todo: use an inflector
        switch ($schemaCurie->getMessage()) {
            case 'category':
                $message = 'search-categories-request';
                break;

            case 'gallery':
                $message = 'search-galleries-request';
                break;

            case 'person':
                $message = 'search-people-request';
                break;

            default:
                $message = "search-{$schemaCurie->getMessage()}s-request";
                break;
        }

        $searchCurie = implode(':', [$schemaCurie->getVendor(), $schemaCurie->getPackage(), 'request', $message]);

        try {
            $searchRequest = MessageResolver::resolveCurie($searchCurie)::fromArray([
                'count'  => 1,
                'q'      => '+redirect_ref:' . NodeRef::fromNode($redirect)->toString(),
                'sort'   => 'published-at-desc',
                'status' => NodeStatus::PUBLISHED,
            ]);

            $searchResponse = $pbjx->copyContext($request, $searchRequest)->request($searchRequest);

            if ($searchResponse->has('nodes')) {
                $node = $searchResponse->getFromListAt('nodes', 0);
                return UriTemplateService::expand(
                    "{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()
                );
            }
        } catch (Throwable $e) {
        }

        return null;
    }

    protected function createGetNodeResponse(Message $request, Pbjx $pbjx): Message
    {
        return GetRedirectResponseV1::create();
    }
}
