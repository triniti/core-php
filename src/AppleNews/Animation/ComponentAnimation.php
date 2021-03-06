<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Animation;

use Triniti\AppleNews\AppleNewsObject;

/**
 * @link https://developer.apple.com/documentation/apple_news/component_animation
 */
abstract class ComponentAnimation extends AppleNewsObject
{
    /** @var bool */
    protected $userControllable = false;

    /**
     * @return bool
     */
    public function getUserControllable(): bool
    {
        return $this->userControllable;
    }

    /**
     * @param bool $userControllable
     *
     * @return static
     */
    public function setUserControllable(bool $userControllable = false): self
    {
        $this->userControllable = $userControllable;
        return $this;
    }
}
