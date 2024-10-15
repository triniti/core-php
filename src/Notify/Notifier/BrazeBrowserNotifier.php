<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Gdbots\Pbj\Message;
use Gdbots\UriTemplate\UriTemplateService;

class BrazeBrowserNotifier extends AbstractBrazeNotifier
{
    protected function buildPayload(Message $notification, Message $app, ?Message $content = null): array
    {
        $brazePayload = parent::buildPayload($notification, $app, $content);
        if (!isset($brazePayload['messages'])) {
            $brazePayload['messages'] = [];
        }

        $title = $content->get('title', $notification->get('title'));
        $body = $notification->get('body');
        $brazePayload['messages']['web_push'] = [
            'title' => $title,
            'alert' => $body,
            'require_interaction' => $notification->get('require_interaction'),
        ];

        if ($notification->has('braze_message_variation_id')) {
            $brazePayload['messages']['web_push']['message_variation_id'] = $notification->get('braze_message_variation_id');
        }

        $canonical = UriTemplateService::expand(
            "{$content::schema()->getQName()}.canonical",
            $content->getUriTemplateVars()
        );
        $brazePayload['messages']['web_push']['custom_uri'] = $canonical;

        return $brazePayload;
    }
}
