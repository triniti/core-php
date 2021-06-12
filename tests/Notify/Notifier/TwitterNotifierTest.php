<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify\Notifier;

use Acme\Schemas\Canvas\Block\TextBlockV1;
use Acme\Schemas\Iam\Node\EmailAppV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Node\EmailNotificationV1;
use Acme\Schemas\Sys\Node\FlagsetV1;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Enum\Code;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Triniti\Notify\Notifier\SendGridEmailNotifier;
use Triniti\Notify\Notifier\TwitterNotifier;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Sys\Flags;
use Triniti\Tests\AbstractPbjxTest;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwitterNotifierTest extends AbstractPbjxTest
{
    protected Key $key;
    protected Environment $twig;
    private TwitterNotifier $notifier;
    protected InMemoryNcr $ncr;

    public function setup(): void {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        $this->key = Key::createNewRandomKey();
        $flagset = FlagsetV1::fromArray(['_id' => 'test']);
        $this->ncr->putNode($flagset);
        $flags = new Flags($this->ncr, 'acme:flagset:test');
        $this->notifier = new TwitterNotifier($flags, $this->key);

//        $this->notifier = new class($flags, $this->key) extends TwitterNotifier
//        {
//            /**
//             * @param Flags $flags
//             */
//            public function setFlags(Flags $flags): void
//            {
//                $this->flags = $flags;
//            }
//
//            protected function getGuzzleClient(): GuzzleClient
//            {
//                $mock = new MockHandler(
//                    [
//                        new Response(
//                            201,
//                            ['Content-Type' => 'application/json'],
//                            json_encode(['id' => 123])
//                        ),
//                    ]
//                );
//                $handler = HandlerStack::create($mock);
//
//                return new GuzzleClient(
//                    [
//                        'base_uri' => 'https://api.com',
//                        'handler'  => $handler,
//                    ]
//                );
//            }
//        };
    }

    public function testBeamMeUpScotty()
    {
        $result = $this->notifier->beamMeUpScotty('sorry bro i have piano lesson now i have to go');
        $id = $result['response']['id_str'] ?? null;
        $result = NotifierResultV1::fromArray($result);
        if ($id) {
            $result->addToMap('tags', 'id', $id);
        }

        print_r($result);
    }

    /**
     * Emails are sent without a content-ref, notification body is rendered as email body
     * note a valid use case yet.
     */
//    public function xxtestSendWithoutContent()
//    {
//        $result = $this->notifier->send($this->getNotification(), $this->getApp());
//        $this->assertTrue($result->get('ok'));
//        $this->assertSame('123', $result->getFromMap('tags', 'sendgrid_campaign_id'));
//    }

//    /**
//     * @return EmailNotificationV1
//     */
//    protected function getNotification(): EmailNotificationV1
//    {
//        return EmailNotificationV1::create()
//            ->set('status', NodeStatus::PUBLISHED())
//            ->set('title', 'Lorem ipsum dolor sit amet')
//            ->set('sender', 'hello@example.com')
//            ->set('template', 'breaking-news')
//            ->set('subject', 'Lorem ipsum dolor sit amet')
//            ->set(
//                'body',
//                'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
//            )
//            ->addToSet('lists', ['demo']);
//    }
//
//    /**
//     * @return EmailAppV1
//     */
//    protected function getApp(): EmailAppV1
//    {
//        return EmailAppV1::create()
//            ->set('status', NodeStatus::PUBLISHED())
//            ->set('title', 'SendGrid Email')
//            ->set(
//                'sendgrid_api_key',
//                Crypto::encrypt('XXX', $this->key)
//            )
//            ->set('sendgrid_suppression_group_id', 6775)
//            ->addToMap('sendgrid_senders', 'hello@example.com', 319136)
//            ->addToMap('sendgrid_lists', 'demo', 4946985);
//    }
//
//    public function testSendWithSendGridFlagDisabled()
//    {
//        $flagset = FlagsetV1::fromArray(
//            [
//                '_id'      => 'sendgrid',
//                'booleans' => ['sendgrid_email_notifier_disabled' => true],
//            ]
//        );
//        $this->ncr->putNode($flagset);
//        $this->notifier->setFlags(new Flags($this->ncr, 'acme:flagset:sendgrid'));
//        $result = $this->notifier->send($this->getNotification(), $this->getApp(), $this->getContent());
//
//        $this->assertFalse($result->get('ok'));
//        $this->assertSame(Code::CANCELLED, $result->get('code'));
//    }
//
//    /**
//     * @return ArticleV1
//     */
//    protected function getContent(): ArticleV1
//    {
//        return ArticleV1::create()
//            ->set('title', 'Lorem Ipsum')
//            ->set('status', NodeStatus::PUBLISHED())
//            ->set('slug', '2018/08/08/lorem-ipsum')
//            ->addToList(
//                'blocks',
//                [
//                    TextBlockV1::create()
//                        ->set('text', '<p>this is a block from unit test</p>'),
//                ]
//            );
//    }
//
//    public function testSendWithMissingSender()
//    {
//        $notification = $this->getNotification();
//        $notification->clear('sender');
//        $result = $this->notifier->send($notification, $this->getApp(), $this->getContent());
//        $this->assertFalse($result->get('ok'));
//        $this->assertEquals(Code::INVALID_ARGUMENT, $result->get('code'));
//    }
//
//    public function testSend()
//    {
//        $result = $this->notifier->send($this->getNotification(), $this->getApp(), $this->getContent());
//        $this->assertTrue($result->get('ok'));
//        $this->assertSame('123', $result->getFromMap('tags', 'sendgrid_campaign_id'));
//    }
}


