<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;

class FcmAndroidNotifier extends AbstractFcmNotifier
{
    const DISABLED_FLAG_NAME = 'fcm_android_notifier_disabled';

    protected function buildPayload(Message $notification, Message $app, ?Message $content = null): array
    {
        $payload = parent::buildPayload($notification, $app, $content);
        $payload['data'] = [
            'msg'              => $payload['notification']['body'],
            'notification_ref' => NodeRef::fromNode($notification)->toString(),
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

