<?php
declare(strict_types=1);

namespace Triniti\Sys\Validator;

use Gdbots\Pbj\Assertion;
use Gdbots\Pbj\Exception\AssertionFailed;
use Gdbots\Pbjx\DependencyInjection\PbjxValidator;
use Gdbots\Pbjx\Event\PbjxEvent;
use Gdbots\Pbjx\EventSubscriber;
use Triniti\Schemas\Sys\Mixin\Redirect\Redirect;
use Triniti\Schemas\Sys\Mixin\Redirect\RedirectV1Mixin;
use Triniti\Schemas\Sys\RedirectId;
use Triniti\Sys\Exception\RedirectLoopProblem;

class RedirectValidator implements EventSubscriber, PbjxValidator
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        $curie = RedirectV1Mixin::findOne()->getCurie();
        $prefix = "{$curie->getVendor()}:{$curie->getPackage()}:command:";
        return [
            "{$prefix}create-redirect.validate" => 'validateCreateRedirect',
            "{$prefix}update-redirect.validate" => 'validateUpdateRedirect',
        ];
    }

    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateCreateRedirect(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();
        Assertion::true($command->has('node'), 'Field "node" is required.', 'node');
        $this->validateRedirect($command->get('node'));
    }

    /**
     * @param PbjxEvent $pbjxEvent
     */
    public function validateUpdateRedirect(PbjxEvent $pbjxEvent): void
    {
        $command = $pbjxEvent->getMessage();
        Assertion::true($command->has('new_node'), 'Field "new_node" is required.', 'new_node');
        $this->validateRedirect($command->get('new_node'));
    }

    /**
     * @param Redirect $redirect
     *
     * @throws AssertionFailed
     * @throws RedirectLoopProblem
     */
    protected function validateRedirect(Redirect $redirect): void
    {
        Assertion::true($redirect->has('redirect_to'), 'Field "redirect_to" is required.', 'node.redirect_to');

        /** @var RedirectId $id */
        $id = $redirect->get('_id');
        $uri = $id->toUri();
        Assertion::startsWith($uri, '/', 'Request URI must start with "/".');

        $uri = strtolower(trim($uri, '/'));
        $redirectTo = strtolower(trim($redirect->get('redirect_to', ''), '/'));

        if ($uri === $redirectTo) {
            throw new RedirectLoopProblem();
        }
    }
}
