<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbj\WellKnown\NodeRef;

/**
 * Util for applying field values from a teaserable to a teaser. Useful for
 * the SyncTeaser command and the like. Intentionally does not transfer order_date.
 * If a teaser is not supplied, a new one will be created, transformed, and returned.
 */
class TeaserTransformer
{
    public static function transform(Message $target, ?Message $teaser = null): Message
    {
        $targetRef = NodeRef::fromNode($target);

        if (null === $teaser) {
            static $teaserClass = null;
            if (null === $teaserClass) {
                $teaserClass = MessageResolver::resolveCurie(
                    SchemaCurie::fromstring("{$targetRef->getVendor()}:curator:node:{$targetRef->getLabel()}-teaser")
                );
            }

            $teaser = $teaserClass::create()->set('sync_with_target', true);
        }

        $teaser->set('target_ref', $targetRef);

        foreach ($target::schema()->getMixins() as $mixin) {
            if (1 === preg_match(SchemaCurie::VALID_PATTERN, $mixin)) {
                $message = SchemaCurie::fromString($mixin)->getMessage();
                $method = sprintf('transform%s', StringUtil::toCamelFromSlug($message));
                if (method_exists(static::class, $method)) {
                    static::$method($target, $teaser);
                }
            }
        }

        static::transformDescription($target, $teaser);
        static::transformSingleField($target, $teaser, 'image_ref');
        return $teaser;
    }

    protected static function transformSingleField(Message $target, Message $teaser, string $fieldName): void
    {
        if (!$teaser::schema()->hasField($fieldName)) {
            return;
        }

        $teaser->clear($fieldName);
        if ($target->has($fieldName)) {
            $teaser->set($fieldName, $target->get($fieldName));
        }
    }

    protected static function transformSetField(Message $target, Message $teaser, string $fieldName): void
    {
        if (!$teaser::schema()->hasField($fieldName)) {
            return;
        }

        $teaser->clear($fieldName);
        if ($target->has($fieldName)) {
            $teaser->addToSet($fieldName, $target->get($fieldName));
        }
    }

    protected static function transformMapField(Message $target, Message $teaser, string $fieldName): void
    {
        if (!$teaser::schema()->hasField($fieldName)) {
            return;
        }

        $teaser->clear($fieldName);
        if ($target->has($fieldName)) {
            foreach ($target->get($fieldName) as $key => $value) {
                $teaser->addToMap($fieldName, $key, $value);
            }
        }
    }

    protected static function transformAdvertising(Message $target, Message $teaser): void
    {
        static::transformSingleField($target, $teaser, 'ads_enabled');
        static::transformSingleField($target, $teaser, 'dfp_ad_unit_path');
        static::transformMapField($target, $teaser, 'dfp_cust_params');
    }

    protected static function transformAsset(Message $target, Message $teaser): void
    {
        static::transformSingleField($target, $teaser, 'credit');
        static::transformSingleField($target, $teaser, 'credit_url');
        static::transformSingleField($target, $teaser, 'cta_text');
    }

    protected static function transformCategorizable(Message $target, Message $teaser): void
    {
        static::transformSetField($target, $teaser, 'category_refs');
    }

    protected static function transformDescription(Message $target, Message $teaser): void
    {
        $description = $target->get('description', $target->get('meta_description'));

        if (empty($description) && $target->has('blocks')) {
            /** @var Message $block */
            foreach ($target->get('blocks') as $block) {
                if (
                    $block::schema()->hasMixin('triniti:canvas:mixin:text-block')
                    && $block->has('text')
                ) {
                    $description = strip_tags($block->get('text'));
                    break;
                }
            }
        }

        $teaser->set('description', $description);
    }

    protected static function transformGallery(Message $target, Message $teaser): void
    {
        static::transformSingleField($target, $teaser, 'credit');
        static::transformSingleField($target, $teaser, 'credit_url');
        $teaser->set('cta_text', $target->get('launch_text', $teaser->get('cta_text')));
    }

    protected static function transformHasChannel(Message $target, Message $teaser): void
    {
        static::transformSingleField($target, $teaser, 'channel_ref');
    }

    protected static function transformHashtaggable(Message $target, Message $teaser): void
    {
        static::transformSetField($target, $teaser, 'hashtags');
    }

    protected static function transformHasPeople(Message $target, Message $teaser): void
    {
        static::transformSetField($target, $teaser, 'person_refs');
        static::transformSetField($target, $teaser, 'primary_person_refs');
    }

    protected static function transformImageAsset(Message $target, Message $teaser): void
    {
        if ($teaser->has('image_ref')) {
            return;
        }

        $teaser->set('image_ref', NodeRef::fromNode($target));
    }

    protected static function transformNode(Message $target, Message $teaser): void
    {
        $teaser->set('title', $target->get('display_title', $target->get('title', $teaser->get('title'))));
    }

    protected static function transformSeo(Message $target, Message $teaser): void
    {
        foreach ([
                     'is_unlisted',
                     'meta_description',
                     'seo_image_ref',
                     'seo_published_at',
                     'seo_updated_at',
                     'seo_title',
                 ] as $fieldName) {
            static::transformSingleField($target, $teaser, $fieldName);
        }

        static::transformSetField($target, $teaser, 'meta_keywords');
    }

    protected static function transformSponsorable(Message $target, Message $teaser): void
    {
        static::transformSingleField($target, $teaser, 'sponsor_ref');
    }

    protected static function transformSwipeable(Message $target, Message $teaser): void
    {
        static::transformSingleField($target, $teaser, 'swipe');
    }

    protected static function transformTaggable(Message $target, Message $teaser): void
    {
        static::transformMapField($target, $teaser, 'tags');
    }

    protected static function transformThemeable(Message $target, Message $teaser): void
    {
        static::transformSingleField($target, $teaser, 'theme');
    }

    protected static function transformVideo(Message $target, Message $teaser): void
    {
        static::transformSingleField($target, $teaser, 'credit');
        $teaser->set('cta_text', $target->get('launch_text', $teaser->get('cta_text')));
    }
}
