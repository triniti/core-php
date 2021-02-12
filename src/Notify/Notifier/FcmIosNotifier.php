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
        $payload['content_available'] = true;
        $payload['mutable_content'] = true;

        if (!isset($payload['notification'])) {
            $payload['notification'] = [];
        }
        $payload['notification']['click_action'] = 'COMMENT_SNOOZE';
        $payload['data'] = [
            'notification_ref' => NodeRef::fromNode($notification)->toString(),
        ];

        if (null !== $content) {
            $contentRef = NodeRef::fromNode($content);
            $payload['data']['guid'] = $contentRef->getId();
            $payload['data']['node_ref'] = $contentRef->toString();
            $payload['data']['type'] = "{$contentRef->getVendor()}#{$contentRef->getLabel()}";
        }

        return $payload;
    }
}
