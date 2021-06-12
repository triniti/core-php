
<?php
declare(strict_types=1);

namespace Tmz\News;

use Gdbots\Ncr\Event\NodeProjectedEvent;
use Gdbots\Pbj\Message;
use Gdbots\Pbjx\EventSubscriber;
use Gdbots\UriTemplate\UriTemplateService;

final class TwitterWatcher implements EventSubscriber
{
    public static function getSubscribedEvents()
    {
        return [
            'triniti:news:mixin:article.published' => 'onArticlePublished',
        ];
    }

    /**
     * @param Message $event
     * @param Pbjx    $pbjx
     */
    public function onArticlePublished(Message $event, Pbjx $pbjx): void
    {
        $this->notifyTwitter($event, $event->get('node_ref'), $pbjx, 'create');
    }

    public function notifyTwitter(Message $event, $article, Pbjx $pbjx): void
    {
        $lastEvent = $event->getLastEvent();
        if ($lastEvent->isReplay()) {
            return;
        }

        $article = $event->getNode();

        if ($article instanceof NodeRef) {
          try {
              $article = $this->ncr->getNode($article, false, $this->createNcrContext($event));
          } catch (NodeNotFound $nf) {
              return;
          } catch (\Throwable $e) {
              throw $e;
          }
        }  

        if (!$article instanceof Message) {
            return;
        }

        if (!$this->shouldNotifyTwitter($event, $article)) {
          return;
        }

        $notification = $this->createTwitterNotification($event, $article, $pbjx)
        ->set('app_ref', NodeRef::fromNode($app));

        $curie = $notification::schema()->getCurie();
        $curie = "{$curie->getVendor()}:{$curie->getPackage()}:command:create-notification";

        /** @var Message $class */
        $class = MessageResolver::resolveCurie(SchemaCurie::fromString($curie));
        $command = $class::create()->set('node', $notification);

        $pbjx->copyContext($event, $command);
        $command
            ->set('ctx_correlator_ref', $event->generateMessageRef())
            ->clear('ctx_app');

        $nodeRef = NodeRef::fromNode($article);
        $pbjx->sendAt($command, strtotime('+180 seconds'), "{$nodeRef}.post-tweet");
  }

    /**
     * @param Message $event
     * @param Message $article
     * @param Pbjx    $pbjx
     *
     * @return Message
     */
    protected function createTwitterNotification(Message $event, Message $article, Pbjx $pbjx): Message
    {
        /** @var \DateTime $date */
        $date = $event->get('occurred_at')->toDateTime();

        return TwitterNotificationV1Mixin::findOne()->createMessage()
            ->set('title', $article->get('title'))
            ->set('send_at', $date)
            ->set('content_ref', NodeRef::fromNode($article));
    }

    /**
     * @param Message $event
     * @param Message $article
     *
     * @return bool
     */
    protected function shouldNotifyTwitter(Message $event, Message $article): bool
    {
        // override to implement your own check to block twitter posts
        // based on the event or article
        return true;
    }
  }
