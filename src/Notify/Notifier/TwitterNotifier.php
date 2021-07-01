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
use Triniti\Notify\Notifier;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Sys\Flags;

class TwitterNotifier implements Notifier
{
    const ENDPOINT = 'https://api.twitter.com/1.1/';

    protected Flags $flags;
    protected Key $key;
    protected ?GuzzleClient $guzzleClient = null;
    protected string $oauthConsumerKey;
    protected string $oauthConsumerSecret;
    protected string $oauthToken;
    protected string $oauthTokenSecret;

    public function __construct(Flags $flags, Key $key)
    {
        $this->flags = $flags;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Message $notification, Message $app, ?Message $content = null): Message
    {
        if (null === $content) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::INVALID_ARGUMENT)
                ->set('error_name', 'NullContent')
                ->set('error_message', 'Content cannot be null');
        }

        if ($this->flags->getBoolean('twitter_notifier_disabled')) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::CANCELLED)
                ->set('error_name', 'TwitterNotifierDisabled')
                ->set('error_message', 'Flag [twitter_notifier_disabled] is true');
        }

        try {
            $this->oauthConsumerKey = $app->get('oauth_consumer_key');
            $this->oauthConsumerSecret = Crypto::decrypt($app->get('oauth_consumer_secret'), $this->key);
            $this->oauthToken = $app->get('oauth_token');
            $this->oauthTokenSecret = Crypto::decrypt($app->get('oauth_token_secret'), $this->key);

            $tweet = $notification->get('body', $content->get('meta_description', $content->get('title')))
                . ' ' . $this->getCanonicalUrl($content);
            $result = $this->postTweet($tweet);
        } catch (\Throwable $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : Code::UNKNOWN;
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', $code)
                ->set('error_name', ClassUtil::getShortName($e))
                ->set('error_message', substr($e->getMessage(), 0, 2048));
        }

        $notifierResult = NotifierResultV1::fromArray($result);
        if (isset($result['response'])) {
            $id = $result['response']['id_str'] ?? null;
            $screenName = $result['response']['user']['screen_name'] ?? null;
            $tweetUrl = null !== $id && null !== $screenName ? "https://twitter.com/{$screenName}/status/{$id}" : null;

            $notifierResult->addToMap('tags', 'id', $id);
            $notifierResult->addToMap('tags', 'tweet_url', $tweetUrl);
        }

        return $notifierResult;
    }

    protected function postTweet(string $tweet): array {
        $options = [
            RequestOptions::FORM_PARAMS => [
                'status' => $tweet,
            ],
        ];

        try {
            $response = $this->getGuzzleClient()->post('statuses/update.json', $options);
            $content = (string)$response->getBody()->getContents();
            $httpCode = $response->getStatusCode();
            $json = json_decode($content, true);

            return [
                'ok' => HttpCode::HTTP_OK === $httpCode,
                'code' => StatusCodeUtil::httpToVendor($httpCode),
                'http_code' => $httpCode,
                'raw_response' => $content,
                'response' => $json,
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

    protected function getCanonicalUrl(Message $message): string
    {
        return UriTemplateService::expand(
            "{$message::schema()->getQName()}.canonical",
            $message->getUriTemplateVars()
        );
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
                'base_uri' => self::ENDPOINT,
                'handler'  => $stack,
                'auth' => 'oauth'
            ]);
        }

        return $this->guzzleClient;
    }
}
