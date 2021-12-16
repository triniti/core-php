<?php
declare(strict_types=1);

namespace Triniti\Curator;

use Gdbots\Ncr\AbstractSearchNodesRequestHandler;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbjx\Pbjx;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Enum\ComparisonOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Numbr;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;

class SearchPromotionsRequestHandler extends AbstractSearchNodesRequestHandler
{
    protected \DateTimeZone $timeZone;

    public static function handlesCuries(): array
    {
        // deprecated mixins, will be removed in 4.x
        $curies = MessageResolver::findAllUsingMixin('triniti:curator:mixin:search-promotions-request:v1', false);
        $curies[] = 'triniti:curator:request:search-promotions-request';
        return $curies;
    }

    public function __construct(NcrSearch $ncrSearch, ?string $timeZone = null)
    {
        parent::__construct($ncrSearch);
        $this->timeZone = new \DateTimeZone($timeZone ?: 'UTC');
    }

    protected function beforeSearchNodes(Message $request, ParsedQuery $parsedQuery): void
    {
        parent::beforeSearchNodes($request, $parsedQuery);
        $required = BoolOperator::REQUIRED;

        if ($request->has('slot')) {
            $parsedQuery->addNode(
                new Field(
                    'slot',
                    new Word((string)$request->get('slot'), $required),
                    $required
                )
            );
        }

        if ($request->has('render_at')) {
            /** @var \DateTime|\DateTimeImmutable $renderAt */
            $renderAt = $request->get('render_at');
            $renderAt = $renderAt->setTimezone($this->timeZone);

            $sod = (float)((int)strtotime("{$renderAt->format('H:i:s')}UTC", 0));
            $dow = strtolower($renderAt->format('D'));

            // This expects a NodeMapper for elasticsearch (or whatever provider the NcrSearch is
            // configured to use) storing the d__{$dow}_start_at and d__{$dow}_end_at as seconds
            // since midnight rather than the human friendly HH:MM:SS values in {$dow}_start_at
            // and {$dow}_end_at. the "d__" prefix is used to signify a derived field that is
            // created at index time.  it is not actually a part of the schema on the promotion.
            $parsedQuery
                ->addNode(
                    new Field(
                        "d__{$dow}_start_at",
                        new Numbr($sod, ComparisonOperator::LTE),
                        $required
                    )
                )->addNode(
                    new Field(
                        "d__{$dow}_end_at",
                        new Numbr($sod, ComparisonOperator::GTE),
                        $required
                    )
                );
        }
    }

    protected function createSearchNodesResponse(Message $request, Pbjx $pbjx): Message
    {
        return MessageResolver::resolveCurie('*:curator:request:search-promotions-response:v1')::create();
    }
}
