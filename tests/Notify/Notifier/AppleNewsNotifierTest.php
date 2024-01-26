<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify\Notifier;

use Acme\Schemas\Iam\Node\AppleNewsAppV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Notify\Node\AppleNewsNotificationV1;
use Acme\Schemas\Ovp\Node\VideoV1;
use Acme\Schemas\Sys\Node\FlagsetV1;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbj\WellKnown\UuidIdentifier;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\UriTemplate\UriTemplateService;
use Triniti\AppleNews\ArticleDocumentMarshaler;
use Triniti\Dam\UrlProvider;
use Triniti\Notify\Notifier\AppleNewsNotifier;
use Triniti\Sys\Flags;
use Triniti\Tests\AbstractPbjxTest;

class AppleNewsNotifierTest extends AbstractPbjxTest
{
    protected Message $app;
    protected AppleNewsNotifier $appleNewsNotifier;
    protected AppleNewsNotifier $mockAppleNewsNotifier;
    protected Message $notification;
    protected UrlProvider $urlProvider;
    protected Message $videoNode;
    protected InMemoryNcr $ncr;

    protected function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        $this->markTestSkipped();

        // fixme: eliminate use of reflection and mocks, use guzzle mock ref SendGridEmailNotifierTest

        // using fake key and fake secret
        $key = Key::loadFromAsciiSafeString('def000003bd033b3a29a123785dcf50f57355722c41595baa2df2724f5dc1381d51ac6e7d5b9aef857133fda22d3959e78c86f964e2660ecb934d605461578d708851112');
        $this->app = AppleNewsAppV1::create()
            ->set('api_key', 'key')
            ->set('api_secret', 'def50200ebe14ca27c17e1befcf7f16291f3fd432310791441371a7ad80928b76d5bbc5d05081d0644a236cfcbd73c71c82beefab91cc3d2fdc51e01cfe80437485e3d973aaa18ba044747a56ce81ed66e2807e86565de139707');

        $marshaler = new ArticleDocumentMarshaler($this->ncr, $this->pbjx, $this->urlProvider);

        $flagset = FlagsetV1::fromArray(['_id' => 'test']);
        $this->ncr->putNode($flagset);
        $flags = new Flags($this->ncr, 'acme:flagset:test');

        $this->appleNewsNotifier = new AppleNewsNotifier($flags, $key, $marshaler);
        $this->notification = AppleNewsNotificationV1::create()
            ->set('apple_news_id', UuidIdentifier::fromString('1fd3f344-28c1-4ad3-acb3-f32eac206401'))
            ->set('apple_news_revision', str_replace('=', '', strtr(base64_encode('AAAAAAAAAAAAAAAAAAAAAw=='), '+/', '-_')));

        $this->mockAppleNewsNotifier = $this->getMockBuilder('Triniti\Notify\Notifier\AppleNewsNotifier')
            ->enableOriginalConstructor()
            ->setConstructorArgs(
                [
                    $key,
                    $this->ncr,
                    $this->pbjx,
                    $this->urlProvider,
                    $marshaler,
                ]
            )
            ->onlyMethods(
                [
                    'createArticleDocument',
                    'updateArticleDocument',
                    'deleteArticleDocument',
                    'sendNotification',
                ]
            )
            ->getMock();
        $this->mockAppleNewsNotifier->method('createArticleDocument')->willReturn(['ok' => false, 'code' => 1, 'response' => 'test', 'error_name' => 'test', 'error_message' => 'test']);
        $this->mockAppleNewsNotifier->method('updateArticleDocument')->willReturn(['ok' => false, 'code' => 1, 'response' => 'test', 'error_name' => 'test', 'error_message' => 'test']);
        $this->mockAppleNewsNotifier->method('deleteArticleDocument')->willReturn(['ok' => false, 'code' => 1, 'response' => 'test', 'error_name' => 'test', 'error_message' => 'test']);
        $this->mockAppleNewsNotifier->method('sendNotification')->willReturn(['ok' => false, 'code' => 1, 'response' => 'test', 'error_name' => 'test', 'error_message' => 'test']);

        UriTemplateService::registerGlobals([
            'web_base_url' => $_SERVER['WEB_BASE_URL'] ?? 'https://acme.local/',
        ]);

        UriTemplateService::registerTemplates([
            'acme:article.canonical' => '{+web_base_url}{+slug}/',
        ]);

