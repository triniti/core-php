<?php
declare(strict_types=1);

namespace Triniti\OvpJwplayer;

use Gdbots\Ncr\AggregateResolver;
use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\CommandHandler;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\Schemas\Pbjx\Enum\HttpCode;
use Gdbots\UriTemplate\UriTemplateService;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\RequestException;
use Triniti\Dam\UrlProvider;
use Triniti\Ovp\ArtifactUrlProvider;
use Triniti\Ovp\VideoAggregate;
use Triniti\OvpJwplayer\Exception\JwplayerMediaNotSynced;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Sys\Flags;

class SyncMediaHandler implements CommandHandler
{
    const DISABLED_FLAG_NAME = 'jwplayer_sync_disabled';
    const ENDPOINT = 'https://api.jwplatform.com/v1';
    const VALID_EXTENSION_REGEX = '/^(aac|flv|m3u8|mp4|mpd|mpeg|ogg|smil|webm)$/';

    protected string $key;
    protected string $secret;
    protected Ncr $ncr;
    protected Flags $flags;
    protected UrlProvider $urlProvider;
    protected ArtifactUrlProvider $artifactUrlProvider;
    protected GuzzleClientInterface $guzzleClient;

    public static function handlesCuries(): array
    {
        return ['triniti:ovp.jwplayer:command:sync-media'];
    }

    public function __construct(
        string $key,
        string $secret,
        Ncr $ncr,
        UrlProvider $urlProvider,
        ArtifactUrlProvider $artifactUrlProvider,
        Flags $flags,
        ?GuzzleClientInterface $guzzleClient = null
    ) {
        $this->key = $key;
        $this->secret = $secret;
        $this->ncr = $ncr;
        $this->urlProvider = $urlProvider;
        $this->artifactUrlProvider = $artifactUrlProvider;
        $this->flags = $flags;
        $this->guzzleClient = $guzzleClient ?: new GuzzleClient();
    }

    public function handleCommand(Message $command, Pbjx $pbjx): void
    {
        if ($this->flags->getBoolean(self::DISABLED_FLAG_NAME)) {
            return;
        }

        $node = $this->getSupportedNode($command, $pbjx);
        if (!$node) {
            return;
        }

        $retries = $command->get('ctx_retries');
        if ($retries >= 5) {
            throw new JwplayerMediaNotSynced(sprintf(
                'The Jwplayer Media was not synced: failed to sync after [%s] retries. node_ref: [%s].',
                $retries,
                $node->generateNodeRef()
            ));
        }

        if ($node->fget('status') === NodeStatus::DELETED && $node->has('jwplayer_media_id')) {
            $this->deleteVideo($command, $pbjx, $node);
            return;
        }

        if (!$node->has('jwplayer_media_id')) {
            $parameters = $this->marshalParameters($node);
            $mediaId = $this->createVideo($command, $pbjx, $node, $parameters);
            if (!$mediaId) {
                return; // rate limited, retry already sent in createVideo
            }
        } else {
            $mediaId = $node->get('jwplayer_media_id');
            $media = $this->getExistingMedia($command, $pbjx, $node);
            if (!$media) {
                // rate limited, retry already sent in getExistingMedia
                return;
            }

            $parameters = $this->marshalParameters($node, $media);
            $parameters['video_key'] = $mediaId;
            if (!$this->updateVideo($command, $pbjx, $node, $parameters)) {
                // rate limited, retry already sent in updateVideo
                return;
            }
        }

        $eventFields = [];
        $syncCaptions = $command->isInSet('fields', 'captions') && $node->has('caption_urls');
        $captionKeyMap = null;

        if ($syncCaptions) {
            $captionKeyMap = $this->syncCaptions($command, $pbjx, $node->get('caption_urls'), $mediaId);
            if ($captionKeyMap) {
                $eventFields['jwplayer_caption_keys'] = $captionKeyMap;
            }
        }

        $syncThumbnail = $command->isInSet('fields', 'thumbnail')
            && ($node->has('poster_image_ref') || $node->has('image_ref'))
            && ($captionKeyMap || !$syncCaptions); // if captions failed the retry was already sent in syncCaptions
        if ($syncThumbnail) {
            $imageId = AssetId::fromString($node->get('poster_image_ref', $node->get('image_ref'))->getId());
            $imageUrl = $this->urlProvider->getUrl($imageId);
            $thumbnailWasSynced = $this->syncThumbnail($command, $pbjx, $imageUrl, $mediaId);
            if ($thumbnailWasSynced) {
                $eventFields['thumbnail_ref'] = $node->get('poster_image_ref', $node->get('image_ref'));
            }
        }

        $this->syncMedia($command, $pbjx, $node, $eventFields, $mediaId);
    }

