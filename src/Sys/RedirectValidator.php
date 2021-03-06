<?php
declare(strict_types=1);

namespace Triniti\Sys;

use Gdbots\Pbj\Assertion;
use Gdbots\Pbjx\DependencyInjection\PbjxValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Triniti\Schemas\Sys\RedirectId;
use Triniti\Sys\Exception\RedirectLoopProblem;

class RedirectValidator implements EventSubscriber, PbjxValidator
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:sys:mixin:redirect.validate' => 'validate',
        ];
    }

    public function validate(PbjxEvent $pbjxEvent): void
    {
        $node = $pbjxEvent->getMessage();
        Assertion::true($node->has('redirect_to'), 'Field "redirect_to" is required.', 'node.redirect_to');

        /** @var RedirectId $id */
        $id = $node->get('_id');
        $uri = $id->toUri();
        Assertion::startsWith($uri, '/', 'Request URI must start with "/".');

        $uri = strtolower(trim($uri, '/'));
        $redirectTo = strtolower(trim($node->get('redirect_to', ''), '/'));

        if ($uri === $redirectTo) {
            throw new RedirectLoopProblem();
        }
    }
}
