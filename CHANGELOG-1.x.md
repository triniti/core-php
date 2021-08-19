# CHANGELOG for 1.x
This changelog references the relevant changes done in 1.x versions.


## v1.1.2
* Add process lock to `Triniti\Notify\SendNotificationHandler`


## v1.1.1
* Add 3 minute buffer to twitter notification `send_at` to allow users the chance to edit notification before it is sent.


## v1.1.0
* Support auto post to twitter on article publish.


## v1.0.7
* Add sort handling for apps, roles, flagsets to `Triniti\Ncr\Search\Elastica\QueryFactory`.


## v1.0.6
* Add subtitled manifest to artifact url provider.


## v1.0.5
* Subscribe `NcrArticleProjector` to `apple-news-article-synced` mixin and event.


## v1.0.4
* Ensure `order_date` is updated to `published_at` value when a teaser or teaserable node is published.


## v1.0.3
* Add `Triniti\AppleNews\Style\ConditionalComponentStyle` to enable conditional properties for component style.
* Update `ArticleDocumentMarshaler::transformGoogleMapBlock` to use Map instead of Place and work with zoom level.


## v1.0.2
* Remove use of `HasEndUserMessage`.


## v1.0.1
* Add article.updated subscriber to AppleNewsWatcher.


## v1.0.0
* Initial version.
