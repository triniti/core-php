<?php
declare(strict_types=1);

namespace Triniti\AppleNews\Exception;

use Gdbots\Schemas\Pbjx\Enum\Code;

final class ArticleNotPublished extends \RuntimeException
{
    public function __construct(string $message = 'Article not published.')
    {
        parent::__construct($message, Code::FAILED_PRECONDITION);
    }
}
