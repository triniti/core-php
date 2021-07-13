<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify\Notifier;

use Acme\Schemas\Canvas\Block\TextBlockV1;
use Acme\Schemas\Iam\Node\TwitterAppV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Node\TwitterNotificationV1;
use Acme\Schemas\Sys\Node\FlagsetV1;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Triniti\Notify\Notifier\TwitterNotifier;
use Triniti\Sys\Flags;
use Triniti\Tests\AbstractPbjxTest;

class TwitterNotifierTest extends AbstractPbjxTest
{
    protected Key $key;
    private TwitterNotifier $notifier;
    protected InMemoryNcr $ncr;
    protected Message $app;
    protected Message $notification;
    protected Message $content;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        $this->key = Key::createNewRandomKey();
        $this->app = $this->getApp();
        $this->content = $this->getContent();
        $this->notification = $this->getNotification();

        $flagset = FlagsetV1::fromArray(['_id' => 'test']);
        $this->ncr->putNode($flagset);
        $flags = new Flags($this->ncr, 'acme:flagset:test');

        $this->notifier = new class($flags, $this->key) extends TwitterNotifier {
            protected string $status;

            public function setFlags(Flags $flags): void
            {
                $this->flags = $flags;
            }

            public function getStatus(): string
            {
                return $this->status;
            }

            protected function postTweet(string $status): array
            {
                $this->status = $status;
                return parent::postTweet($status);
            }

            protected function getGuzzleClient(): GuzzleClient
            {
                $mock = new MockHandler(
                    [
                        new Response(
                            200,
                            ['Content-Type' => 'application/json'],
                            json_encode(['id_str' => '123', 'user' => ['screen_name' => 'tester']])
                        ),
                    ]
                );
                $handler = HandlerStack::create($mock);

                return new GuzzleClient(
                    [
                        'base_uri' => 'https://api.com',
                        'handler'  => $handler,
                    ]
                );
            }
        };
    }

    public function testSendWithTwitterNotifierDisabled()
    {
        $flagset = FlagsetV1::fromArray(
            [
                '_id'      => 'twitter',
                'booleans' => ['twitter_notifier_disabled' => true],
            ]
        );
        $this->ncr->putNode($flagset);
        $this->notifier->setFlags(new Flags($this->ncr, 'acme:flagset:twitter'));
        $result = $this->notifier->send($this->notification, $this->app, $this->content);

        $this->assertFalse($result->get('ok'));
        $this->assertSame('TwitterNotifierDisabled', $result->get('error_name'));
    }

    public function testSend()
    {
        $result = $this->notifier->send($this->notification, $this->app, $this->content);
        $this->assertSame('123', $result->getFromMap('tags', 'tweet_id'));
        $this->assertSame("https://twitter.com/tester/status/123", $result->getFromMap('tags', 'tweet_url'));
        $this->assertSame('tester', $result->getFromMap('tags', 'twitter_screen_name'));
    }

    public function testSendStatusOverride()
    {
        $this->notification->clear('body');
        $this->notifier->send($this->notification, $this->app, $this->content);
        $status = $this->notifier->getStatus();
        $this->assertSame('Lorem Ipsum https://www.acme.com/2018/08/08/lorem-ipsum/', $status);

        $this->content->set('meta_description', 'meta description');
        $this->notifier->send($this->notification, $this->app, $this->content);
        $status = $this->notifier->getStatus();
        $this->assertSame('meta description https://www.acme.com/2018/08/08/lorem-ipsum/', $status);
    }

    public function testSendWithExceptionResponse()
    {
        $flagset = FlagsetV1::fromArray(
            [
                '_id'      => 'twitter',
                'booleans' => ['twitter_notifier_disabled' => false],
            ]
        );
        $this->ncr->putNode($flagset);
        $flags = new Flags($this->ncr, 'acme:flagset:twitter');
        $this->notifier->setFlags($flags);
        $this->notifier = new class($flags, $this->key) extends TwitterNotifier {
            public function setFlags(Flags $flags): void
            {
                $this->flags = $flags;
            }

            protected function getGuzzleClient(): GuzzleClient
            {
                $mock = new MockHandler(
                    [
                        new RequestException(
                            'Bad Request',
                            new Request(
                                'POST',
                                'test')
                            ,
                            new Response(
                                403,
                                ['Content-Type' => 'application/json'],
                                json_encode(['id_str' => '123'])
                            ),
                        ),

                    ]
                );
                $handler = HandlerStack::create($mock);

                return new GuzzleClient(
                    [
                        'base_uri' => 'https://api.com',
                        'handler'  => $handler,
                    ]
                );
            }
        };
        $result = $this->notifier->send($this->notification, $this->app, $this->content);
        $this->assertSame('RequestException', $result->get('error_name'));
    }

    protected function getNotification(): Message
    {
        return TwitterNotificationV1::create()
            ->set('content_ref', $this->content->generateNodeRef())
            ->set('status', NodeStatus::PUBLISHED())
            ->set('title', 'Lorem ipsum dolor sit amet')
            ->set(
                'body',
                'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
            );
    }

    protected function getApp(): Message
    {
        return TwitterAppV1::create()
            ->set('status', NodeStatus::PUBLISHED())
            ->set('title', 'Twitter App')
            ->set('oauth_consumer_key', 'oauth_consumer_key')
            ->set(
                'oauth_consumer_secret',
                Crypto::encrypt('oauth_consumer_secret', $this->key)
            )
            ->set('oauth_token', 'oauth_token')
            ->set(
                'oauth_token_secret',
                Crypto::encrypt('oauth_token_secret', $this->key)
            );
    }

    protected function getContent(): Message
    {
        return ArticleV1::create()
            ->set('title', 'Lorem Ipsum')
            ->set('status', NodeStatus::PUBLISHED())
            ->set('slug', '2018/08/08/lorem-ipsum')
            ->addToList(
                'blocks',
                [
                    TextBlockV1::create()
                        ->set('text', '<p>this is a block from unit test</p>'),
                ]
            );
    }
}


