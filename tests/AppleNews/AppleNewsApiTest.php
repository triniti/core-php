<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Triniti\AppleNews\AppleNewsApi;
use Triniti\AppleNews\ArticleDocument;

class AppleNewsApiTest extends TestCase
{
    protected AppleNewsApi $mockAppleNewsApi;

    public function setUp(): void
    {
        $this->mockAppleNewsApi = $this->getMockBuilder('Triniti\AppleNews\AppleNewsApi')
            ->enableOriginalConstructor()
            ->setConstructorArgs(
                [
                    'key',
                    'secret',
                ]
            )
            ->onlyMethods(
                [
                    'getGuzzleClient',
                ]
            )
            ->getMock();
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object  $object     Instantiated object that we will run method on.
     * @param string  $methodName Method name to call
     * @param array   $parameters Array of parameters to pass into method.
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function invokeMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @param int $statusCode
     *
     * @dataProvider providerTestRequestInvalidStatusCode
     */
    public function testCreateArticleWithExceptionResponse(int $statusCode): void
    {
        $mock = new MockHandler([
            new Response($statusCode,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'errors' => [['code' => 'INVALID_TYPE', 'value' => 'not_valid']],
                ])
            ),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.com',
            'handler'  => $handler,
        ]);

        $this->mockAppleNewsApi->method('getGuzzleClient')->willReturn($guzzleClient);
        $articleDocument = new ArticleDocument();
        $response = $this->mockAppleNewsApi->createArticle('11', $articleDocument, ['token' => '12']);

        $this->assertFalse($response['ok']);
        $this->assertEquals($statusCode, $response['http_code']);
    }

    /**
     * @param int $statusCode
     *
     * @dataProvider providerTestRequestInvalidStatusCode
     */
    public function testUpdateArticleWithExceptionResponse(int $statusCode): void
    {
        $mock = new MockHandler([
            new Response($statusCode,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'errors' => [['code' => 'INVALID_TYPE', 'value' => 'not_valid']],
                ])
            ),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.com',
            'handler'  => $handler,
        ]);

        $this->mockAppleNewsApi->method('getGuzzleClient')->willReturn($guzzleClient);
        $articleDocument = new ArticleDocument();
        $response = $this->mockAppleNewsApi->updateArticle('11', $articleDocument, ['rev' => '12']);

        $this->assertFalse($response['ok']);
        $this->assertEquals($statusCode, $response['http_code']);
    }

    /**
     * @param int $statusCode
     *
     * @dataProvider providerTestRequestInvalidStatusCode
     */
    public function testDeleteArticleWithExceptionResponse(int $statusCode): void
    {
        $mock = new MockHandler([
            new Response($statusCode,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'errors' => [['code' => 'INVALID_TYPE', 'value' => 'not_valid']],
                ])
            ),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.com',
            'handler'  => $handler,
        ]);

        $this->mockAppleNewsApi->method('getGuzzleClient')->willReturn($guzzleClient);
        $response = $this->mockAppleNewsApi->deleteArticle('11');

        $this->assertFalse($response['ok']);
        $this->assertEquals($statusCode, $response['http_code']);
    }

    /**
     * @param int $statusCode
     *
     * @dataProvider providerTestRequestInvalidStatusCode
     */
    public function testCreateArticleNotificationWithExceptionResponse(int $statusCode): void
    {
        $mock = new MockHandler([
            new Response($statusCode,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'errors' => [['code' => 'INVALID_TYPE', 'value' => 'not_valid']],
                ])
            ),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.com',
            'handler'  => $handler,
        ]);

        $this->mockAppleNewsApi->method('getGuzzleClient')->willReturn($guzzleClient);
        $response = $this->mockAppleNewsApi->createArticleNotification('11', ['alertBody' => 'alert']);

        $this->assertFalse($response['ok']);
        $this->assertEquals($statusCode, $response['http_code']);
    }

    public function testCreateArticle(): void
    {
        $id = 'a91760f1-c169-47d2-9fc4-a7711341264d';
        $shareUrl = 'https://apple.news/ArRPpLPE9QXu3sehS0rvxvA';
        $revision = 'AAAAAAAAAAAAAAAAAAAAew==';

        $mock = new MockHandler([
            new Response(201,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'data' => ['id' => $id, 'shareUrl' => $shareUrl, 'revision' => $revision],
                ])
            ),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.com',
            'handler'  => $handler,
        ]);

        $this->mockAppleNewsApi->method('getGuzzleClient')->willReturn($guzzleClient);
        $articleDocument = new ArticleDocument();
        $response = $this->mockAppleNewsApi->createArticle('11', $articleDocument, ['rev' => '123']);

        $this->assertEquals($id, $response['response']['data']['id']);
        $this->assertEquals($shareUrl, $response['response']['data']['shareUrl']);
        $this->assertEquals($revision, $response['response']['data']['revision']);
    }

    public function testUpdateArticle(): void
    {
        $id = 'a91760f1-c169-47d2-9fc4-a7711341264d';
        $shareUrl = 'https://apple.news/ArRPpLPE9QXu3sehS0rvxvA';
        $revision = 'AAAAAAAAAAAAAAAAAAAAew==';

        $mock = new MockHandler([
            new Response(201,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'data' => ['id' => $id, 'shareUrl' => $shareUrl, 'revision' => $revision],
                ])
            ),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.com',
            'handler'  => $handler,
        ]);

        $this->mockAppleNewsApi->method('getGuzzleClient')->willReturn($guzzleClient);
        $articleDocument = new ArticleDocument();
        $response = $this->mockAppleNewsApi->updateArticle('11', $articleDocument, ['rev' => '123']);

        $this->assertEquals($id, $response['response']['data']['id']);
        $this->assertEquals($shareUrl, $response['response']['data']['shareUrl']);
        $this->assertEquals($revision, $response['response']['data']['revision']);
    }

    public function testDeleteArticle(): void
    {
        $mock = new MockHandler([
            new Response(204,
                ['Content-Type' => 'application/json'],
                json_encode([])
            ),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.com',
            'handler'  => $handler,
        ]);

        $this->mockAppleNewsApi->method('getGuzzleClient')->willReturn($guzzleClient);
        $response = $this->mockAppleNewsApi->deleteArticle('11');

        $this->assertTrue($response['ok'], '$response[\'ok\']');
    }

    public function testCreateArticleNotification(): void
    {
        $id = 'a91760f1-c169-47d2-9fc4-a7711341264d';
        $shareUrl = 'https://apple.news/ArRPpLPE9QXu3sehS0rvxvA';
        $revision = 'AAAAAAAAAAAAAAAAAAAAew==';

        $mock = new MockHandler([
            new Response(201,
                ['Content-Type' => 'application/json'],
                json_encode([
                    'data' => ['id' => $id, 'shareUrl' => $shareUrl, 'revision' => $revision],
                ])
            ),
        ]);

        $handler = HandlerStack::create($mock);
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.com',
            'handler'  => $handler,
        ]);

        $this->mockAppleNewsApi->method('getGuzzleClient')->willReturn($guzzleClient);
        $response = $this->mockAppleNewsApi->createArticleNotification('11', ['alertBody' => 'alert']);

        $this->assertTrue($response['ok']);
    }

    public static function providerTestRequestInvalidStatusCode(): array
    {
        /* status code*/
        return [
            [500],
            [501],
            [503],
            [401],
            [403],
            [404],
        ];
    }
}
