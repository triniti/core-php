<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

abstract class AppleNewsObject implements \JsonSerializable
{
    /**
     * @throws \Throwable
     */
    public function validate(): void
    {
    }

    /**
     * @return array
     */
    protected function getSetProperties(): array
    {
        $properties = array_filter(get_object_vars($this), function ($v) {
            return ($v !== null && $v !== []);
        });

        return $properties;
    }
}
