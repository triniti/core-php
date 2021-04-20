<?php
declare(strict_types=1);

namespace Triniti\Ncr\Search\Elastica;

use Elastica\Query;
use Elastica\Query\AbstractQuery;
use Elastica\Query\FunctionScore;
use Gdbots\Ncr\Search\Elastica\IndexManager;
use Gdbots\Ncr\Search\Elastica\QueryFactory as BaseQueryFactory;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\Util\DateUtil;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\QueryParser\Builder\ElasticaQueryBuilder;
use Gdbots\QueryParser\ParsedQuery;
use Gdbots\Schemas\Iam\Enum\SearchUsersSort;
use Triniti\Schemas\Apollo\Enum\SearchPollsSort;
use Triniti\Schemas\Boost\Enum\SearchSponsorsSort;
use Triniti\Schemas\Canvas\Enum\SearchPagesSort;
use Triniti\Schemas\Curator\Enum\SearchGalleriesSort;
use Triniti\Schemas\Curator\Enum\SearchPromotionsSort;
use Triniti\Schemas\Curator\Enum\SearchTeasersSort;
use Triniti\Schemas\Curator\Enum\SearchTimelinesSort;
use Triniti\Schemas\Curator\Enum\SearchWidgetsSort;
use Triniti\Schemas\Dam\Enum\SearchAssetsSort;
use Triniti\Schemas\News\Enum\SearchArticlesSort;
use Triniti\Schemas\Notify\Enum\SearchNotificationsSort;
use Triniti\Schemas\Ovp\Enum\SearchVideosSort;
use Triniti\Schemas\People\Enum\SearchPeopleSort;
use Triniti\Schemas\Sys\Enum\SearchRedirectsSort;
use Triniti\Schemas\Taxonomy\Enum\SearchCategoriesSort;

class QueryFactory extends BaseQueryFactory
{
    protected function forSearchArticlesRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->addFullTextSearchField('hf')
            ->addFullTextSearchField('swipe')
            ->setHashtagFieldName('hashtags')
            ->addNestedField('blocks')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        if ($request->has('cursor')) {
            $cursor = json_decode(StringUtil::urlsafeB64Decode($request->get('cursor', '')), true) ?: [];
            if (isset($cursor['order_date_before'])) {
                $orderDate = new \DateTime("@{$cursor['order_date_before']}");
                $query->addFilter(new Query\Range('order_date', [
                    'lt' => $orderDate->format(DateUtil::ISO8601_ZULU),
                ]));
            }
        }

