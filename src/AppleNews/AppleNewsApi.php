<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Gdbots\Pbj\Util\ClassUtil;
use Gdbots\Pbjx\Util\StatusCodeUtil;
use Gdbots\Schemas\Pbjx\Enum\HttpCode;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;

class AppleNewsApi
{
    const ENDPOINT = 'https://news-api.apple.com';

    protected ?GuzzleClient $guzzleClient;
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * @link https://developer.apple.com/documentation/apple_news/create_an_article
     *
     * @param string          $channelId
     * @param ArticleDocument $document
     * @param array           $metadata
     *
     * @return array
     */
    public function createArticle(string $channelId, ArticleDocument $document, array $metadata = []): array
    {
        $uri = "/channels/{$channelId}/articles";
        $operation = "POST {$uri}";
        $options = [
            RequestOptions::HEADERS   => [
                'Accept' => 'application/json',
            ],
            RequestOptions::MULTIPART => [
                [
                    'name'     => 'article.json',
                    'filename' => 'article.json',
                    'contents' => json_encode($document),
                    'headers'  => [
                        'Content-Type'        => 'application/json',
                        'Content-Disposition' => 'form-data; name=article.json; filename=article.json',
                    ],
                ],
            ],
        ];

        if (!empty($metadata)) {
            $options[RequestOptions::MULTIPART][] = [
                'name'     => 'metadata',
                'contents' => json_encode(['data' => $metadata]),
                'headers'  => [
                    'Content-Type'        => 'application/json',
                    'Content-Disposition' => 'form-data; name=metadata',
                ],
            ];
        }

        try {
            $response = $this->getGuzzleClient()->post($uri, $options);
            $httpCode = HttpCode::from($response->getStatusCode());
            return [
                'operation' => $operation,
                'ok'        => HttpCode::HTTP_CREATED === $httpCode,
                'code'      => StatusCodeUtil::httpToVendor($httpCode)->value,
                'http_code' => $httpCode->value,
                'response'  => json_decode((string)$response->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            return $this->convertException($e, $operation);
        }
    }

    /**
     * @link https://developer.apple.com/documentation/apple_news/update_an_article
     *
     * @param string          $articleId
     * @param ArticleDocument $document
     * @param array           $metadata
     *
     * @return array
     */
    public function updateArticle(string $articleId, ArticleDocument $document, array $metadata = []): array
    {
        $uri = "/articles/{$articleId}";
        $operation = "POST {$uri}";
        $options = [
            RequestOptions::HEADERS   => [
                'Accept' => 'application/json',
            ],
            RequestOptions::MULTIPART => [
                [
                    'name'     => 'metadata',
                    'contents' => json_encode(['data' => $metadata]),
                    'headers'  => [
                        'Content-Type'        => 'application/json',
                        'Content-Disposition' => 'form-data; name=metadata',
                    ],
                ],
                [
                    'name'     => 'article.json',
                    'filename' => 'article.json',
                    'contents' => json_encode($document),
                    'headers'  => [
                        'Content-Type'        => 'application/json',
                        'Content-Disposition' => 'form-data; name=article.json; filename=article.json',
                    ],
                ],
            ],
        ];

        try {
            $response = $this->getGuzzleClient()->post($uri, $options);
            $httpCode = HttpCode::from($response->getStatusCode());
            return [
                'operation' => $operation,
                'ok'        => HttpCode::HTTP_OK === $httpCode,
                'code'      => StatusCodeUtil::httpToVendor($httpCode)->value,
                'http_code' => $httpCode->value,
                'response'  => json_decode((string)$response->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            return $this->convertException($e, $operation);
        }
    }

    /**
     * @link https://developer.apple.com/documentation/apple_news/delete_an_article
     *
     * @param string $articleId
     *
     * @return array
     */
    public function deleteArticle(string $articleId): array
    {
        $uri = "/articles/{$articleId}";
        $operation = "DELETE {$uri}";
        $options = [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
        ];

        try {
            $response = $this->getGuzzleClient()->delete($uri, $options);
            $httpCode = HttpCode::from($response->getStatusCode());
            return [
                'operation' => $operation,
                'ok'        => HttpCode::HTTP_NO_CONTENT === $httpCode,
                'code'      => StatusCodeUtil::httpToVendor($httpCode)->value,
                'http_code' => $httpCode->value,
                'response'  => [],
            ];
        } catch (\Throwable $e) {
            return $this->convertException($e, $operation);
        }
    }

    /**
     * Creates an article notification request.  No documentation
     * available for this endpoint.
     *
     * @param string $articleId
     * @param array  $data e.g. ['alertBody': 'This is an example alert.', 'countries': ['US', 'GB']]
     *
     * @return array
     */
    public function createArticleNotification(string $articleId, array $data): array
    {
        $uri = "/articles/{$articleId}/notifications";
        $operation = "POST {$uri}";
        $options = [
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::JSON    => [
                'data' => $data,
            ],
        ];

        try {
            $response = $this->getGuzzleClient()->post($uri, $options);
            $httpCode = HttpCode::from($response->getStatusCode());
            return [
                'operation' => $operation,
                'ok'        => HttpCode::HTTP_CREATED === $httpCode,
                'code'      => StatusCodeUtil::httpToVendor($httpCode)->value,
                'http_code' => $httpCode->value,
                'response'  => json_decode((string)$response->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            return $this->convertException($e, $operation);
        }
    }

    protected function convertException(\Throwable $exception, string $operation): array
    {
        if ($exception instanceof RequestException) {
            $httpCode = HttpCode::from($exception->getResponse()->getStatusCode());
            $response = (string)($exception->getResponse()->getBody()->getContents() ?: '{}');
        } else {
            $httpCode = HttpCode::HTTP_INTERNAL_SERVER_ERROR;
            $response = '{}';
        }

        return [
            'operation'     => $operation,
            'ok'            => false,
            'code'          => StatusCodeUtil::httpToVendor($httpCode)->value,
            'http_code'     => $httpCode->value,
            'response'      => json_decode($response, true),
            'error_name'    => ClassUtil::getShortName($exception),
            'error_message' => substr($exception->getMessage(), 0, 2048),
        ];
    }

    protected function getGuzzleClient(): GuzzleClient
    {
        if (null === $this->guzzleClient) {
            $stack = HandlerStack::create();
            $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
                $date = gmdate(\DateTime::ISO8601);
                $contentType = $request->getHeaderLine('Content-Type');
                $stringToSign = $request->getMethod() .
                    $request->getUri() .
                    $date .
                    $contentType .
                    $request->getBody()->getContents();
                $hashed = hash_hmac('sha256', $stringToSign, base64_decode($this->apiSecret), true);
                $signature = (string)rtrim(base64_encode($hashed), "\n");
                $authorization = sprintf(
                    'HHMAC; key=%s; signature=%s; date=%s',
                    $this->apiKey,
                    $signature,
                    $date
                );

                return $request->withHeader('Authorization', $authorization);
            }));

            $this->guzzleClient = new GuzzleClient([
                'base_uri' => self::ENDPOINT,
                'handler'  => $stack,
            ]);
        }

        return $this->guzzleClient;
    }
}
