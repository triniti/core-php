<?php
declare(strict_types=1);

namespace Triniti\Tests\Apollo;

use Acme\Schemas\Apollo\Command\CastVoteV1;
use Acme\Schemas\Apollo\Node\PollV1;
use Acme\Schemas\Apollo\PollAnswerV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Schemas\Pbjx\StreamId;
use Triniti\Apollo\CastVoteHandler;
use Triniti\Schemas\Apollo\Event\VoteCastedV1;
use Triniti\Tests\AbstractPbjxTest;

final class CastVoteHandlerTest extends AbstractPbjxTest
{
    public function testHandleCommand(): void
    {
        $node = PollV1::create()->set('title', 'test-poll');
        $nodeRef = NodeRef::fromNode($node);
        $expectedId = $node->get('_id');
        $answer = PollAnswerV1::create();
        $answerId = $answer->get('_id');
        $command = CastVoteV1::create()
            ->set('poll_ref', $nodeRef)
            ->set('answer_id', $answerId);
        $handler = new CastVoteHandler();
        $handler->handleCommand($command, $this->pbjx);

        foreach ($this->eventStore->pipeAllEvents() as $yield) {
            $event = $yield[0];
            $streamId = explode(':', $yield[1]->toString());
            array_pop($streamId);
            $streamId = implode(':', $streamId);

            $this->assertInstanceOf(VoteCastedV1::class, $event);
            $this->assertSame($event->get('poll_ref'), $nodeRef);
            $this->assertSame($event->get('answer_id'), $answerId);
            $this->assertSame(StreamId::fromString("acme:poll.votes:{$expectedId}")->toString(), $streamId);
        }
    }
}