        $this->videoNode = VideoV1::create()
            ->set('status', NodeStatus::PUBLISHED)
            ->set('_id', UuidIdentifier::fromString('83a6e989-0704-474c-bea5-a5df46965d0a'))
            ->set('kaltura_mp4_url', 'http://www.test.com/test.mp4')
            ->set('description', 'test')
            ->set('slug', 'test-video')
            ->set('poster_image_ref', NodeRef::fromString('acme:image-asset:image_jpg_20190226_25a0f6c553a74bca99617edfd25aaa30'));

        $this->ncr->putNode($this->videoNode);
        $this->ncr->putNode($this->getContent());
    }

    /**
     * Create a ReflectionClass to make protected method public during tests
     *
     * @param $name
     *
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    protected static function getMethod($name)
    {
        $class = new \ReflectionClass('\Triniti\Notify\Notifier\AppleNewsNotifier');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * @throws \ReflectionException
     */
    public function testCreateNcrContext()
    {
        $ncrContext = self::getMethod('createNcrContext');

        $this->assertSame(
            [],
            $ncrContext->invokeArgs($this->appleNewsNotifier, [$this->notification]),
            'Default ncr context should be a empty array'
        );
    }

    /**
     * Test to see if correct operation is being performed depending on apple_news_operation field in notification
     */
    public function testSendCreateArticleDocument()
    {
        $this->notification->set('apple_news_operation', 'create');
        $this->mockAppleNewsNotifier->expects($this->once())->method('createArticleDocument');
        $this->mockAppleNewsNotifier->send($this->notification, $this->app, $this->getContent());
    }

    /**
     * Test to see if correct operation is being performed depending on apple_news_operation field in notification
     */
    public function testSendUpdateArticleDocument()
    {
        $this->notification->set('apple_news_operation', 'update');
        $this->mockAppleNewsNotifier->expects($this->once())->method('updateArticleDocument');
        $this->mockAppleNewsNotifier->send($this->notification, $this->app, $this->getContent());
    }

    /**
     * Test to see if correct operation is being performed depending on apple_news_operation field in notification
     */
    public function testSendDeleteArticleDocument()
    {
        $this->notification->set('apple_news_operation', 'delete');
        $this->mockAppleNewsNotifier->expects($this->once())->method('deleteArticleDocument');
        $this->mockAppleNewsNotifier->send($this->notification, $this->app, $this->getContent());
    }

    /**
     * Test to see if correct operation is being performed depending on apple_news_operation field in notification
     */
    public function testSendNotification()
    {
        $this->notification->set('apple_news_operation', 'notification');
        $this->mockAppleNewsNotifier->expects($this->once())->method('sendNotification');
        $this->mockAppleNewsNotifier->send($this->notification, $this->app, $this->getContent());
    }

    /**
     * Create a content node
     */
    protected function getContent()
    {
        return ArticleV1::fromArray([
            "_schema"          => "pbj:acme:news:node:article:1-0-0",
            "_id"              => "1ccd64a3-e71d-48d7-9cc5-476facde7779",
            "status"           => "published",
            "etag"             => "67991520f5dedbdec055f1e568ba6d3d",
            "created_at"       => "1551130692632537",
            "updated_at"       => "1551304043157750",
            "title"            => "Hot Dog Champ Miki Sudo Wants Equal Pay, Not Attention, To Joey Chestnut",
            "published_at"     => "2018-07-13T00:14:29.310Z",
            "slug"             => "2018/08/08/cardi-b-files-15",
            "blocks"           => [
                [
                    "_schema"          => "pbj:acme:canvas:block:video-block:1-0-0",
                    "etag"             => "4e6b3f2a79a303642360bcc5b75d2dde",
                    "updated_date"     => null,
                    "node_ref"         => "acme:video:83a6e989-0704-474c-bea5-a5df46965d0a",
                    "autoplay"         => false,
                    "launch_text"      => null,
                    "muted"            => false,
                    "start_at"         => 0,
                    "show_more_videos" => false,
                    "poster_image_ref" => "acme:image-asset:image_jpg_20190226_25a0f6c553a74bca99617edfd25aaa30",
                ],
                [
                    "_schema" => "pbj:acme:canvas:block:text-block:1-0-0",
                    "etag"    => "58d55d134b9b140f26ce88265b2c9585",
                    "text"    => "<p><a href=\"http://www.google.com/\" rel=\"noopener noreferrer\">this is a block from unit test</a></p>",
                ],
            ],
            "seo_title"        => 'custom seo title',
            "seo_image_ref"    => null,
            "meta_description" => null,
            "meta_keywords"    => ['a', 'b', 'c'],
        ]);
    }
}
