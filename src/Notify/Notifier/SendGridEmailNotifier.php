<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\Util\ClassUtil;
use Gdbots\Pbjx\Util\StatusCodeUtil;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Gdbots\Schemas\Pbjx\Enum\HttpCode;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Triniti\Notify\Exception\RequiredFieldNotSet;
use Triniti\Notify\Notifier;
use Triniti\Schemas\Common\RenderContextV1;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Sys\Flags;
use Twig\Environment;

class SendGridEmailNotifier implements Notifier
{
    const ENDPOINT = 'https://api.sendgrid.com/v3/';

    protected Flags $flags;
    protected Key $key;
    protected Environment $twig;
    protected ?GuzzleClient $guzzleClient = null;
    protected string $apiKey;

    public function __construct(Flags $flags, Key $key, Environment $twig)
    {
        $this->flags = $flags;
        $this->key = $key;
        $this->twig = $twig;
    }

    public function send(Message $notification, Message $app, ?Message $content = null): Message
    {
        if (null === $content) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::INVALID_ARGUMENT)
                ->set('error_name', 'NullContent')
                ->set('error_message', 'Content cannot be null');
        }

        if ($this->flags->getBoolean('sendgrid_email_notifier_disabled')) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::CANCELLED)
                ->set('error_name', 'SendGridEmailNotifierDisabled')
                ->set('error_message', 'Flag [sendgrid_email_notifier_disabled] is true');
        }

        try {
            $this->guzzleClient = null;
            $this->validate($notification, $app);
            $this->apiKey = Crypto::decrypt($app->get('sendgrid_api_key'), $this->key);
            $campaign = $this->buildCampaign($notification, $app, $content);
            $campaignId = $this->createCampaign($campaign);
            $result = $this->sendCampaign($campaignId);
        } catch (\Throwable $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : Code::UNKNOWN;
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', $code)
                ->set('error_name', ClassUtil::getShortName($e))
                ->set('error_message', substr($e->getMessage(), 0, 2048));
        }

        $result = NotifierResultV1::fromArray($result);
        $result->addToMap('tags', 'sendgrid_campaign_id', (string)$campaignId);
        return $result;
    }

    /**
     * @param Message $notification
     * @param Message $app
     *
     * @throws RequiredFieldNotSet
     */
    protected function validate(Message $notification, Message $app): void
    {
        if (!$notification->has('lists')) {
            throw new RequiredFieldNotSet('SendGrid [lists] are required');
        }

        if (!$notification->has('sender')) {
            throw new RequiredFieldNotSet('SendGrid [sender] is required');
        }

        if (!$app->has('sendgrid_suppression_group_id')) {
            throw new RequiredFieldNotSet('SendGrid [sendgrid_suppression_group_id] is required');
        }
    }

    protected function buildCampaign(Message $notification, Message $app, Message $content): array
    {
        $context = RenderContextV1::fromArray([
            'cache_enabled' => false,
            'platform'      => 'email',
        ]);

        $context->set('container', $content);

        $template = $notification->get('template', 'default');
        $name = strtolower(str_replace(
            '-',
            '_',
            "@email_notifications/{$template}.html.twig"
        ));

        if ('default' !== $template && !$this->twig->getLoader()->exists($name)) {
            $template = str_replace('-', '_', $template);
            $name = str_replace("/{$template}.html", '/default.html', $name);
        }

        $html = $this->twig->render($name, [
            'content'          => $content,
            'context'          => $context,
            'notification'     => $notification,
            // not named "app" so it doesn't conflict with symfony "app" variable
            'notification_app' => $app,
        ]);

        $listIds = [];
        foreach ($notification->get('lists', []) as $list) {
            if ($app->isInMap('sendgrid_lists', $list)) {
                $listIds[] = $app->getFromMap('sendgrid_lists', $list);
            }
        }

        $subject = $notification->get('subject') ?: $notification->get('title');
        $subject = str_replace('[title]', $content->get('title'), $subject);

        return [
            'title'                => $notification->get('title') ?: $content->get('title'),
            'subject'              => $subject,
            'sender_id'            => $app->getFromMap('sendgrid_senders', $notification->get('sender')),
            'list_ids'             => $listIds,
            'suppression_group_id' => $app->get('sendgrid_suppression_group_id'),
            'html_content'         => $html,
        ];
    }

    /**
     * @link https://sendgrid.api-docs.io/v3.0/campaigns-api/create-a-campaign
     *
     * @param array $data
     *
     * @return int
     */
    protected function createCampaign(array $data): int
    {
        $response = $this->getGuzzleClient()->post('campaigns', [RequestOptions::JSON => $data]);
        $campaign = json_decode((string)$response->getBody()->getContents(), true);
        return (int)($campaign['id'] ?? 0);
    }

    /**
     * @link https://sendgrid.api-docs.io/v3.0/campaigns-api/schedule-a-campaign
     *
     * @param int $id
     *
     * @return array
     */
    protected function sendCampaign(int $id): array
    {
        try {
            $response = $this->getGuzzleClient()->post("campaigns/{$id}/schedules/now");
            $httpCode = $response->getStatusCode();
            $content = (string)$response->getBody()->getContents();

            return [
                'ok'           => HttpCode::HTTP_CREATED === $httpCode,
                'code'         => StatusCodeUtil::httpToVendor($httpCode),
                'http_code'    => $httpCode,
                'raw_response' => $content,
                'response'     => json_decode($content, true),
            ];
        } catch (\Throwable $e) {
            return $this->convertException($e);
        }
    }

    protected function convertException(\Throwable $exception): array
    {
        if ($exception instanceof RequestException) {
            $httpCode = $exception->getResponse()->getStatusCode();
            $response = (string)($exception->getResponse()->getBody()->getContents() ?: '{}');
        } else {
            $httpCode = HttpCode::HTTP_INTERNAL_SERVER_ERROR;
            $response = '{}';
        }

        return [
            'ok'            => false,
            'code'          => StatusCodeUtil::httpToVendor($httpCode),
            'http_code'     => $httpCode,
            'raw_response'  => $response,
            'error_name'    => ClassUtil::getShortName($exception),
            'error_message' => substr($exception->getMessage(), 0, 2048),
        ];
    }

    protected function getGuzzleClient(): GuzzleClient
    {
        if (null === $this->guzzleClient) {
            $stack = HandlerStack::create();
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
                return $request->withHeader('Authorization', sprintf('Bearer %s', trim($this->apiKey)));
            }));

            $this->guzzleClient = new GuzzleClient([
                'base_uri' => self::ENDPOINT,
                'handler'  => $stack,
            ]);
        }

        return $this->guzzleClient;
    }
}
