<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;

class AzureIosNotifier extends AbstractAzureNotifier
{
    const DISABLED_FLAG_NAME = 'azure_ios_notifier_disabled';
    const FORMAT = 'apple';

    /**
     * @link https://developer.apple.com/library/archive/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/CreatingtheNotificationPayload.html
     *
     * {@inheritdoc}
     */
    protected function buildPayload(Message $notification, Message $app, ?Message $content = null): array
    {
        $alert = null !== $content ? $content->get('title') : $notification->get('title');
        $payload = [
            'notification'     => [
                'title' => $notification->get('body', $alert),
            ],
            'aps'              => [
                'alert'           => $notification->get('body', $alert),
                'category'        => 'COMMENT_SNOOZE',
                'mutable-content' => 1,
            ],
            'notification_ref' => NodeRef::fromNode($notification)->toString(),
        ];

        if (null !== $content) {
            $contentRef = NodeRef::fromNode($content);
            $payload['node_ref'] = $contentRef->toString();
            // ones below are for legacy apps
            $payload['type'] = "{$contentRef->getVendor()}#{$contentRef->getLabel()}";
            $payload['guid'] = $contentRef->getId();
        }

        return $payload;
    }
}
