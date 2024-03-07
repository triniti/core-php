<?php
declare(strict_types=1);

namespace Triniti\Notify\Notifier;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\Util\ClassUtil;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Pbjx\Enum\Code;
use Triniti\AppleNews\AppleNewsApi;
use Triniti\AppleNews\ArticleDocumentMarshaler;
use Triniti\Notify\Exception\InvalidNotificationContent;
use Triniti\Notify\Exception\RequiredFieldNotSet;
use Triniti\Notify\Notifier;
use Triniti\Schemas\Notify\Enum\NotificationSendStatus;
use Triniti\Schemas\Notify\Enum\SearchNotificationsSort;
use Triniti\Schemas\Notify\NotifierResultV1;
use Triniti\Schemas\Notify\Request\SearchNotificationsRequestV1;
use Triniti\Sys\Flags;

class AppleNewsNotifier implements Notifier
{
    protected Flags $flags;
    protected Key $key;
    protected Pbjx $pbjx;
    protected ArticleDocumentMarshaler $marshaler;
    protected AppleNewsApi $api;
    protected Ncr $ncr;

    public function __construct(Flags $flags, Key $key, Pbjx $pbjx, ArticleDocumentMarshaler $marshaler, Ncr $ncr)
    {
        $this->flags = $flags;
        $this->key = $key;
        $this->pbjx = $pbjx;
        $this->marshaler = $marshaler;
        $this->ncr = $ncr;
    }

