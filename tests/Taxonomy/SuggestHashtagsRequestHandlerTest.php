<?php
declare(strict_types=1);

namespace Triniti\Tests\Taxonomy;

use Acme\Schemas\Taxonomy\Request\SuggestHashtagsRequestV1;
use Triniti\Taxonomy\HashtagSuggester;
use Triniti\Taxonomy\SuggestHashtagsRequestHandler;
use Triniti\Tests\AbstractPbjxTest;

class InMemoryHashtagSuggester implements HashtagSuggester
{
    public function autocomplete(string $prefix, int $count = 25, array $context = []): array
    {
        $hashtags = ['kanye', 'cool', 'cheese', 'mycutie', 'doggie', 'business', 'sharp',
            'awesome', 'fun', 'loving', 'caring', 'best', 'goat', 'playoff', 'dub', 'wowoweee',
            'alligator', 'handsome', 'gemini', 'lovely', 'thebest', 'best', 'angel', 'canela', 'croc',
            'crocodile', 'theman', 'maneater', 'canada', 'movie', 'stars', 'stardust', 'ageoflove',
            'musicfest', 'musicfestival', 'music', 'musician', 'rain', 'rainman', 'cheese', 'cheesy',
            'cheedar', 'lovable', 'rock', 'rockmusic', 'rocky', 'rocky4', 'angel', 'angelie', 'angelina',
            'kathy', 'kahlua', 'bombay', 'karl', 'awe', 'awesome', 'david', 'joel', 'matthew', 'matt',
            'chamomoille', 'chamu', 'pinoy', 'filam', 'filibuster', 'filibustirismo'];

        $suggestions = [];
        foreach ($hashtags as $hashtag) {
            if (count($suggestions) === $count) {
                break;
            }

            if (str_starts_with($hashtag, $prefix)) {
                $suggestions[$hashtag] = $hashtag;
            }
        }

        return array_values($suggestions);
    }
}

final class SuggestHashtagsRequestHandlerTest extends AbstractPbjxTest
{
    public function testWithResultsExpectedWithPrefix()
    {
        $handler = new SuggestHashtagsRequestHandler(new InMemoryHashtagSuggester());
        $request = SuggestHashtagsRequestV1::create();
        $request->set('count', 3);
        $request->set('prefix', 'mu');
        $response = $handler->handleRequest($request, $this->pbjx);

        $hashtags = $response->get('hashtags');
        $this->assertCount(3, $hashtags);
        $this->assertSame('music', $hashtags[2]);

        $request->set('prefix', 'chamomoill');
        $response = $handler->handleRequest($request, $this->pbjx);
        $hashtags = $response->get('hashtags');
        $this->assertCount(1, $hashtags);
        $this->assertSame('chamomoille', $hashtags[0]);
    }

    public function testNoResultsExpectedWithPrefix()
    {
        $handler = new SuggestHashtagsRequestHandler(new InMemoryHashtagSuggester());
        $response = $handler->handleRequest(SuggestHashtagsRequestV1::create()->set('prefix', 'java'), $this->pbjx);
        $this->assertFalse($response->has('hashtags'));
    }

    public function testNoResultsExpected()
    {
        $handler = new SuggestHashtagsRequestHandler(new InMemoryHashtagSuggester());
        $response = $handler->handleRequest(SuggestHashtagsRequestV1::create(), $this->pbjx);

        $this->assertFalse($response->has('hashtags'));
    }
}
