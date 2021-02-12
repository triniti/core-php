<?php
declare(strict_types=1);

namespace Triniti\News;

use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Triniti\Schemas\News\Mixin\Article\Article;

trait PbjxHelperTrait
{
    /**
     * @param Node $node
     *
     * @return bool
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof Article;
    }
}
