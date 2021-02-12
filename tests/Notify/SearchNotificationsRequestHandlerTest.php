<?php
declare(strict_types=1);

namespace Triniti\Tests\Notify;

use Acme\Schemas\Notify\Request\SearchNotificationsRequestV1;
use Gdbots\Ncr\NcrSearch;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\MessageResolver;
use Gdbots\Pbj\SchemaCurie;
use Gdbots\Pbj\SchemaQName;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\QueryParser\Enum\BoolOperator;
use Gdbots\QueryParser\Node\Field;
use Gdbots\QueryParser\Node\Word;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Triniti\Notify\SearchNotificationsRequestHandler;
use Triniti\Tests\AbstractPbjxTest;

final class SearchNotificationsRequestHandlerTest extends AbstractPbjxTest
{
    public function testHandleRequestParsedQuery(): void
    {
        $ncrSearch = new class implements NcrSearch
        {
            public SearchNotificationsRequestHandlerTest $test;
            public $expectedParsedQueryJson;
            public bool $addStatusField = false;

            public function searchNodes(
                Message $request,
                ParsedQuery $actualParsedQuery,
                Message $response,
                array $qnames = [],
                array $context = []
            ): void {
                $expectedParsedQuery = ParsedQuery::fromArray(json_decode($this->expectedParsedQueryJson, true));
                $this->test->assertFalse(
                    in_array('app_ref', $expectedParsedQuery->getFieldsUsed()),
                    'app_ref should not exist'
                );
                $this->test->assertFalse(
                    in_array('content_ref', $expectedParsedQuery->getFieldsUsed()),
                    'content_ref should not exist'
                );
                $this->test->assertFalse(
                    in_array('send_status', $expectedParsedQuery->getFieldsUsed()),
                    'send_status should not exist'
                );

                if ($this->addStatusField) {
                    $expectedParsedQuery->addNode(
                        new Field(
                            'status',
                            new Word(NodeStatus::DELETED, BoolOperator::PROHIBITED()),
                            BoolOperator::PROHIBITED()
                        )
                    );
                }
                $this->test->assertEquals($expectedParsedQuery, $actualParsedQuery);
            }

            public function createStorage(SchemaQName $qname, array $context = []): void
            {
            }

            public function describeStorage(SchemaQName $qname, array $context = []): string
            {
                return '';
            }

            public function indexNodes(array $nodes, array $context = []): void
            {
            }

            public function deleteNodes(array $nodeRefs, array $context = []): void
            {
            }
        };

        $ncrSearch->test = $this;
        $handler = new SearchNotificationsRequestHandler($ncrSearch);

        $parsedQueryJson = '[{"key1": 1, "type": "number"}, {"key2" : 2, "type": "number"}]';
        $ncrSearch->expectedParsedQueryJson = $parsedQueryJson;

        $request = SearchNotificationsRequestV1::create();
        $request->set('parsed_query_json', $parsedQueryJson);

        $ncrSearch->addStatusField = true;

        $handler->handleRequest($request, $this->pbjx);
        $request = SearchNotificationsRequestV1::create();
        $request->set('parsed_query_json', $parsedQueryJson);
        $request->set('status', NodeStatus::PENDING());

        $ncrSearch->addStatusField = false;

        $handler->handleRequest($request, $this->pbjx);
        $request = SearchNotificationsRequestV1::create();
        $request->set('parsed_query_json', $parsedQueryJson);
        $request->addToSet('statuses', [NodeStatus::PENDING()]);

        $ncrSearch->addStatusField = false;
        $handler->handleRequest($request, $this->pbjx);
    }

