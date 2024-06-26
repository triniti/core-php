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
use Triniti\Notify\Exception\UnableToFetchAccessToken;
use Triniti\Notify\Notifier;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Sys\Flags;

abstract class AbstractFcmNotifier implements Notifier
{
    const API_ENDPOINT = 'https://fcm.googleapis.com';
    const DISABLED_FLAG_NAME = 'fcm_notifier_disabled';

    protected Flags $flags;
    protected ?GuzzleClient $guzzleClient = null;
    protected Key $key;
    protected string $accessToken = '';
    protected array $authConfig = [];

    public function __construct(Flags $flags, Key $key)
    {
        $this->flags = $flags;
        $this->key = $key;
    }

    public function send(Message $notification, Message $app, ?Message $content = null): Message
    {
        if ($this->flags->getBoolean(static::DISABLED_FLAG_NAME)) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::CANCELLED->value)
                ->set('error_name', 'FcmNotifierDisabled')
                ->set('error_message', 'Flag [' . static::DISABLED_FLAG_NAME . '] is true');
        }

        try {
            $this->guzzleClient = null;
            $this->validate($notification, $app);
            $this->authConfig = $this->parseAuthConfig($app);
            $payload = $this->buildPayload($notification, $app, $content);
            $this->accessToken = $this->fetchAccessToken();
            $result = $this->sendNotification($payload);
            $response = $result['response'] ?? [];
            $result = NotifierResultV1::fromArray($result);

            if (isset($response['name'])) {
                preg_match('/.*\/(\d+)/', $response['name'], $matches);
                $result->addToMap('tags', 'fcm_message_id', $matches[1] ?? '');
            }
        } catch (\Throwable $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : Code::UNKNOWN->value;

            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', $code)
                ->set('error_name', ClassUtil::getShortName($e))
                ->set('error_message', substr($e->getMessage(), 0, 2048));
        }

        return $result;
    }

    protected function validate(Message $notification, Message $app): void
    {
        if (!$app->has('fcm_auth_config')) {
            throw new RequiredFieldNotSet('[fcm_auth_config] is required');
        }
    }

    /**
     * @link https://firebase.google.com/docs/cloud-messaging/concept-options
     *
     * @param Message $notification
     * @param Message $app
     * @param Message $content
     *
     * @return array
     */
    protected function buildPayload(Message $notification, Message $app, ?Message $content): array
    {
        $title = null !== $content ? $content->get('title') : $notification->get('title');
        $payload = [
            'notification' => [
                'body' => $notification->get('body', $title),
            ],
            'fcm_options'  => [
                'analytics_label' => $this->generateAnalyticsLabel($notification, $app),
            ],
        ];

        if ($notification->has('body') && $notification->get('body') !== $title) {
            $payload['notification']['title'] = $title;
        }

        $topics = $notification->get('fcm_topics');
        if (empty($topics)) {
            return $payload;
        }

        if (count($topics) === 1) {
            $payload['topic'] = $topics[0];
            return $payload;
        }

        $payload['condition'] = implode(' || ', array_map(
            function ($topic) {
                return "'{$topic}' in topics";
            }, $topics
        ));

        return $payload;
    }

    /**
     * @link https://firebase.google.com/docs/cloud-messaging/send-message
     *
     * @param array $payload
     *
     * @return array
     */
    protected function sendNotification(array $payload): array
    {
        try {
            $response = $this->getGuzzleClient()->post("/v1/projects/{$this->authConfig['project_id']}/messages:send", [RequestOptions::JSON => $payload]);
            $httpCode = HttpCode::from($response->getStatusCode());
            $content = (string)$response->getBody()->getContents();

            return [
                'ok'           => HttpCode::HTTP_OK === $httpCode || HttpCode::HTTP_CREATED === $httpCode,
                'code'         => StatusCodeUtil::httpToVendor($httpCode)->value,
                'http_code'    => $httpCode->value,
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
            $httpCode = HttpCode::from($exception->getResponse()->getStatusCode());
            $response = (string)($exception->getResponse()->getBody()->getContents() ?: '{}');
        } else {
            $httpCode = HttpCode::HTTP_INTERNAL_SERVER_ERROR;
            $response = '{}';
        }

        return [
            'ok'            => false,
            'code'          => StatusCodeUtil::httpToVendor($httpCode)->value,
            'http_code'     => $httpCode->value,
            'raw_response'  => $response,
            'error_name'    => ClassUtil::getShortName($exception),
            'error_message' => substr($exception->getMessage(), 0, 2048),
        ];
    }

    protected function generateAnalyticsLabel(Message $notification, Message $app): string
    {
        return $notification->get('_id')->toString();
    }

    protected function fetchAccessToken(): string
    {
        $client = new \Google_Client();
        $client->setAuthConfig($this->authConfig);
        $client->addScope(\Google_Service_FirebaseCloudMessaging::FIREBASE_MESSAGING);
        $tokens = $client->fetchAccessTokenWithAssertion();

        if (!isset($tokens['access_token'])) {
            throw new UnableToFetchAccessToken();
        }

        return $tokens['access_token'];
    }

    protected function parseAuthConfig(Message $app): array
    {
        return json_decode(base64_decode(Crypto::decrypt($app->get('fcm_auth_config'), $this->key)), true);
    }

    /**
     * @link https://firebase.google.com/docs/cloud-messaging/auth-server
     *
     * @return GuzzleClient
     */
    protected function getGuzzleClient(): GuzzleClient
    {
        if (null === $this->guzzleClient) {
            $stack = HandlerStack::create();
            $stack->push(
                Middleware::mapRequest(
                    function (RequestInterface $request) {
                        return $request->withHeader('Authorization', "Bearer {$this->accessToken}");
                    }
                )
            );

            $this->guzzleClient = new GuzzleClient([
                'base_uri' => self::API_ENDPOINT,
                'handler'  => $stack,
                'timeout'  => 5,
            ]);
        }

        return $this->guzzleClient;
    }
}
