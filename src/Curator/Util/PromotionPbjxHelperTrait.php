<?php
declare(strict_types=1);

namespace Triniti\Curator\Util;

use Gdbots\Schemas\Ncr\Mixin\Node\Node;
use Triniti\Schemas\Curator\Mixin\Promotion\Promotion;

trait PromotionPbjxHelperTrait
{
    /**
     * @param Node $node
     *
     * @return bool
     */
    protected function isNodeSupported(Node $node): bool
    {
        return $node instanceof Promotion;
    }
}