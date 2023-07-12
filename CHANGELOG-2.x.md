# CHANGELOG for 2.x
This changelog references the relevant changes done in 2.x versions.


## v2.4.1
* In `\Triniti\Ovp\UpdateTranscriptionStatusHandler::handleCommand` update the Document Asset last as it is least likely to be available in the NCR. When it is unavailable the event is not applied to the Video, but that process only requires the Document Asset's NodeRef, which is known. 


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