    protected function createVideo(Message $command, Pbjx $pbjx, Message $node, array $parameters): ?string
    {
        $qString = $this->createAuthenticatedQString($parameters);
        try {
            $response = $this->guzzleClient->post(self::ENDPOINT . '/videos/create' . $qString);
        } catch (RequestException $re) {
            if ($re->hasResponse() && $re->getResponse()->hasHeader('X-RateLimit-Reset')) {
                $timestamp = (int)($re->getResponse()->getHeader('X-RateLimit-Reset')[0] + rand(5, 15));
            } else {
                $timestamp = strtotime('+' . rand(5, 15) . ' seconds');
            }

            $this->retry($command, $pbjx, $timestamp);
            return null;
        } catch (\Throwable $e) {
            throw $e;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== HttpCode::HTTP_OK) {
            throw new JwplayerMediaNotSynced(sprintf(
                'The Jwplayer Media was not synced: failed to create Jwplayer Media. node_ref: [%s]. Status code: [%s]. Reason: ["%s"]',
                $node->generateNodeRef(),
                $statusCode,
                $response->getReasonPhrase() ?: 'none given'
            ));
        }

        return unserialize($response->getBody()->getContents())['video']['key'];
    }

    protected function updateVideo(Message $command, Pbjx $pbjx, Message $node, array $parameters): bool
    {
        $qString = $this->createAuthenticatedQString($parameters);
        try {
            $response = $this->guzzleClient->post(self::ENDPOINT . '/videos/update' . $qString);
        } catch (RequestException $re) {
            if ($re->hasResponse() && $re->getResponse()->hasHeader('X-RateLimit-Reset')) {
                $timestamp = (int)($re->getResponse()->getHeader('X-RateLimit-Reset')[0] + rand(5, 15));
            } else {
                $timestamp = strtotime('+' . rand(5, 15) . ' seconds');
            }

            $this->retry($command, $pbjx, $timestamp);
            return false;
        } catch (\Throwable $e) {
            throw $e;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== HttpCode::HTTP_OK) {
            throw new JwplayerMediaNotSynced(sprintf(
                'The Jwplayer Media was not synced: failed to update Jwplayer Media. node_ref: [%s]. Status Code: [%s]. Reason: ["%s"]',
                $node->generateNodeRef(),
                $statusCode,
                $response->getReasonPhrase() ?: 'none given'
            ));
        }

        return true;
    }

    protected function deleteVideo(Message $command, Pbjx $pbjx, Message $node): void
    {
        $qString = $this->createAuthenticatedQString([
            'video_key' => $node->get('jwplayer_media_id'),
        ]);

        try {
            $response = $this->guzzleClient->post(self::ENDPOINT . '/videos/delete' . $qString);
        } catch (RequestException $re) {
            if ($re->hasResponse() && $re->getResponse()->hasHeader('X-RateLimit-Reset')) {
                $timestamp = (int)($re->getResponse()->getHeader('X-RateLimit-Reset')[0] + rand(5, 15));
            } else {
                $timestamp = strtotime('+' . rand(5, 15) . ' seconds');
            }

            $this->retry($command, $pbjx, $timestamp);
            return;
        } catch (\Throwable $e) {
            throw $e;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== HttpCode::HTTP_OK) {
            throw new JwplayerMediaNotSynced(sprintf(
                'The Jwplayer Media was not synced: failed to delete Jwplayer Media. node_ref: [%s]. Status Code: [%s]. Reason: ["%s"]',
                $node->generateNodeRef(),
                $statusCode,
                $response->getReasonPhrase() ?: 'none given'
            ));
        }

        $this->syncMedia($command, $pbjx, $node);
    }

