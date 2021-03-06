<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Exception;

use Gdbots\Pbj\Exception\HasEndUserMessage;
use Gdbots\Schemas\Pbjx\Enum\Code;

final class ArticleNotPublished extends \RuntimeException implements TrinitiAppleNewsException, HasEndUserMessage
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Article not published.')
    {
        parent::__construct($message, Code::FAILED_PRECONDITION);
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
