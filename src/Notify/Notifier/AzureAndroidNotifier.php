<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;

class AzureAndroidNotifier extends AbstractAzureNotifier
{
    const DISABLED_FLAG_NAME = 'azure_android_notifier_disabled';
    const FORMAT = 'gcm';

    /**
     * @link https://developers.google.com/cloud-messaging/concept-options#notifications_and_data_messages
     *
     * {@inheritdoc}
     */
    protected function buildPayload(Message $notification, Message $app, ?Message $content = null): array
    {
        $msg = null !== $content ? $content->get('title') : $notification->get('title');
        $payload = [
            'notification' => [
                'title' => $notification->get('body', $msg),
            ],
            'data'         => [
                'msg'              => $notification->get('body', $msg),
                'notification_ref' => NodeRef::fromNode($notification)->toString(),
            ],
        ];

        if (null !== $content) {
            $contentRef = NodeRef::fromNode($content);
            $payload['data']['node_ref'] = $contentRef->toString();
            // ones below are for legacy apps
            $payload['data']['type'] = "{$contentRef->getVendor()}#{$contentRef->getLabel()}";
            $payload['data']['guid'] = $contentRef->getId();
        }

        return $payload;
    }
}

