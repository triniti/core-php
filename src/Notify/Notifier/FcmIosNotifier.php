<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;

class FcmIosNotifier extends AbstractFcmNotifier
{
    const DISABLED_FLAG_NAME = 'fcm_ios_notifier_disabled';

    /**
     * @link https://firebase.google.com/docs/cloud-messaging/http-server-ref#send-downstream
     *
     * {@inheritdoc}
     */
    protected function buildPayload(Message $notification, Message $app, ?Message $content = null): array
    {
        $payload = parent::buildPayload($notification, $app, $content);
        // https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/generating_a_remote_notification?language=objc
        $payload['apns']['payload']['aps'] = ['content-available' => 1, 'mutable-content' => 1];

        if (!isset($payload['notification'])) {
            $payload['notification'] = [];
        }

        $payload['data'] = [
            'notification_ref' => NodeRef::fromNode($notification)->toString(),
        ];

        if (null !== $content) {
            $contentRef = NodeRef::fromNode($content);
            $payload['data']['guid'] = $contentRef->getId();
            $payload['data']['node_ref'] = $contentRef->toString();
            $payload['data']['type'] = "{$contentRef->getVendor()}#{$contentRef->getLabel()}";
        }

        return ['message' => $payload];
    }
}
