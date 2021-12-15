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
use Triniti\Notify\Exception\InvalidNotificationContent;
use Triniti\Notify\Exception\RequiredFieldNotSet;
use Triniti\Notify\Notifier;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Sys\Flags;

abstract class AbstractAzureNotifier implements Notifier
{
    const API_VERSION = '?api-version=2015-01';
    const DISABLED_FLAG_NAME = 'azure_notifier_disabled';
    const FORMAT = 'unknown';

    protected string $endpoint = '';
    protected string $hubName = '';
    protected string $sasKeyName = '';
    protected string $sasKeyValue = '';

    protected ?GuzzleClient $guzzleClient = null;
    protected Flags $flags;
    protected Key $key;

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
                ->set('error_name', 'AzureNotifierDisabled')
                ->set('error_message', 'Flag [' . static::DISABLED_FLAG_NAME . '] is true');
        }

        try {
            $this->guzzleClient = null;
            $this->validate($notification, $app);
            $connectionString = Crypto::decrypt($app->get('azure_notification_hub_connection'), $this->key);
            $this->parseConnectionString($connectionString);
            $this->hubName = $app->get('azure_notification_hub_name');
            $payload = $this->buildPayload($notification, $app, $content);
            $result = $this->sendNotification($payload);
        } catch (\Throwable $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : Code::UNKNOWN->value;
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', $code)
                ->set('error_name', ClassUtil::getShortName($e))
                ->set('error_message', substr($e->getMessage(), 0, 2048));
        }

        $locationHeader = isset($result['location_header'], $result['location_header'][0])
            ? (string)$result['location_header'][0]
            : false;

        $response = $result['response'] ?? [];
        $result = NotifierResultV1::fromArray($result)
            ->set('raw_response', $response ? json_encode($response) : '{}');

        if ($result->get('ok') && $locationHeader) {
            $azureNotificationId = str_replace(
                [$this->endpoint . $this->hubName . '/messages/', self::API_VERSION], '', $locationHeader
            );
            $result->addToMap('tags', 'azure_notification_id', (string)$azureNotificationId);
        }

        return $result;
    }

    protected function validate(Message $notification, Message $app): void
    {
        if (!$app->has('azure_notification_hub_connection')) {
            throw new RequiredFieldNotSet('[azure_notification_hub_connection] is required');
        }

        if (!$app->has('azure_notification_hub_name')) {
            throw new RequiredFieldNotSet('[azure_notification_hub_name] is required');
        }
    }

    /**
     * @param string $connectionString
     *
     * @throws InvalidNotificationContent
     * @example
     * Endpoint=sb://{namespace}.servicebus.windows.net/;SharedAccessKeyName={keyName};SharedAccessKey={keyValue}
     *
     */
    protected function parseConnectionString(string $connectionString): void
    {
        $parts = explode(';', $connectionString);
        if (count($parts) !== 3) {
            throw new InvalidNotificationContent('Azure Notification hub connection string is invalid.');
        }

        foreach ($parts as $part) {
            if (str_starts_with($part, 'Endpoint=')) {
                $this->endpoint = 'https' . substr($part, strlen('Endpoint=sb'));
                continue;
            }

            if (str_starts_with($part, 'SharedAccessKeyName=')) {
                $this->sasKeyName = substr($part, strlen('SharedAccessKeyName='));
                continue;
            }

            if (str_starts_with($part, 'SharedAccessKey=')) {
                $this->sasKeyValue = substr($part, strlen('SharedAccessKey='));
                continue;
            }
        }
    }

    abstract protected function buildPayload(Message $notification, Message $app, ?Message $content): array;

    /**
     * @link https://docs.microsoft.com/en-us/previous-versions/azure/reference/dn223266%28v%3dazure.100%29
     *
     * @param array  $payload
     * @param string $tags
     *
     * @return array
     */
    protected function sendNotification(array $payload, ?string $tags = null): array
    {
        $headers = [
            'ServiceBusNotification-Format' => static::FORMAT,
        ];

        if (!empty($tags)) {
            $headers['ServiceBusNotification-Tags'] = $tags;
        }

        try {
            $response = $this->getGuzzleClient()->post($this->hubName . '/messages' . self::API_VERSION, [
                RequestOptions::HEADERS => $headers,
                RequestOptions::JSON    => $payload,
            ]);

            $httpCode = $response->getStatusCode();
            return [
                'ok'              => HttpCode::HTTP_OK === $httpCode || HttpCode::HTTP_CREATED === $httpCode,
                'code'            => StatusCodeUtil::httpToVendor($httpCode),
                'http_code'       => $httpCode,
                'response'        => json_decode((string)$response->getBody()->getContents(), true),
                'location_header' => $response->getHeader('Location'),
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
            $stack->push(
                Middleware::mapRequest(
                    function (RequestInterface $request) {
                        $targetUri = rawurlencode((string)$request->getUri());
                        $expires = time() + 300;
                        $stringToSign = $targetUri . "\n" . $expires;
                        $hashed = hash_hmac('sha256', $stringToSign, $this->sasKeyValue, true);
                        $signature = rawurlencode(base64_encode($hashed));
                        $authorization = sprintf(
                            'SharedAccessSignature sr=%s&sig=%s&se=%s&skn=%s',
                            $targetUri,
                            $signature,
                            $expires,
                            $this->sasKeyName
                        );

                        return $request->withHeader('Authorization', $authorization);
                    }
                )
            );

            $this->guzzleClient = new GuzzleClient([
                'base_uri' => $this->endpoint,
                'handler'  => $stack,
            ]);
        }

        return $this->guzzleClient;
    }
}
