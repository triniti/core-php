<?php
declare(strict_types=1);

namespace Triniti\Dam\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

class InvalidArgumentException extends \InvalidArgumentException implements TrinitiDamException
{
    /**
     * @param string     $message
     * @param \Throwable $previous
     */
    public function __construct(string $message = 'Invalid argument.', ?\Throwable $previous = null)
    {
        parent::__construct($message, Code::INVALID_ARGUMENT, $previous);
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
