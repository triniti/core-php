<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify\Notifier;

use Acme\Schemas\Iam\Node\AndroidAppV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Node\AndroidNotificationV1;
use Acme\Schemas\Sys\Node\FlagsetV1;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Schemas\Pbjx\Enum\Code;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Triniti\Notify\Notifier;
use Triniti\Notify\Notifier\AzureAndroidNotifier;
use Triniti\Sys\Flags;
use Triniti\Tests\AbstractPbjxTest;

class AzureAndroidNotifierTest extends AbstractPbjxTest
{
    const CONNECTION_STRING = 'Endpoint=sb://endpoint/;SharedAccessKeyName=keyName;SharedAccessKey=keyValue';
    const HUB_NAME = 'ad-hoc';

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
        $this->notifier = new class($this->flags, $this->key) extends AzureAndroidNotifier
        {
            public function getEndpoint(): string
            {
                return $this->endpoint;
            }

            public function getHubName(): string
            {
                return $this->hubName;
            }

            public function getSasKeyName(): string
            {
                return $this->sasKeyName;
            }

            public function getSasKeyValue(): string
            {
                return $this->sasKeyValue;
            }

            protected function getGuzzleClient(): GuzzleClient
            {
                $location = $this->getEndpoint() . $this->getHubName() . '/messages/123' . self::API_VERSION;
                $mock = new MockHandler(
                    [
                        new Response(201, ['Location' => $location]),
                    ]
                );
                $handler = HandlerStack::create($mock);

                return new GuzzleClient(
                    [
                        'base_uri' => $this->endpoint,
                        'handler'  => $handler,
                    ]
                );
            }

            public function setFlags(Flags $flags): void
            {
                $this->flags = $flags;
            }
        };
    }

    public function testSendWithAndroidFlagDisabled()
    {
        $flagset = FlagsetV1::fromArray(
            [
                '_id'      => 'android',
                'booleans' => ['azure_android_notifier_disabled' => true],
            ]
        );
        $this->ncr->putNode($flagset);
        $this->notifier->setFlags(new Flags($this->ncr, 'acme:flagset:android'));
        $result = $this->notifier->send($this->getNotification(), $this->getApp(), $this->getContent());

        $this->assertFalse($result->get('ok'), 'notifications are cancelled when flag is disabled');
        $this->assertSame(Code::CANCELLED, $result->get('code'), 'code must be set to cancelled when flag is disabled');
    }

    public function testSendWithOutAzureNotificationHubConnectionField()
    {
        $app = $this->getApp()->clear('azure_notification_hub_connection');
        $result = $this->notifier->send($this->getNotification(), $app, $this->getContent());
        $this->assertFalse($result->get('ok'), 'notifications are not sent when all the required fields are not set');
        $this->assertSame(
            Code::INVALID_ARGUMENT,
            $result->get('code'),
            'code must be set to invalid argument when all the required notification fields are not set'
        );
    }

    public function testSendWithOutAzureNotificationHubNameField()
    {
        $app = $this->getApp()->clear('azure_notification_hub_name');
        $result = $this->notifier->send($this->getNotification(), $app, $this->getContent());
        $this->assertFalse($result->get('ok'), 'notifications are not sent when all the required fields are not set');
        $this->assertSame(
            Code::INVALID_ARGUMENT,
            $result->get('code'),
            'code must be set to invalid argument when all the required notification fields are not set'
        );
    }

    /**
     * Notifications can be sent without a content-ref, payload uses notification body field
     */
    public function testSendWithoutContent()
    {
        $result = $this->notifier->send($this->getNotification(), $this->getApp());
        $this->assertTrue($result->get('ok'), 'notifications can be sent without content');
        $this->assertSame(
            '123',
            $result->getFromMap('tags', 'azure_notification_id'),
            'azure_notification_id must match'
        );
    }

    public function testSend()
    {
        $result = $this->notifier->send($this->getNotification(), $this->getApp(), $this->getContent());
        $this->assertTrue($result->get('ok'), 'notification must be successfully sent');
        $this->assertSame(
            '123',
            $result->getFromMap('tags', 'azure_notification_id'),
            'azure_notification_id must match'
        );
    }

    protected function getNotification(): AndroidNotificationV1
    {
        return AndroidNotificationV1::create()
            ->set('title', 'Title of the notification');
    }

    protected function getApp(): AndroidAppV1
    {
        return AndroidAppV1::create()
            ->set(
                'azure_notification_hub_connection',
                Crypto::encrypt(
                    self::CONNECTION_STRING,
                    $this->key
                )
            )
            ->set('azure_notification_hub_name', self::HUB_NAME);
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