    public function testHandleRequestQNames(): void
    {
        $ncrSearch = new class implements NcrSearch
        {
            public SearchNotificationsRequestHandlerTest $test;
            public $expectedQNames;

            public function searchNodes(
                Message $request,
                ParsedQuery $parsedQuery,
                Message $response,
                array $qnames = [],
                array $context = []
            ): void {
                $this->test->assertSame($this->expectedQNames, $qnames);
                $this->test->assertFalse(
                    in_array('app_ref', $parsedQuery->getFieldsUsed()),
                    'app_ref should not exist'
                );
                $this->test->assertFalse(
                    in_array('content_ref', $parsedQuery->getFieldsUsed()),
                    'content_ref should not exist'
                );
                $this->test->assertFalse(
                    in_array('send_status', $parsedQuery->getFieldsUsed()),
                    'send_status should not exist'
                );
            }

            public function createStorage(SchemaQName $qname, array $context = []): void
            {
            }

            public function describeStorage(SchemaQName $qname, array $context = []): string
            {
                return '';
            }

            public function indexNodes(array $nodes, array $context = []): void
            {
            }

            public function deleteNodes(array $nodeRefs, array $context = []): void
            {
            }
        };

        $ncrSearch->test = $this;
        $handler = new SearchNotificationsRequestHandler($ncrSearch);
        $validQNames = [];
        foreach (MessageResolver::findAllUsingMixin('triniti:notify:mixin:notification:v1', false) as $curie) {
            $qname = SchemaCurie::fromString($curie)->getQName();
            $validQNames[$qname->getMessage()] = $qname;
        }

        $ncrSearch->expectedQNames = [$validQNames['android-notification'], $validQNames['apple-news-notification']];
        $request = SearchNotificationsRequestV1::create();
        $request->addToSet('types', ['android-notification', 'apple-news-notification', 'wrong-type-notification']);

        $handler->handleRequest($request, $this->pbjx);
        $request = SearchNotificationsRequestV1::create();
        $request->addToSet('types', ['android-notification', '']);

        $ncrSearch->expectedQNames = [$validQNames['android-notification']];

        $handler->handleRequest($request, $this->pbjx);
        $request = SearchNotificationsRequestV1::create();

        $ncrSearch->expectedQNames = array_values($validQNames);
        $handler->handleRequest($request, $this->pbjx);
    }

    public function testHandleRequestLinkedRef(): void
    {
        $ncrSearch = new class implements NcrSearch
        {
            public SearchNotificationsRequestHandlerTest $test;
            public NodeRef $expectedAppRef;
            public NodeRef $expectedContentRef;

            public function searchNodes(
                Message $request,
                ParsedQuery $parsedQuery,
                Message $response,
                array $qnames = [],
                array $context = []
            ): void {
                $this->test->assertTrue(in_array('app_ref', $parsedQuery->getFieldsUsed()));
                $this->test->assertTrue(in_array('content_ref', $parsedQuery->getFieldsUsed()));

                /** @var Field $field */
                foreach ($parsedQuery->getNodesOfType(Field::NODE_TYPE) as $field) {
                    if ('app_ref' === $field->getName()) {
                        $node = $field->getNode();
                        $this->test->assertInstanceOf(Word::class, $node);
                        $this->test->assertSame(BoolOperator::REQUIRED(), $node->getBoolOperator());
                        $this->test->assertSame((string)$this->expectedAppRef, $node->getValue());

                        continue;
                    }

                    if ('content_ref' === $field->getName()) {
                        $node = $field->getNode();
                        $this->test->assertInstanceOf(Word::class, $node);
                        $this->test->assertSame(BoolOperator::REQUIRED(), $node->getBoolOperator());
                        $this->test->assertSame((string)$this->expectedContentRef, $node->getValue());

                        continue;
                    }

                    return;
                }
                $this->test->assertTrue(false, 'app_ref or content_ref was not parsed correctly.');
            }

            public function createStorage(SchemaQName $qname, array $context = []): void
            {
            }

            public function describeStorage(SchemaQName $qname, array $context = []): string
            {
                return '';
            }

            public function indexNodes(array $nodes, array $context = []): void
            {
            }

            public function deleteNodes(array $nodeRefs, array $context = []): void
            {
            }
        };

        $ncrSearch->test = $this;
        $handler = new SearchNotificationsRequestHandler($ncrSearch);

        $ncrSearch->expectedContentRef = NodeRef::fromString('acme:article:123');
        $ncrSearch->expectedAppRef = NodeRef::fromString('acme:slack-app:456');

        $request = SearchNotificationsRequestV1::create();
        $request->set('app_ref', $ncrSearch->expectedAppRef);
        $request->set('content_ref', $ncrSearch->expectedContentRef);

        $handler->handleRequest($request, $this->pbjx);
    }
}
