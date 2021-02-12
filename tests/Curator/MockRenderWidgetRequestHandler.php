<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Gdbots\Pbj\Message;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Pbjx\RequestHandler;
use Triniti\Schemas\Curator\Request\RenderWidgetResponseV1;

final class MockRenderWidgetRequestHandler implements RequestHandler
{
    public static function handlesCuries(): array
    {
        return [];
    }

    public function handleRequest(Message $request, Pbjx $pbjx): Message
    {
        return RenderWidgetResponseV1::create();
    }
}
