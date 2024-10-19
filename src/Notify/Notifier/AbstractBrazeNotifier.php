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
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Sys\Flags;

abstract class AbstractBrazeNotifier implements Notifier
{
    protected ?GuzzleClient $guzzleClient = null;
    protected ?string $apiKey = null;

    const string DISABLED_FLAG_NAME = 'braze_notifier_disabled';

    public function __construct(
        protected readonly Flags  $flags,
        protected readonly Key    $key,
        protected readonly string $apiEndpoint = 'https://rest.iad-01.braze.com',
    ) {
    }

    protected function getGuzzleClient(): GuzzleClient
    {
        if (null !== $this->guzzleClient) {
            return $this->guzzleClient;
        }

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::mapRequest(
                function (RequestInterface $request): RequestInterface {
                    return $request
                        ->withHeader('Authorization', "Bearer $this->apiKey")
                        ->withHeader('content-type', 'application/json');
                }
            )
        );

        $this->guzzleClient = new GuzzleClient([
            'base_uri' => $this->apiEndpoint,
            'handler'  => $stack,
            'timeout'  => 5,
        ]);

        return $this->guzzleClient;
    }

    protected function buildPayload(Message $notification, Message $app, ?Message $content = null): array
    {
        $payload = [
            'broadcast' => true,
        ];

        if ($notification->has('braze_segment_id')) {
            $payload['segment_id'] = $notification->get('braze_segment_id')->toString();
        }

        if ($notification->has('braze_campaign_id')) {
            $payload['campaign_id'] = $notification->get('braze_campaign_id')->toString();
        }

        return $payload;
    }

    protected function validate(Message $notification, Message $app): void
    {
        if (!$app->has('braze_api_key')) {
            throw new RequiredFieldNotSet("[braze_api_key] is required on {$app->generateNodeRef()}");
        }
        $this->apiKey = Crypto::decrypt($app->get('braze_api_key'), $this->key);
    }

    protected function sendNotification(array $payload): array
    {
        try {
            $response = $this->getGuzzleClient()->request('POST', '/messages/send', [RequestOptions::JSON => $payload]);

            $httpCode = HttpCode::from($response->getStatusCode());
            $content = $response->getBody()->getContents();

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
            $httpCode = HttpCode::tryFrom($exception->getResponse()->getStatusCode()) ?: HttpCode::UNKNOWN;
            $response = $exception->getResponse()->getBody()->getContents() ?: '{}';
        } else {
            $httpCode = HttpCode::tryFrom($exception->getCode()) ?: HttpCode::HTTP_INTERNAL_SERVER_ERROR;
            $response = '{}';
        }

        return [
            'ok'            => false,
            'code'          => StatusCodeUtil::httpToVendor($httpCode)->value,
            'raw_response'  => $response,
            'error_name'    => ClassUtil::getShortName($exception),
            'error_message' => substr($exception->getMessage(), 0, 2048),
        ];
    }

    public function send(Message $notification, Message $app, ?Message $content = null): Message
    {
        if ($this->flags->getBoolean(self::DISABLED_FLAG_NAME)) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::CANCELLED->value)
                ->set('error_name', 'BrazeNotifierDisabled')
                ->set('error_message', 'Flag [' . self::DISABLED_FLAG_NAME . '] is true');
        }

        try {
            $this->validate($notification, $app);
            $payload = $this->buildPayload($notification, $app, $content);
            $result = $this->sendNotification($payload);
            $response = $result['response'] ?? [];
            $result = NotifierResultV1::fromArray($result);

            if ($result->get('ok') && isset($response['send_id'])) {
                $result->addToMap('tags', 'send_id', $response['send_id']);
            }

            return $result;
        } catch (\Throwable $e) {
            $code = Code::tryFrom($e->getCode()) ?: Code::UNKNOWN;

            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', $code->value)
                ->set('error_name', ClassUtil::getShortName($e))
                ->set('error_message', substr($e->getMessage(), 0, 2048));
        }
    }
}
