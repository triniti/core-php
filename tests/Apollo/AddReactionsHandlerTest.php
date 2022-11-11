<?php

declare(strict_types=1);

namespace Triniti\Tests\Apollo;

use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Apollo\AddReactionsHandler;
use Triniti\Schemas\Apollo\Command\AddReactionsV1;
use Triniti\Schemas\Apollo\Event\ReactionsAddedV1;
use Triniti\Tests\AbstractPbjxTest;

final class AddReactionsHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $node = ArticleV1::create()->set('title', 'test-article');
        $nodeRef = NodeRef::fromNode($node);
        $expectedId = $node->get('_id');
        $command = AddReactionsV1::create()
            ->set('node_ref', $nodeRef)
            ->addToSet('reactions', ['wtf']);
        $handler = new AddReactionsHandler();
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->eventStore->pipeAllEvents() as $yield) {
            $event = $yield[0];
            $streamId = explode(':', $yield[1]->toString());
            array_pop($streamId);
            $streamId = implode(':', $streamId);

            $this->assertInstanceOf(ReactionsAddedV1::class, $event);
            $this->assertSame($event->get('node_ref'), $nodeRef);
            $this->assertSame($event->get('reactions'), ['wtf']);
            $this->assertSame(StreamId::fromString("acme:article.reactions:{$expectedId}")->toString(), $streamId);
        }
    }
}
