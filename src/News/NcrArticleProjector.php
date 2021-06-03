<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Ncr\NcrProjector;

class NcrArticleProjector extends NcrProjector
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:news:mixin:article-slotting-removed'  => 'onNodeEvent',
            'triniti:news:event:article-slotting-removed'  => 'onNodeEvent',
            'triniti:news:mixin:apple-news-article-synced' => 'onNodeEvent',
            'triniti:news:event:apple-news-article-synced' => 'onNodeEvent',
        ];
    }
}