    public function send(Message $notification, Message $app, ?Message $content = null): Message
    {
        if (null === $content) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::INVALID_ARGUMENT->value)
                ->set('error_name', 'NullContent')
                ->set('error_message', 'Content cannot be null');
        }

        if ($this->flags->getBoolean('apple_news_notifier_disabled')) {
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', Code::CANCELLED->value)
                ->set('error_name', 'AppleNewsNotifierDisabled')
                ->set('error_message', 'Flag [apple_news_notifier_disabled] is true');
        }

        try {
            $this->createApi($notification, $app, $content);
            $operation = $notification->get('apple_news_operation');
            switch ($operation) {
                case 'create':
                    $result = $this->createArticle($notification, $app, $content);
                    break;

                case 'update':
                    $result = $this->updateArticle($notification, $app, $content);
                    break;

                case 'delete':
                    $result = $this->deleteArticle($notification, $app, $content);
                    break;

                case 'notification':
                    $result = $this->createArticleNotification($notification, $app, $content);
                    break;

                default:
                    throw new InvalidNotificationContent("AppleNews operation [{$operation}] is not supported.");
            }
        } catch (\Throwable $e) {
            $code = $e->getCode() > 0 ? $e->getCode() : Code::UNKNOWN->value;
            return NotifierResultV1::create()
                ->set('ok', false)
                ->set('code', $code)
                ->set('error_name', ClassUtil::getShortName($e))
                ->set('error_message', substr($e->getMessage(), 0, 2048));
        }

        $response = $result['response'] ?? [];
        $result = NotifierResultV1::fromArray($result)
            ->set('raw_response', $response ? json_encode($response) : '{}')
            ->addToMap('tags', 'apple_news_operation', $operation);

        if ($result->get('ok') && isset($response['data'], $response['data']['id'])) {
            $newsId = $response['data']['id'] ?? null;
            $shareUrl = $response['data']['shareUrl'] ?? null;
            $revision = $response['data']['revision'] ?? null;
            $revision = $revision ? StringUtil::urlsafeB64Encode((string)$revision) : $revision;
            $result
                ->addToMap('tags', 'apple_news_id', $newsId)
                ->addToMap('tags', 'apple_news_share_url', $shareUrl)
                ->addToMap('tags', 'apple_news_revision', $revision);
        }

        return $result;
    }

    protected function createArticleNotification(Message $notification, Message $app, Message $article): array
    {
        if (!$article->has('apple_news_id')) {
            throw new RequiredFieldNotSet('Article [apple_news_id] is required');
        }

        return $this->api->createArticleNotification((string)$article->get('apple_news_id'), [
            'alertBody' => $notification->get('body', $article->get('title')),
        ]);
    }

    protected function createArticle(Message $notification, Message $app, Message $article): array
    {
        if (!$app->has('channel_id')) {
            throw new RequiredFieldNotSet('App [channel_id] is required');
        }

        $document = $this->marshaler->marshal($article);
        $metadata = $this->createArticleMetadata($article);
        return $this->api->createArticle($app->get('channel_id'), $document, $metadata);
    }

    protected function updateArticle(Message $notification, Message $app, Message $article): array
    {
        if (!$article->has('apple_news_id')) {
            throw new RequiredFieldNotSet('Article [apple_news_id] is required');
        }

        if (!$article->has('apple_news_revision')) {
            throw new RequiredFieldNotSet('Article [apple_news_revision] is required');
        }

        $document = $this->marshaler->marshal($article);
        $metadata = $this->createArticleMetadata($article);
        $metadata['revision'] = $article->get('apple_news_revision');
        $result = $this->api->updateArticle($article->fget('apple_news_id'), $document, $metadata);

        if ($result['ok']) {
            return $result;
        }

        $code = $result['response']['errors'][0]['code'] ?? null;
        if ('WRONG_REVISION' !== $code) {
            return $result;
        }

        $latestRevision = $this->getLatestRevision($notification);
        if (null !== $latestRevision && $metadata['revision'] !== $latestRevision) {
            $metadata['revision'] = $latestRevision;
            $result = $this->api->updateArticle($article->fget('apple_news_id'), $document, $metadata);
        }

        if ($result['ok']) {
            return $result;
        }

        $getArticleResult = $this->api->getArticle($article->fget('apple_news_id'));
        if (!$getArticleResult['ok']) {
            $this->sendMessageToSlack($article, $notification);
            return $result;
        }

        $revision = $getArticleResult['response']['data']['revision'];
        if ($metadata['revision'] !== $revision) {
            $metadata['revision'] = $revision;
            $result = $this->api->updateArticle($article->fget('apple_news_id'), $document, $metadata);

            if ($result['ok']) {
                return $result;
            }

            $code = $result['response']['errors'][0]['code'] ?? null;
            if ('WRONG_REVISION' === $code) {
                $this->sendMessageToSlack($article, $notification);
            }
        }

        return $result;
    }

    protected function sendMessageToSlack(Message $article, Message $notification): void
    {
        // Override to implement your own Slack message
    }

    protected function deleteArticle(Message $notification, Message $app, Message $article): array
    {
        if (!$article->has('apple_news_id')) {
            throw new RequiredFieldNotSet('Article [apple_news_id] is required');
        }

        return $this->api->deleteArticle((string)$article->get('apple_news_id'));
    }

    protected function createApi(Message $notification, Message $app, Message $article): void
    {
        $this->api = new AppleNewsApi(
            $app->get('api_key'),
            Crypto::decrypt($app->get('api_secret'), $this->key)
        );
    }

    protected function getLatestRevision(Message $notification): ?string
    {
        $request = SearchNotificationsRequestV1::create()
            ->addToSet('types', ['apple-news-notification'])
            ->set('q', '+apple_news_operation:(update OR create)')
            ->set('send_status', NotificationSendStatus::SENT)
            ->set('app_ref', $notification->get('app_ref'))
            ->set('content_ref', $notification->get('content_ref'))
            ->set('ctx_causator_ref', $notification->generateMessageRef())
            ->set('count', 1)
            ->set('sort', SearchNotificationsSort::SENT_AT_DESC);

        $response = $this->pbjx->request($request);
        if (!$response->has('nodes')) {
            return null;
        }

        /** @var Message $result */
        $result = $response->getFromListAt('nodes', 0)->get('notifier_result');
        $revision = $result->getFromMap('tags', 'apple_news_revision');
        return $revision ? StringUtil::urlsafeB64Decode($revision) : $revision;
    }

    protected function createArticleMetadata(Message $article): array
    {
        $sections = $this->createArticleSections($article);
        if (empty($sections)) {
            return [];
        }

        return [
            'links' => [
                'sections' => $sections,
            ],
        ];
    }

    /**
     * @param Message $article
     *
     * @return string[]
     */
    protected function createArticleSections(Message $article): array
    {
        $sections = [];
        $defaultSection = $this->flags->getString('apple_news_default_section_url');
        if ('' !== $defaultSection && $article->get('is_homepage_news')) {
            $sections[] = $defaultSection;
        }

        if (!$article->has('channel_ref')) {
            return $sections;
        }

        try {
            $channel = $this->ncr->getNode($article->get('channel_ref'));
        } catch (\Throwable $e) {
            return $sections;
        }

        if (!$channel->isInMap('tags', 'apple_news_section_url')) {
            return $sections;
        }

        $sections[] = $channel->getFromMap('tags', 'apple_news_section_url');
        return $sections;
    }
}
