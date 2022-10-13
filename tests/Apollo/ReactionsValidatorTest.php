<?php
declare(strict_types=1);

namespace Triniti\Tests\Apollo;

use Acme\Schemas\Apollo\Node\ReactionsV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Apollo\ReactionsValidator;
use Triniti\Schemas\Apollo\Command\AddReactionsV1;
use Triniti\Tests\AbstractPbjxTest;

final class ReactionsValidatorTest extends AbstractPbjxTest
{
    protected function setup(): void
    {
        parent::setup();
    }

    public function testValidateReactions(): void
    {
        $reactions = ReactionsV1::create();
        $reactions->addToMap('reactions', '', null);

        $validator = new ReactionsValidator();
        $pbjxEvent = new PbjxEvent($reactions);
        $validator->validateReactions($pbjxEvent);
        $this->assertSame(['love' => 0, 'haha' => 0, 'wow' => 0, 'wtf' => 0, 'trash' => 0, 'sad' => 0], $reactions->get('reactions'));
    }

    public function testValidateAddReactions(): void
    {
        $command = AddReactionsV1::create();
        $command
            ->set('node_ref', NodeRef::fromString('acme:article:1234'))
            ->addToSet('reactions', ['wtf']);

        $validator = new ReactionsValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateAddReactions($pbjxEvent);

        // if it gets here it's a pass
        $this->assertTrue(true);
    }

    public function testValidateAddReactionsWithInvalidReactions(): void
    {
        $command = AddReactionsV1::create();
        $command
            ->set('node_ref', NodeRef::fromString('acme:article:1234'))
            ->addToSet('reactions', ['wtf', 'test']);

        $validator = new ReactionsValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateAddReactions($pbjxEvent);

        $this->assertSame(['wtf'], $command->get('reactions'));
    }

    public function testValidateAddReactionsWithInvalidNodeRef(): void
    {
        $this->expectExceptionMessage('Node does not support reactions.');
        $command = AddReactionsV1::create();
        $command
            ->set('node_ref', NodeRef::fromString('acme:ios-app:1234'))
            ->addToSet('reactions', ['wtf']);

        $validator = new ReactionsValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateAddReactions($pbjxEvent);
    }

    public function testValidateAddReactionsWithNoReactions(): void
    {
        $this->expectExceptionMessage('Field "reactions" is required.');
        $command = AddReactionsV1::create();
        $command
            ->set('node_ref', NodeRef::fromString('acme:article:1234'));

        $validator = new ReactionsValidator();
        $pbjxEvent = new PbjxEvent($command);
        $validator->validateAddReactions($pbjxEvent);
    }
}
