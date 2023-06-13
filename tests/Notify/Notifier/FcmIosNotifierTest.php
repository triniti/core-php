<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify\Notifier;

use Acme\Schemas\Iam\Node\IosAppV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Node\IosNotificationV1;
use Acme\Schemas\Sys\Node\FlagsetV1;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Schemas\Pbjx\Enum\Code;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Triniti\Notify\Notifier;
use Triniti\Notify\Notifier\FcmIosNotifier;
use Triniti\Sys\Flags;
use Triniti\Tests\AbstractPbjxTest;

class FcmIosNotifierTest extends AbstractPbjxTest
{
    protected Flags $flags;
    protected Key $key;
    protected Notifier $notifier;
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();

        $flagset = FlagsetV1::fromArray(['_id' => 'test']);
        $this->ncr->putNode($flagset);
        $this->flags = new Flags($this->ncr, 'acme:flagset:test');
        $this->key = Key::createNewRandomKey();
        $this->notifier = new class($this->flags, $this->key) extends FcmIosNotifier
        {
            protected function getGuzzleClient(): GuzzleClient
            {
                $mock = new MockHandler([
                    new Response(201, [], '{"name":"projects/123"}'),
                ]);
                return new GuzzleClient(['handler' => HandlerStack::create($mock)]);
            }

            public function setFlags(Flags $flags): void
            {
                $this->flags = $flags;
            }

            protected function fetchAccessToken(): string
            {
                return '123';
            }

            protected function parseAuthConfig(Message $app): array
            {
                return [
                    'project_id'     => 'acme-test',
                    'client_email'   => 'test@acme.com',
                    'client_id'      => '123',
                    'private_key_id' => '123',
                    'private_key'    => '123'
                ];
            }

            protected function validate(Message $notification, Message $app): void {}
        };
    }

    public function testSendWithIosFlagDisabled()
    {
        $flagset = FlagsetV1::fromArray(
            [
                '_id'      => 'ios',
                'booleans' => ['fcm_ios_notifier_disabled' => true],
            ]
        );
        $this->ncr->putNode($flagset);
        $this->notifier->setFlags(new Flags($this->ncr, 'acme:flagset:ios'));
        $result = $this->notifier->send($this->getNotification(), $this->getApp(), $this->getContent());

        $this->assertFalse($result->get('ok'), 'notifications are cancelled when flag is disabled');
        $this->assertSame(Code::CANCELLED->value, $result->get('code'), 'code must be set to cancelled when flag is disabled');
    }

    /**
     * Notifications can be sent without a content-ref, payload uses notification body field
     */
    public function testSendWithoutContent()
    {
        $result = $this->notifier->send($this->getNotificationWithTopics(), $this->getApp());
        $this->assertTrue($result->get('ok'), 'notifications can be sent without content');
        $this->assertSame(
            '123',
            $result->getFromMap('tags', 'fcm_message_id'),
            'fcm_message_id must match'
        );
    }

    public function testSendWithNotificationBody()
    {
        $result = $this->notifier->send(
            $this->getNotificationWithBody(),
            $this->getApp(),
            $this->getContent()
        );
        $this->assertTrue($result->get('ok'), 'notification must be sent successfully when FCM topics are set');
    }

    public function testSendWithTopics()
    {
        $result = $this->notifier->send(
            $this->getNotificationWithTopics(),
            $this->getApp(),
            $this->getContent()
        );
        $this->assertTrue($result->get('ok'), 'notification must be sent successfully when FCM topics are set');
    }

    protected function getNotification(): IosNotificationV1
    {
        return IosNotificationV1::create()
            ->set('title', 'Title of the notification');
    }

    protected function getNotificationWithBody(): IosNotificationV1
    {
        return IosNotificationV1::create()
            ->set('title', 'Title of the notification')
            ->set('body', 'Body of the notification');
    }

    protected function getNotificationWithTopics(): IosNotificationV1
    {
        return IosNotificationV1::create()
            ->set('title', 'Title of the notification')
            ->addToSet('fcm_topics', ['ios-all']);
    }

    protected function getApp(): IosAppV1
    {
        return IosAppV1::create();
    }

    protected function getContent(): ArticleV1
    {
        return ArticleV1::fromArray(
            [
                '_id'   => '5c9cc362-5a4b-11e9-9606-30342d323838',
                'title' => 'Article title',
            ]
        );
    }
}
