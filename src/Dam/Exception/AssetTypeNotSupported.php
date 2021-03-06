<?php
declare(strict_types=1);

namespace Triniti\Dam\Exception;

final class AssetTypeNotSupported extends InvalidArgumentException implements TrinitiDamException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'The asset type is not supported.')
    {
        parent::__construct($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getEndUserMessage()
    {
        return $this->getMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getEndUserHelpLink()
    {
        return null;
    }
}
