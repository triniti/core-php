<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\UriTemplate\UriTemplateService;

class FcmBrowserNotifier extends AbstractFcmNotifier
{
    const DISABLED_FLAG_NAME = 'fcm_browser_notifier_disabled';

    protected function buildPayload(Message $notification, Message $app, ?Message $content = null): array
    {
        $payload = parent::buildPayload($notification, $app, $content);
        $payload['data'] = [
            'notification_ref' => NodeRef::fromNode($notification)->toString(),
        ];

        if (null !== $content) {
            $payload['data']['node_ref'] = NodeRef::fromNode($content)->toString();
            $url = UriTemplateService::expand(
                "{$content::schema()->getQName()}.canonical",
                $content->getUriTemplateVars()
            );

            if (!empty($url)) {
                if (!isset($payload['notification'])) {
                    $payload['notification'] = [];
                }
                $payload['notification']['click_action'] = $url;
            }
        }

        return ['message' => $payload];
    }
}