        switch ($request->get('sort', SearchArticlesSort::RELEVANCE())->getValue()) {
            case SearchArticlesSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchArticlesSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchArticlesSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchArticlesSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchArticlesSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchArticlesSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchArticlesSort::ORDER_DATE_ASC:
                return Query::create($query)->setSort(['order_date' => 'asc']);

            case SearchArticlesSort::ORDER_DATE_DESC:
                return Query::create($query)->setSort(['order_date' => 'desc']);

            case SearchArticlesSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchArticlesSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            // these "stat" sorting options refer to article-stats fields
            case SearchArticlesSort::POPULARITY:
                return Query::create($query)->setSort(['views' => 'desc', 'created_at' => 'desc']);

            case SearchArticlesSort::COMMENTS:
                return Query::create($query)->setSort(['comments' => 'desc', 'created_at' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchAssetsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->setHashtagFieldName('hashtags')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        if ($request->has('q') && str_starts_with($request->get('q'), '*')) {
            $query->setParam('should', new Query\Wildcard('title.raw', $request->get('q')));
        }

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchAssetsSort::RELEVANCE())->getValue()) {
            case SearchAssetsSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchAssetsSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchAssetsSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchAssetsSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchAssetsSort::MIME_TYPE_ASC:
                return Query::create($query)->setSort(['mime_type' => 'asc']);

            case SearchAssetsSort::MIME_TYPE_DESC:
                return Query::create($query)->setSort(['mime_type' => 'desc']);

            case SearchAssetsSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchAssetsSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            case SearchAssetsSort::GALLERY_SEQ_ASC:
                return Query::create($query)->setSort(['gallery_seq' => 'asc']);

            case SearchAssetsSort::GALLERY_SEQ_DESC:
                return Query::create($query)->setSort(['gallery_seq' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchCategoriesRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName('title')
            ->setHashtagFieldName('hashtags')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchCategoriesSort::RELEVANCE())->getValue()) {
            case SearchCategoriesSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchCategoriesSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchCategoriesSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchCategoriesSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchCategoriesSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchCategoriesSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return Query::create($query);
        }
    }

    protected function forSearchGalleriesRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->setHashtagFieldName('hashtags')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        if ($request->has('cursor')) {
            $cursor = json_decode(StringUtil::urlsafeB64Decode($request->get('cursor', '')), true) ?: [];
            if (isset($cursor['order_date_before'])) {
                $orderDate = new \DateTime("@{$cursor['order_date_before']}");
                $query->addFilter(new Query\Range('order_date', [
                    'lt' => $orderDate->format(DateUtil::ISO8601_ZULU),
                ]));
            }
        }

        switch ($request->get('sort', SearchGalleriesSort::RELEVANCE())->getValue()) {
            case SearchGalleriesSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchGalleriesSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchGalleriesSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchGalleriesSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchGalleriesSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchGalleriesSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchGalleriesSort::ORDER_DATE_ASC:
                return Query::create($query)->setSort(['order_date' => 'asc']);

            case SearchGalleriesSort::ORDER_DATE_DESC:
                return Query::create($query)->setSort(['order_date' => 'desc']);

            case SearchGalleriesSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchGalleriesSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchNotificationsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort')->getValue()) {
            case SearchNotificationsSort::CREATED_AT_ASC;
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchNotificationsSort::CREATED_AT_DESC;
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchNotificationsSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchNotificationsSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchNotificationsSort::SEND_AT_ASC:
                return Query::create($query)->setSort(['send_at' => 'asc']);

            case SearchNotificationsSort::SEND_AT_DESC:
                return Query::create($query)->setSort(['send_at' => 'desc']);

            case SearchNotificationsSort::SENT_AT_ASC:
                return Query::create($query)->setSort(['sent_at' => 'asc']);

            case SearchNotificationsSort::SENT_AT_DESC:
                return Query::create($query)->setSort(['sent_at' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchPagesRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->lowerCaseTerms(!in_array('redirect_ref', $parsedQuery->getFieldsUsed()))
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->addFullTextSearchField('custom_code')
            ->setHashtagFieldName('hashtags')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchPagesSort::RELEVANCE())->getValue()) {
            case SearchPagesSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchPagesSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchPagesSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchPagesSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchPagesSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchPagesSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchPagesSort::ORDER_DATE_ASC:
                return Query::create($query)->setSort(['order_date' => 'asc']);

            case SearchPagesSort::ORDER_DATE_DESC:
                return Query::create($query)->setSort(['order_date' => 'desc']);

            case SearchPagesSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchPagesSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchPeopleRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName('title.standard')
            ->addFullTextSearchField('title.standard')
            ->setHashtagFieldName('hashtags');

        $builder->ignoreStopWords(false);
        $builder->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchPeopleSort::RELEVANCE())->getValue()) {
            case SearchPeopleSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchPeopleSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchPeopleSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchPeopleSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchPeopleSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchPeopleSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchPollsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->setHashtagFieldName('hashtags')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchPollsSort::RELEVANCE())->getValue()) {
            case SearchPollsSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchPollsSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchPollsSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchPollsSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchPollsSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchPollsSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchPollsSort::ORDER_DATE_ASC:
                return Query::create($query)->setSort(['order_date' => 'asc']);

            case SearchPollsSort::ORDER_DATE_DESC:
                return Query::create($query)->setSort(['order_date' => 'desc']);

            case SearchPollsSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchPollsSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchPromotionsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchPromotionsSort::RELEVANCE())->getValue()) {
            case SearchPromotionsSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchPromotionsSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchPromotionsSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchPromotionsSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchPromotionsSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchPromotionsSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchPromotionsSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchPromotionsSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            case SearchPromotionsSort::PRIORITY_ASC:
                return Query::create($query)->setSort(['priority' => 'asc']);

            case SearchPromotionsSort::PRIORITY_DESC:
                return Query::create($query)->setSort(['priority' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchRedirectsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchRedirectsSort::CREATED_AT_ASC())->getValue()) {
            case SearchRedirectsSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchRedirectsSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchRedirectsSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchRedirectsSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchRedirectsSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchRedirectsSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchSponsorsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchSponsorsSort::CREATED_AT_ASC())->getValue()) {
            case SearchSponsorsSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchSponsorsSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchSponsorsSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchSponsorsSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchSponsorsSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchSponsorsSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchSponsorsSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchSponsorsSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchTeasersRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->setHashtagFieldName('hashtags')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        if ($request->has('cursor')) {
            $cursor = json_decode(StringUtil::urlsafeB64Decode($request->get('cursor', '')), true) ?: [];
            if (isset($cursor['order_date_before'])) {
                $orderDate = new \DateTime("@{$cursor['order_date_before']}");
                $query->addFilter(new Query\Range('order_date', [
                    'lt' => $orderDate->format(DateUtil::ISO8601_ZULU),
                ]));
            }
        }

        switch ($request->get('sort', SearchTeasersSort::RELEVANCE())->getValue()) {
            case SearchTeasersSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchTeasersSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchTeasersSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchTeasersSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchTeasersSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchTeasersSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchTeasersSort::ORDER_DATE_ASC:
                return Query::create($query)->setSort(['order_date' => 'asc']);

            case SearchTeasersSort::ORDER_DATE_DESC:
                return Query::create($query)->setSort(['order_date' => 'desc']);

            case SearchTeasersSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchTeasersSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchTimelinesRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->setHashtagFieldName('hashtags')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchTimelinesSort::RELEVANCE())->getValue()) {
            case SearchTimelinesSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchTimelinesSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchTimelinesSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchTimelinesSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchTimelinesSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchTimelinesSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchTimelinesSort::ORDER_DATE_ASC:
                return Query::create($query)->setSort(['order_date' => 'asc']);

            case SearchTimelinesSort::ORDER_DATE_DESC:
                return Query::create($query)->setSort(['order_date' => 'desc']);

            case SearchTimelinesSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchTimelinesSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchUsersRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->setMentionFieldName('networks')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        if (!$request->has('sort')) {
            return Query::create($query);
        }

        switch ($request->get('sort')->getValue()) {
            case SearchUsersSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchUsersSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchUsersSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchUsersSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchUsersSort::FIRST_NAME_ASC:
                return Query::create($query)->setSort(['first_name.raw' => 'asc', 'last_name.raw' => 'asc']);

            case SearchUsersSort::FIRST_NAME_DESC:
                return Query::create($query)->setSort(['first_name.raw' => 'desc', 'last_name.raw' => 'desc']);

            case SearchUsersSort::LAST_NAME_ASC:
                return Query::create($query)->setSort(['last_name.raw' => 'asc', 'first_name.raw' => 'asc']);

            case SearchUsersSort::LAST_NAME_DESC:
                return Query::create($query)->setSort(['last_name.raw' => 'desc', 'first_name.raw' => 'desc']);

            default:
                return Query::create($query);
        }
    }

    protected function forSearchVideosRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->setHashtagFieldName('hashtags')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        if ($request->has('cursor')) {
            $cursor = json_decode(StringUtil::urlsafeB64Decode($request->get('cursor', '')), true) ?: [];
            if (isset($cursor['order_date_before'])) {
                $orderDate = new \DateTime("@{$cursor['order_date_before']}");
                $query->addFilter(new Query\Range('order_date', [
                    'lt' => $orderDate->format(DateUtil::ISO8601_ZULU),
                ]));
            }
        }

        switch ($request->get('sort', SearchVideosSort::RELEVANCE())->getValue()) {
            case SearchVideosSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchVideosSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchVideosSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchVideosSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchVideosSort::PUBLISHED_AT_ASC:
                return Query::create($query)->setSort(['published_at' => 'asc']);

            case SearchVideosSort::PUBLISHED_AT_DESC:
                return Query::create($query)->setSort(['published_at' => 'desc']);

            case SearchVideosSort::ORDER_DATE_ASC:
                return Query::create($query)->setSort(['order_date' => 'asc']);

            case SearchVideosSort::ORDER_DATE_DESC:
                return Query::create($query)->setSort(['order_date' => 'desc']);

            case SearchVideosSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchVideosSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    protected function forSearchWidgetsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName(MappingBuilder::ALL_FIELD)
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        switch ($request->get('sort', SearchWidgetsSort::RELEVANCE())->getValue()) {
            case SearchWidgetsSort::CREATED_AT_ASC:
                return Query::create($query)->setSort(['created_at' => 'asc']);

            case SearchWidgetsSort::CREATED_AT_DESC:
                return Query::create($query)->setSort(['created_at' => 'desc']);

            case SearchWidgetsSort::UPDATED_AT_ASC:
                return Query::create($query)->setSort(['updated_at' => 'asc']);

            case SearchWidgetsSort::UPDATED_AT_DESC:
                return Query::create($query)->setSort(['updated_at' => 'desc']);

            case SearchWidgetsSort::TITLE_ASC:
                return Query::create($query)->setSort(['title.raw' => 'asc']);

            case SearchWidgetsSort::TITLE_DESC:
                return Query::create($query)->setSort(['title.raw' => 'desc']);

            default:
                return $this->createRelevanceWithRecencySortedQuery($query, $request);
        }
    }

    /**
     * Applies relevance scoring weighted with recency to the query and returns
     * the final query object which will be sent to elastic search.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/guide/current/decay-functions.html
     *
     * @param AbstractQuery $query
     * @param Message       $request
     *
     * @return Query
     */
    protected function createRelevanceWithRecencySortedQuery(AbstractQuery $query, Message $request): Query
    {
        if (!$query->hasParam('must') && !$query->hasParam('should')) {
            // no scores to match so decay function will not work
            return Query::create($query)->setSort(['created_at' => 'desc']);
        }
        $before = $request->get('created_before') ?: new \DateTime('now', new \DateTimeZone('UTC'));
        $query = (new FunctionScore())
            ->setQuery($query)
            ->addFunction(FunctionScore::DECAY_EXPONENTIAL, [
                IndexManager::CREATED_AT_ISO_FIELD_NAME => [
                    'origin' => $before->format(DateUtil::ISO8601_ZULU),
                    'scale'  => '7d',
                    'offset' => '2m',
                    'decay'  => 0.1,
                ],
            ]);
        return Query::create($query);
    }
}
