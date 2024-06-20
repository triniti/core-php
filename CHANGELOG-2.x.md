# CHANGELOG for 2.x
This changelog references the relevant changes done in 2.x versions.


## v2.6.2
* Remove Guzzle timeout in `Triniti\AppleNews\AppleNewsApi`.


## v2.6.1
* Increase Guzzle timeout in `Triniti\AppleNews\AppleNewsApi`.


## v2.6.0
* Use atomic counters to update poll count in `Triniti\Apollo\NcrPollStatsProjector`
* Set timeout in notifiers that use guzzle to 5 seconds.
* Add `Triniti\Sys\InspectSeoHandler` to handle seo inspection


## v2.5.3
* Use field-based jobId for Jwplayer SyncMedia commands


## v2.5.2
* Apple Notification Failures: CMS Re-Push


## v2.5.1
* Allow file details to change when asset nodes are updated
* Do not clear file_etag when enriching assets with s3 object
* Use guzzle retry middleware in `Triniti\Dam\AssetEnricher` instead of aws


## v2.5.0
* Require php 8.3.
* Allow symfony 7.x
* Do not use public-read when uploading to s3.


## v2.4.4
* Check for empty unslotted nodes in SearchTeasersRequestHandler and SearchArticlesRequestHandler.


## v2.4.3
* Add webp image mimetype in `Triniti\Dam\Util\MimeTypeUtil`


## v2.4.2
* In `Triniti\Dam\AssetUploader::uploadToS3` add ability to customize bucket, key, and acl via options when uploading file to s3.


## v2.4.1
* In `Triniti\Ovp\UpdateTranscriptionStatusHandler::handleCommand` update the Document Asset last as it is least likely to be available in the NCR. When it is unavailable the event is not applied to the Video, but that process only requires the Document Asset's NodeRef, which is known.


## v2.4.0
* Update FCM to HTTP v1 API.


## v2.3.2
* Change ${var} in strings is deprecated, use {$var} instead.


## v2.3.1
* Add `Triniti\AppleNews\Component\ConditionalComponent` to enable conditional properties for components


## v2.3.0
* Require symfony 6.2.x
* Remove sensio/framework-extra-bundle.


## v2.2.0
* Add `Triniti\Apollo\AddReactionsHandler` to handle add reactions command.
* Add `Triniti\Apollo\NcrReactionsProjector` to enable reactions on nodes.
* Add `Triniti\Apollo\ReactionsValidator` to validate AddReactions command and Reactions node.


## v2.1.3
* When syncing to Jwplayer, include people and categories as custom parameters.


## v2.1.2
* Patching `Triniti\AppleNews\SupportedUnits`, `jsonSerialize` return type.


## v2.1.1
* Patching `Triniti\AppleNews\AppleNewApi` guzzle client for PHP 8.1.


## v2.1.0
* Update `Triniti\News\TwitterWatcher` to post tweets to all twitter apps.


## v2.0.1
* Fix couple of missed php8.1 enum uses (->value checkes) in Ovp*


## v2.0.0
__BREAKING CHANGES__

* Upgrade to support Symfony 6 and PHP 8.1.