    protected function syncThumbnail(Message $command, Pbjx $pbjx, string $imageUrl, string $mediaId): bool
    {
        // todo: use guzzle sink for file downloads
        $imageResponse = $this->guzzleClient->get($imageUrl);
        $image = $imageResponse->getBody()->getContents();

        $qString = $this->createAuthenticatedQString([
            'video_key' => $mediaId,
            'md5'       => md5($image),
            'size'      => $imageResponse->getHeader('Content-Length')[0],
        ]);

        try {
            $response = $this->guzzleClient->post(self::ENDPOINT . '/videos/thumbnails/update' . $qString);
        } catch (RequestException $re) {
            if ($re->hasResponse() && $re->getResponse()->hasHeader('X-RateLimit-Reset')) {
                $timestamp = (int)($re->getResponse()->getHeader('X-RateLimit-Reset')[0] + rand(5, 15));
            } else {
                $timestamp = strtotime('+' . rand(5, 15) . ' seconds');
            }

            $newCommand = clone $command;
            $newCommand->removeFromSet('fields', ['captions']); // if we made it this far captions already synced
            $this->retry($newCommand, $pbjx, $timestamp);
            return false;
        } catch (\Throwable $e) {
            throw $e;
        }

        $linkData = unserialize($response->getBody()->getContents())['link'];
        $uploadUrl = $this->createUploadUrl($linkData);
        $this->guzzleClient->post($uploadUrl, [
            'multipart' => [[
                'name'     => 'file',
                'contents' => $image,
                'filename' => $imageUrl,
            ]],
        ]);

        return true;
    }

