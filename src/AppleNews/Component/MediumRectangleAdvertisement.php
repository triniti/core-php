<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Component;

/**
 * @link https://developer.apple.com/documentation/apple_news/medium_rectangle_advertisement
 */
class MediumRectangleAdvertisement extends Component
{
    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        $properties = $this->getSetProperties();
        $properties['role'] = 'medium_rectangle_advertisement';
        return $properties;
    }
}

