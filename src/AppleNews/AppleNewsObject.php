<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

abstract class AppleNewsObject implements \JsonSerializable
{
    public function validate(): void
    {
    }

    protected function getSetProperties(): array
    {
        return array_filter(get_object_vars($this), function ($v) {
            return ($v !== null && $v !== []);
        });
    }
}
