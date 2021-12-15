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
use Gdbots\Schemas\Iam\Enum\SearchAppsSort;
use Gdbots\Schemas\Iam\Enum\SearchRolesSort;
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
use Triniti\Schemas\Sys\Enum\SearchFlagsetsSort;
use Triniti\Schemas\Sys\Enum\SearchPicklistsSort;
use Triniti\Schemas\Sys\Enum\SearchRedirectsSort;
use Triniti\Schemas\Taxonomy\Enum\SearchCategoriesSort;
use Triniti\Schemas\Taxonomy\Enum\SearchChannelsSort;

class QueryFactory extends BaseQueryFactory
{
    protected function forSearchAppsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName('title')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        return match ($request->get('sort', SearchAppsSort::TITLE_ASC)) {
            SearchAppsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchAppsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchAppsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchAppsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchAppsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchAppsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => Query::create($query),
        };
    }

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

        return match ($request->get('sort', SearchArticlesSort::RELEVANCE)) {
            SearchArticlesSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchArticlesSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchArticlesSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchArticlesSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchArticlesSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchArticlesSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchArticlesSort::ORDER_DATE_ASC => Query::create($query)->setSort(['order_date' => 'asc']),
            SearchArticlesSort::ORDER_DATE_DESC => Query::create($query)->setSort(['order_date' => 'desc']),
            SearchArticlesSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchArticlesSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            SearchArticlesSort::POPULARITY => Query::create($query)->setSort(['views' => 'desc', 'created_at' => 'desc']),
            SearchArticlesSort::COMMENTS => Query::create($query)->setSort(['comments' => 'desc', 'created_at' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->get('sort', SearchAssetsSort::RELEVANCE)) {
            SearchAssetsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchAssetsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchAssetsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchAssetsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchAssetsSort::MIME_TYPE_ASC => Query::create($query)->setSort(['mime_type' => 'asc']),
            SearchAssetsSort::MIME_TYPE_DESC => Query::create($query)->setSort(['mime_type' => 'desc']),
            SearchAssetsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchAssetsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            SearchAssetsSort::GALLERY_SEQ_ASC => Query::create($query)->setSort(['gallery_seq' => 'asc']),
            SearchAssetsSort::GALLERY_SEQ_DESC => Query::create($query)->setSort(['gallery_seq' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->get('sort', SearchCategoriesSort::RELEVANCE)) {
            SearchCategoriesSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchCategoriesSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchCategoriesSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchCategoriesSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchCategoriesSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchCategoriesSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => Query::create($query),
        };
    }

    protected function forSearchChannelsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName('title')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        return match ($request->get('sort', SearchChannelsSort::TITLE_ASC)) {
            SearchChannelsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchChannelsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchChannelsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchChannelsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchChannelsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchChannelsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => Query::create($query),
        };
    }

    protected function forSearchFlagsetsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName('title')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        return match ($request->get('sort', SearchFlagsetsSort::TITLE_ASC)) {
            SearchFlagsetsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchFlagsetsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchFlagsetsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchFlagsetsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchFlagsetsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchFlagsetsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => Query::create($query),
        };
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

        return match ($request->get('sort', SearchGalleriesSort::RELEVANCE)) {
            SearchGalleriesSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchGalleriesSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchGalleriesSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchGalleriesSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchGalleriesSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchGalleriesSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchGalleriesSort::ORDER_DATE_ASC => Query::create($query)->setSort(['order_date' => 'asc']),
            SearchGalleriesSort::ORDER_DATE_DESC => Query::create($query)->setSort(['order_date' => 'desc']),
            SearchGalleriesSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchGalleriesSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->get('sort', SearchNotificationsSort::SENT_AT_DESC)) {
            SearchNotificationsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchNotificationsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchNotificationsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchNotificationsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchNotificationsSort::SEND_AT_ASC => Query::create($query)->setSort(['send_at' => 'asc']),
            SearchNotificationsSort::SEND_AT_DESC => Query::create($query)->setSort(['send_at' => 'desc']),
            SearchNotificationsSort::SENT_AT_ASC => Query::create($query)->setSort(['sent_at' => 'asc']),
            SearchNotificationsSort::SENT_AT_DESC => Query::create($query)->setSort(['sent_at' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->get('sort', SearchPagesSort::RELEVANCE)) {
            SearchPagesSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchPagesSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchPagesSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchPagesSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchPagesSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchPagesSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchPagesSort::ORDER_DATE_ASC => Query::create($query)->setSort(['order_date' => 'asc']),
            SearchPagesSort::ORDER_DATE_DESC => Query::create($query)->setSort(['order_date' => 'desc']),
            SearchPagesSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchPagesSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->get('sort', SearchPeopleSort::RELEVANCE)) {
            SearchPeopleSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchPeopleSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchPeopleSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchPeopleSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchPeopleSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchPeopleSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
    }

    protected function forSearchPicklistsRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName('title')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        return match ($request->fget('sort', SearchPicklistsSort::TITLE_ASC)) {
            SearchPicklistsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchPicklistsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchPicklistsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchPicklistsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchPicklistsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchPicklistsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => Query::create($query),
        };
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

        return match ($request->fget('sort', SearchPollsSort::RELEVANCE)) {
            SearchPollsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchPollsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchPollsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchPollsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchPollsSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchPollsSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchPollsSort::ORDER_DATE_ASC => Query::create($query)->setSort(['order_date' => 'asc']),
            SearchPollsSort::ORDER_DATE_DESC => Query::create($query)->setSort(['order_date' => 'desc']),
            SearchPollsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchPollsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->fget('sort', SearchPromotionsSort::RELEVANCE)) {
            SearchPromotionsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchPromotionsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchPromotionsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchPromotionsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchPromotionsSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchPromotionsSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchPromotionsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchPromotionsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            SearchPromotionsSort::PRIORITY_ASC => Query::create($query)->setSort(['priority' => 'asc']),
            SearchPromotionsSort::PRIORITY_DESC => Query::create($query)->setSort(['priority' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->fget('sort', SearchRedirectsSort::TITLE_ASC)) {
            SearchRedirectsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchRedirectsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchRedirectsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchRedirectsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchRedirectsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchRedirectsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
    }

    protected function forSearchRolesRequest(Message $request, ParsedQuery $parsedQuery, array $qnames): Query
    {
        $builder = (new ElasticaQueryBuilder())
            ->setDefaultFieldName('title')
            ->addParsedQuery($parsedQuery);

        $query = $builder->getBoolQuery();

        $this->filterDates($request, $query);
        $this->filterQNames($request, $query, $qnames);
        $this->filterStatuses($request, $query);

        return match ($request->fget('sort', SearchRolesSort::TITLE_ASC)) {
            SearchRolesSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchRolesSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchRolesSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchRolesSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchRolesSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchRolesSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => Query::create($query),
        };
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

        return match ($request->fget('sort', SearchSponsorsSort::RELEVANCE)) {
            SearchSponsorsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchSponsorsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchSponsorsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchSponsorsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchSponsorsSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchSponsorsSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchSponsorsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchSponsorsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->fget('sort', SearchTeasersSort::RELEVANCE)) {
            SearchTeasersSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchTeasersSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchTeasersSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchTeasersSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchTeasersSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchTeasersSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchTeasersSort::ORDER_DATE_ASC => Query::create($query)->setSort(['order_date' => 'asc']),
            SearchTeasersSort::ORDER_DATE_DESC => Query::create($query)->setSort(['order_date' => 'desc']),
            SearchTeasersSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchTeasersSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->fget('sort', SearchTimelinesSort::RELEVANCE)) {
            SearchTimelinesSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchTimelinesSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchTimelinesSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchTimelinesSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchTimelinesSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchTimelinesSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchTimelinesSort::ORDER_DATE_ASC => Query::create($query)->setSort(['order_date' => 'asc']),
            SearchTimelinesSort::ORDER_DATE_DESC => Query::create($query)->setSort(['order_date' => 'desc']),
            SearchTimelinesSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchTimelinesSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->fget('sort', SearchUsersSort::RELEVANCE)) {
            SearchUsersSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchUsersSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchUsersSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchUsersSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchUsersSort::FIRST_NAME_ASC => Query::create($query)->setSort(['first_name.raw' => 'asc', 'last_name.raw' => 'asc']),
            SearchUsersSort::FIRST_NAME_DESC => Query::create($query)->setSort(['first_name.raw' => 'desc', 'last_name.raw' => 'desc']),
            SearchUsersSort::LAST_NAME_ASC => Query::create($query)->setSort(['last_name.raw' => 'asc', 'first_name.raw' => 'asc']),
            SearchUsersSort::LAST_NAME_DESC => Query::create($query)->setSort(['last_name.raw' => 'desc', 'first_name.raw' => 'desc']),
            default => Query::create($query),
        };
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

        return match ($request->fget('sort', SearchVideosSort::RELEVANCE)) {
            SearchVideosSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchVideosSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchVideosSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchVideosSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchVideosSort::PUBLISHED_AT_ASC => Query::create($query)->setSort(['published_at' => 'asc']),
            SearchVideosSort::PUBLISHED_AT_DESC => Query::create($query)->setSort(['published_at' => 'desc']),
            SearchVideosSort::ORDER_DATE_ASC => Query::create($query)->setSort(['order_date' => 'asc']),
            SearchVideosSort::ORDER_DATE_DESC => Query::create($query)->setSort(['order_date' => 'desc']),
            SearchVideosSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchVideosSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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

        return match ($request->fget('sort', SearchWidgetsSort::RELEVANCE)) {
            SearchWidgetsSort::CREATED_AT_ASC => Query::create($query)->setSort(['created_at' => 'asc']),
            SearchWidgetsSort::CREATED_AT_DESC => Query::create($query)->setSort(['created_at' => 'desc']),
            SearchWidgetsSort::UPDATED_AT_ASC => Query::create($query)->setSort(['updated_at' => 'asc']),
            SearchWidgetsSort::UPDATED_AT_DESC => Query::create($query)->setSort(['updated_at' => 'desc']),
            SearchWidgetsSort::TITLE_ASC => Query::create($query)->setSort(['title.raw' => 'asc']),
            SearchWidgetsSort::TITLE_DESC => Query::create($query)->setSort(['title.raw' => 'desc']),
            default => $this->createRelevanceWithRecencySortedQuery($query, $request),
        };
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