    protected function syncCaptions(Message $command, Pbjx $pbjx, array $captions, string $mediaId): ?array
    {
        $qString = $this->createAuthenticatedQString([
            'video_key'    => $mediaId,
            'kinds_filter' => 'captions',
        ]);

        try {
            $response = $this->guzzleClient->post(self::ENDPOINT . '/videos/tracks/list' . $qString);
        } catch (RequestException $re) {
            if ($re->hasResponse() && $re->getResponse()->hasHeader('X-RateLimit-Reset')) {
                $timestamp = (int)($re->getResponse()->getHeader('X-RateLimit-Reset')[0] + rand(5, 15));
            } else {
                $timestamp = strtotime('+' . rand(5, 15) . ' seconds');
            }

            $this->retry($command, $pbjx, $timestamp);
            return null;
        } catch (\Throwable $e) {
            throw $e;
        }

        $tracks = unserialize($response->getBody()->getContents())['tracks'];
        foreach ($tracks as $track) {
            $qString = $this->createAuthenticatedQString([
                'track_key' => $track['key'],
            ]);
            try {
                $this->guzzleClient->post(self::ENDPOINT . '/videos/tracks/delete' . $qString);
            } catch (RequestException $re) {
                if ($re->hasResponse() && $re->getResponse()->hasHeader('X-RateLimit-Reset')) {
                    $timestamp = (int)($re->getResponse()->getHeader('X-RateLimit-Reset')[0] + rand(5, 15));
                } else {
                    $timestamp = strtotime('+' . rand(5, 15) . ' seconds');
                }

                $this->retry($command, $pbjx, $timestamp);
                return null;
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        $captionKeyMap = [];
        foreach ($captions as $language => $url) {
            $captionResponse = $this->guzzleClient->get($url);
            $caption = $captionResponse->getBody()->getContents();
            $qString = $this->createAuthenticatedQString([
                'video_key' => $mediaId,
                'kind'      => 'captions',
                'label'     => $language,
                'language'  => $language,
                'md5'       => md5($caption),
                'size'      => $captionResponse->getHeader('Content-Length')[0],
            ]);

            try {
                $response = $this->guzzleClient->post(self::ENDPOINT . '/videos/tracks/create' . $qString);
            } catch (RequestException $re) {
                if ($re->hasResponse() && $re->getResponse()->hasHeader('X-RateLimit-Reset')) {
                    $timestamp = (int)($re->getResponse()->getHeader('X-RateLimit-Reset')[0] + rand(5, 15));
                } else {
                    $timestamp = strtotime('+' . rand(5, 15) . ' seconds');
                }

                $this->retry($command, $pbjx, $timestamp);
                return null;
            } catch (\Throwable $e) {
                throw $e;
            }

            $linkData = unserialize($response->getBody()->getContents())['link'];
            $uploadUrl = $this->createUploadUrl($linkData);
            $uploadResponse = $this->guzzleClient->post($uploadUrl, [
                'multipart' => [[
                    'name'     => 'file',
                    'contents' => $caption,
                    'filename' => $url,
                ]],
            ]);

            $captionKey = unserialize($uploadResponse->getBody()->getContents())['media']['key'];
            $captionKeyMap[$language] = $captionKey;
        }

        return $captionKeyMap;
    }

    protected function retry(Message $command, Pbjx $pbjx, int $timestamp): void
    {
        $newCommand = clone $command;
        $newCommand->set('ctx_retries', 1 + $newCommand->get('ctx_retries'));
        $pbjx->copyContext($command, $newCommand);
        $jobId = $command->get('node_ref') . '.sync-jwplayer-media';
        $pbjx->sendAt($newCommand, $timestamp, $jobId);
    }

    protected function getSupportedNode(Message $command, Pbjx $pbjx): ?Message
    {
        /** @var NodeRef $nodeRef */
        $nodeRef = $command->get('node_ref');
        $context = ['causator' => $command];

        try {
            $node = $this->ncr->getNode($nodeRef, true, $context);
            $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNode($node, $pbjx);
        } catch (NodeNotFound $nf) {
            $aggregate = AggregateResolver::resolve($nodeRef->getQName())::fromNodeRef($nodeRef, $pbjx);
        } catch (\Throwable $e) {
            throw $e;
        }

        $aggregate->sync($context);
        $node = $aggregate->getNode();

        if (
            $node->get('jwplayer_sync_enabled')
            && (
                $node->has('live_m3u8_url')
                || $node->has('mezzanine_ref')
                || $node->has('kaltura_mp4_url')
                || $node->has('kaltura_flavors')
                || ($node->has('mezzanine_url') && preg_match(self::VALID_EXTENSION_REGEX, pathinfo($node->get('mezzanine_url'), PATHINFO_EXTENSION)))
            )
        ) {
            return $node;
        }

        return null;
    }

    protected function getExistingMedia(Message $command, Pbjx $pbjx, Message $node): ?array
    {
        $mediaId = $node->get('jwplayer_media_id');
        $qString = $this->createAuthenticatedQString([
            'video_key' => $mediaId,
        ]);

        try {
            $response = $this->guzzleClient->get(self::ENDPOINT . '/videos/show' . $qString);
        } catch (RequestException $re) {
            if ($re->hasResponse() && $re->getResponse()->hasHeader('X-RateLimit-Reset')) {
                $timestamp = (int)($re->getResponse()->getHeader('X-RateLimit-Reset')[0] + rand(5, 15));
            } else {
                $timestamp = strtotime('+' . rand(5, 15) . ' seconds');
            }

            $this->retry($command, $pbjx, $timestamp);
            return null;
        } catch (\Throwable $e) {
            throw $e;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== HttpCode::HTTP_OK) {
            throw new JwplayerMediaNotSynced(sprintf(
                'The Jwplayer Media was not synced: failed to GET existing jwplayer media. node_ref: [%s]. jwplayer_media_id: [%s]. Status code: [%s]. Reason: ["%s"]',
                $node->generateNodeRef(),
                $mediaId,
                $statusCode,
                $response->getReasonPhrase() ?: 'none given'
            ));
        }

        return unserialize($response->getBody()->getContents());
    }

    protected function marshalParameters(Message $node, ?array $media = null): array
    {
        /**
         * fixme: this method is a bit mixed up as it both marshals the parameters for a video and also
         * "removes" the existing parameters. would be better to just marshal for the video and then use
         * a separate process for removing existing.
         *
         * fixme: would also be a good idea to split this into separate methods that would be easier to
         * override at the site level, potentially turn it into a separate transformer as well.
         */
        $sourceUrl = null;
        $sourceFormat = null;

        if ($node->has('live_m3u8_url')) {
            $sourceUrl = $node->get('live_m3u8_url');
            $sourceFormat = 'm3u8';
        } else if ($node->has('mezzanine_ref')) {
            $mezzanineId = AssetId::fromString($node->get('mezzanine_ref')->getId());
            $sourceUrl = $this->artifactUrlProvider->getManifest($mezzanineId);
            $sourceFormat = 'm3u8';
        } else if ($node->has('kaltura_mp4_url') || $node->has('kaltura_flavors')) {
            $sourceUrl = $this->getMp4FromKaltura($node);
            $sourceFormat = 'mp4';
        } else if ($node->has('mezzanine_url')) {
            $sourceUrl = $node->get('mezzanine_url');
            $sourceFormat = pathinfo($sourceUrl, PATHINFO_EXTENSION);
        }

        if (null === $sourceUrl) {
            throw new JwplayerMediaNotSynced(sprintf(
                'The Jwplayer Media was not synced: failed to derive url and/or mime type. node_ref: [%s]. live_m3u8_url: ["%s"]. mezzanine_ref: [%s]. kaltura_mp4_url: [%s]. mezzanine_url: ["%s"].',
                $node->generateNodeRef(),
                $node->get('live_m3u8_url', 'null'),
                $node->get('mezzanine_ref', 'null'),
                $node->get('kaltura_mp4_url', 'null'),
                $node->get('mezzanine_url', 'null'),
            ));
        }

        $parameters = [
            'author'       => $node::schema()->getQName()->getVendor(),
            'description'  => $node->get('description', $node->get('title')),
            'duration'     => $node->get('duration'),
            'link'         => UriTemplateService::expand("{$node::schema()->getQName()}.canonical", $node->getUriTemplateVars()),
            'sourceformat' => $sourceFormat,
            'sourcetype'   => 'url',
            'sourceurl'    => $sourceUrl,
            'title'        => $node->get('title'),
        ];

        $parameters['date'] = NodeStatus::PUBLISHED->value === $node->fget('status')
            ? $node->get('published_at')->getTimeStamp()
            : $node->get('created_at')->getSeconds();

        $isUpdate = !empty($media);
        if ($isUpdate) { // cannot send null param on create
            $parameters['expires_date'] = $node->has('expires_at')
                ? $node->get('expires_at')->getTimestamp()
                : null;
        } else if ($node->has('expires_at')) {
            $parameters['expires_date'] = $node->get('expires_at')->getTimestamp();
        }

        $tags = [
            'id:' . $node->get('_id'),
            'is_unlisted:' . ($node->get('is_unlisted') ? 'true' : 'false'),
            'status:' . $node->get('status'),
        ];

        $refs = array_unique(array_merge(
            $node->get('category_refs', []),
            $node->get('person_refs', []),
            $node->get('primary_person_refs', [])
        ));

        foreach ($this->ncr->getNodes($refs) as $n) {
            $tags[] = $n::schema()->getCurie()->getMessage() . ':' . $n->get('slug');
        }

        foreach ($node->get('hashtags', []) as $hashtag) {
            $tags[] = 'hashtag:' . strtolower($hashtag);
        }

        foreach (['mpm', 'show'] as $field) {
            if ($node->has($field)) {
                $tags[] = $field . ':' . $node->get($field);
            }
        }

        $parameters['tags'] = implode(',', $tags);

        if (!$node->has('channel_ref')) {
            if ($isUpdate) { // cannot send removal param on create
                $parameters['custom.-channel_slug'] = 'noop'; // the minus in the key name removes the param entirely
            }
        } else {
            $n = null;
            try {
                $n = $this->ncr->getNode($node->get('channel_ref'));
                $parameters['custom.channel_slug'] = $n->get('slug');
            } catch (NodeNotFound $nf) {
                if ($isUpdate) {
                    $parameters['custom.-channel_slug'] = 'noop';
                }
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        $customParameterBooleanFields = [
            'ads_enabled',
            'is_full_episode',
            'is_live',
            'is_promo',
            'is_unlisted',
            'sharing_enabled',
        ];
        $deterministicCustomParameters = array_merge($customParameterBooleanFields, [
            'channel_slug',
            'has_music',
            'id',
            'status',
        ]);
        $existingCustomParameters = isset($media['video']['custom']) ? $media['video']['custom'] : [];

        foreach ($existingCustomParameters as $key => $value) {
            if (in_array($key, $deterministicCustomParameters)) {
                continue;
            }
            // remove any existing custom params, they will be re-added next if they exist
            $parameters['custom.-' . $key] = 'noop';
        }

        foreach ($customParameterBooleanFields as $field) {
            $parameters['custom.' . $field] = $node->get($field) ? 'true' : 'false';
        }

        $parameters['custom.id'] = $node->get('_id');
        $parameters['custom.status'] = $node->get('status');
        $parameters['custom.has_music'] = $node->get('has_music');

        foreach ($node->get('tags', []) as $key => $value) {
            $parameters['custom.' . $key] = $value;
        }

        foreach (['mpm', 'show', 'tvpg_rating'] as $field) {
            if ($node->has($field)) {
                $parameters['custom.' . $field] = $node->get($field);
            }
        }

        return $parameters;
    }

    protected function getMp4FromKaltura(Message $node): ?string
    {
        if ($node->has('kaltura_mp4_url')) {
            $url = $node->get('kaltura_mp4_url');
            $ext = strtolower((string)pathinfo($url, PATHINFO_EXTENSION));
            if ('mp4' === $ext && $this->canUseMp4FromKaltura($url)) {
                return str_replace('http://', 'https://', $url);
            }
        }

        /** @var Message $flavor */
        foreach ($node->get('kaltura_flavors', []) as $flavor) {
            if ('mp4' !== $flavor->get('file_ext') || !$flavor->has('url')) {
                continue;
            }

            $url = $flavor->get('url');
            if ($this->canUseMp4FromKaltura($url)) {
                return str_replace('http://', 'https://', $url);
            }
        }

        return null;
    }

    protected function canUseMp4FromKaltura(string $url): bool
    {
        // override to customize, e.g. don't allow kaltura.com hosted urls
        // return false === strpos($url, 'kaltura.com');
        return true;
    }

    protected function createAuthenticatedQString(array $parameters): string
    {
        $parameters['api_nonce'] = mt_rand(10000000, 99999999);
        $parameters['api_timestamp'] = time();
        $parameters['api_key'] = $this->key;
        $parameters['api_format'] = 'php'; // fixme: should use json here to avoid unserializing potentially malicious php

        $encodedParameters = [];
        foreach ($parameters as $key => $value) {
            $encodedParameters[rawurlencode((string)$key)] = rawurlencode((string)$value);
        }
        ksort($encodedParameters);
        $qString = '';
        foreach ($encodedParameters as $key => $value) {
            $qString .= sprintf('%s=%s%s', $key, $value, '&');
        }
        $qStringWithSecret = preg_replace('/&$/', $this->secret, $qString);
        $apiSignature = sha1($qStringWithSecret);
        $qString .= 'api_signature=' . $apiSignature;
        return '?' . $qString;
    }

    protected function createUploadUrl(array $linkData): string
    {
        return sprintf(
            '%s://%s%s?api_format=php&key=%s&token=%s',
            $linkData['protocol'],
            $linkData['address'],
            $linkData['path'],
            $linkData['query']['key'],
            $linkData['query']['token']
        );
    }

    protected function syncMedia(
        Message $command,
        Pbjx $pbjx,
        Message $node,
        array $fields = [],
        ?string $mediaId = null
    ): void {
        /** @var VideoAggregate $aggregate */
        $aggregate = AggregateResolver::resolve($node->generateNodeRef()->getQName())::fromNode($node, $pbjx);
        $aggregate->syncMedia($command, $fields, $mediaId);
        $aggregate->commit(['causator' => $command]);
    }
}
