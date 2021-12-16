<?php
declare(strict_types=1);

namespace Triniti\Ncr;

use Gdbots\Ncr\GetNodeRequestHandler as BaseGetNodeRequestHandler;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;

/**
 * @deprecated will be removed in 4.x
 */
class GetNodeRequestHandler extends BaseGetNodeRequestHandler
{
    public static function handlesCuries(): array
    {
        $vendor = MessageResolver::getDefaultVendor();
        $curies = array_flip(MessageResolver::findAllUsingMixin('gdbots:ncr:mixin:get-node-request:v1', false));

        unset($curies['gdbots:ncr:request:get-node-request']);
        unset($curies['gdbots:iam:request:get-user-request']);
        unset($curies["{$vendor}:iam:request:get-user-request"]);
        unset($curies['triniti:sys:request:get-redirect-request']);
        unset($curies["{$vendor}:sys:request:get-redirect-request"]);

        return array_keys($curies);
    }

    protected function createGetNodeResponse(Message $request, Pbjx $pbjx): Message
    {
        $curie = str_replace('-request', '-response', $request::schema()->getCurie()->toString());
        return MessageResolver::resolveCurie($curie)::create();
    }
}
