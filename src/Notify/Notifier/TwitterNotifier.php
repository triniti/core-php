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
use Gdbots\UriTemplate\UriTemplateService;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Triniti\Notify\Exception\RequiredFieldNotSet;
use Triniti\Notify\Notifier;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Sys\Flags;

class TwitterNotifier implements Notifier
{
    const API_ENDPOINT = 'https://api.twitter.com/1.1/';

    protected Flags $flags;
    protected Key $key;
    protected ?GuzzleClient $guzzleClient = null;
    protected string $oauthConsumerKey = '';
    protected string $oauthConsumerSecret = '';
    protected string $oauthToken = '';
    protected string $oauthTokenSecret = '';

    public function __construct(Flags $flags, Key $key)
    {
        $this->flags = $flags;
        $this->key = $key;
    }

    public function send(Message $notification, Message $app, ?Message $content = null): Message
    {
        if ($this->flags->getBoolean('twitter_notifier_disabled')) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::CANCELLED->value)
                ->set('error_name', 'TwitterNotifierDisabled')
                ->set('error_message', 'Flag [twitter_notifier_disabled] is true');
        }

        try {
            $this->guzzleClient = null;
            $this->validate($notification, $app);
            $this->oauthConsumerKey = $app->get('oauth_consumer_key');
            $this->oauthConsumerSecret = Crypto::decrypt($app->get('oauth_consumer_secret'), $this->key);
            $this->oauthToken = $app->get('oauth_token');
            $this->oauthTokenSecret = Crypto::decrypt($app->get('oauth_token_secret'), $this->key);
            $tweet = $this->generateTweet($notification, $app, $content);
            if (empty($tweet)) {
                return NotifierResultV1::create()
                    ->set('ok', false)
                    ->set('code', Code::INVALID_ARGUMENT->value)
                    ->set('error_name', 'NullContent')
                    ->set('error_message', 'Tweet cannot be null');
            }

            $result = $this->postTweet($tweet);
        } catch (\Throwable $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : Code::UNKNOWN->value;
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', $code)
                ->set('error_name', ClassUtil::getShortName($e))
                ->set('error_message', substr($e->getMessage(), 0, 2048));
        }

        $response = $result['response'] ?? [];
        $result = NotifierResultV1::fromArray($result);

        if (isset($response['id_str'])) {
            $tweetId = (string)$response['id_str'];
            $result->addToMap('tags', 'tweet_id', $tweetId);

            $screenName = $response['user']['screen_name'] ?? null;
            if ($screenName) {
                $result->addToMap('tags', 'twitter_screen_name', (string)$screenName);
                $tweetUrl = "https://twitter.com/{$screenName}/status/{$tweetId}";
                $result->addToMap('tags', 'tweet_url', $tweetUrl);
            }
        }

        return $result;
    }

    protected function validate(Message $notification, Message $app): void
    {
        foreach (['oauth_consumer_key', 'oauth_consumer_secret', 'oauth_token', 'oauth_token_secret'] as $field) {
            if (!$app->has($field)) {
                throw new RequiredFieldNotSet("[{$field}] is required");
            }
        }
    }

    protected function generateTweet(Message $notification, Message $app, ?Message $content = null): ?string
    {
        if (null === $content) {
            return $notification->get('body');
        }

        $tweet = $notification->get('body', $content->get('meta_description', $content->get('title')))
            . ' ' . $this->getCanonicalUrl($content);
        return trim($tweet);
    }

    protected function getCanonicalUrl(Message $message): ?string
    {
        return UriTemplateService::expand(
            "{$message::schema()->getQName()}.canonical",
            $message->getUriTemplateVars()
        );
    }

    protected function postTweet(string $tweet): array
    {
        $options = [
            RequestOptions::FORM_PARAMS => [
                'status' => $tweet,
            ],
        ];

        try {
            $response = $this->getGuzzleClient()->post('statuses/update.json', $options);
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

    protected function getGuzzleClient(): GuzzleClient
    {
        if (null === $this->guzzleClient) {
            $stack = HandlerStack::create();
            $middleware = new Oauth1([
                'consumer_key'    => $this->oauthConsumerKey,
                'consumer_secret' => $this->oauthConsumerSecret,
                'token'           => $this->oauthToken,
                'token_secret'    => $this->oauthTokenSecret,
            ]);
            $stack->push($middleware);

            $this->guzzleClient = new GuzzleClient([
                'base_uri' => self::API_ENDPOINT,
                'handler'  => $stack,
                'auth'     => 'oauth',
            ]);
        }

        return $this->guzzleClient;
    }
}
