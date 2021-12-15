<?php
declare(strict_types=1);

namespace Triniti\AppleNews;

use Gdbots\Ncr\Exception\NodeNotFound;
use Gdbots\Ncr\Ncr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\GeoPoint;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\UriTemplate\UriTemplateService;
use Triniti\AppleNews\Component\Audio;
use Triniti\AppleNews\Component\Body;
use Triniti\AppleNews\Component\Component;
use Triniti\AppleNews\Component\Container;
use Triniti\AppleNews\Component\Divider;
use Triniti\AppleNews\Component\EmbedWebVideo;
use Triniti\AppleNews\Component\FacebookPost;
use Triniti\AppleNews\Component\Gallery;
use Triniti\AppleNews\Component\GalleryItem;
use Triniti\AppleNews\Component\Heading;
use Triniti\AppleNews\Component\Image;
use Triniti\AppleNews\Component\Instagram;
use Triniti\AppleNews\Component\Map;
use Triniti\AppleNews\Component\MapItem;
use Triniti\AppleNews\Component\MapSpan;
use Triniti\AppleNews\Component\Photo;
use Triniti\AppleNews\Component\Place;
use Triniti\AppleNews\Component\PullQuote;
use Triniti\AppleNews\Component\Quote;
use Triniti\AppleNews\Component\Tweet;
use Triniti\AppleNews\Component\Video;
use Triniti\AppleNews\Exception\ArticleNotPublished;
use Triniti\AppleNews\Layout\ComponentLayout;
use Triniti\AppleNews\Layout\Layout;
use Triniti\AppleNews\Layout\Margin;
use Triniti\AppleNews\Link\ComponentLink;
use Triniti\AppleNews\Link\Link;
use Triniti\AppleNews\Style\ComponentStyle;
use Triniti\AppleNews\Style\ComponentTextStyle;
use Triniti\AppleNews\Style\DocumentStyle;
use Triniti\AppleNews\Style\StrokeStyle;
use Triniti\AppleNews\Style\TextStyle;
use Triniti\Dam\UrlProvider;
use Triniti\Schemas\Common\Enum\AspectRatio;
use Triniti\Schemas\Dam\AssetId;
use Triniti\Schemas\Dam\Enum\SearchAssetsSort;
use Triniti\Schemas\Dam\Request\SearchAssetsRequestV1;

class ArticleDocumentMarshaler
{
    /** @var Ncr */
    protected $ncr;

    /** @var Pbjx */
    protected $pbjx;

    /** @var UrlProvider */
    protected $urlProvider;

    /** @var ArticleDocument */
    protected $document;

    /** @var Message */
    protected $article;

    /** @var Metadata */
    protected $metadata;

    /**
     * @param Ncr         $ncr
     * @param Pbjx        $pbjx
     * @param UrlProvider $urlProvider
     */
    public function __construct(Ncr $ncr, Pbjx $pbjx, UrlProvider $urlProvider)
    {
        $this->ncr = $ncr;
        $this->pbjx = $pbjx;
        $this->urlProvider = $urlProvider;
    }

    /**
     * Marshals an Article into an Apple News ArticleDocument
     *
     * @link https://developer.apple.com/documentation/apple_news/articledocument
     *
     * @param Message $article
     *
     * @return ArticleDocument
     */
    public function marshal(Message $article): ArticleDocument
    {
        if (!NodeStatus::PUBLISHED === $article->get('status')) {
            throw new ArticleNotPublished();
        }

        $this->document = new ArticleDocument();
        $this->article = $article;
        $this->metadata = new Metadata();

        $this->document
            ->setIdentifier($this->article->get('_id')->toString())
            ->setTitle($this->article->get('title'));

        $this->metadata
            ->setDateCreated($this->article->get('created_at')->toDateTime()->format(\DateTime::ATOM))
            ->setDatePublished($this->article->get('published_at')->format(\DateTime::ATOM))
            ->setKeywords($this->article->get('meta_keywords', []))
            ->setCanonicalURL($this->getCanonicalUrl($this->article));

        if ($this->article->has('updated_at')) {
            $this->metadata->setDateModified($this->article->get('updated_at')->toDateTime()->format(\DateTime::ATOM));
        }

        $this->setLayout();
        $this->setDocumentStyle();
        $this->setTextStyles();
        $this->setComponentLayouts();
        $this->setComponentStyles();
        $this->setComponentTextStyles();
        $this->createComponents();

        $this->setAuthors();
        $this->setExcerpt();
        $this->setLinkedArticles();
        $this->setThumbnailUrl();
        $this->setCampaignData();

        $this->document->setMetadata($this->metadata);
        $this->document->validate();

        return $this->document;
    }

    /**
     * @param Message $message
     *
     * @return string
     */
    protected function getCanonicalUrl(Message $message): string
    {
        return UriTemplateService::expand(
            "{$message::schema()->getQName()}.canonical",
            $message->getUriTemplateVars()
        );
    }

    /**
     * @see Metadata
     */
    protected function setAuthors(): void
    {
    }

    /**
     * @see Metadata
     */
    protected function setCampaignData(): void
    {
    }

    /**
     * @see Metadata
     */
    protected function setExcerpt(): void
    {
        if (!$this->article->has('blocks')) {
            return;
        }

        /** @var Message $block */
        foreach ($this->article->get('blocks') as $block) {
            if ($block->has('text') && 'text-block' === $block::schema()->getCurie()->getMessage()) {
                $this->metadata->setExcerpt(strip_tags($block->get('text')));

                return;
            }
        }
    }

    /**
     * @see Metadata
     */
    protected function setLinkedArticles(): void
    {
        if (!$this->article->has('related_article_refs')) {
            return;
        }

        foreach ($this->getNodes($this->article->get('related_article_refs')) as $node) {
            if (!$node->has('apple_news_id') || !$node->get('apple_news_enabled')) {
                continue;
            }

            $link = new LinkedArticle();
            $link
                ->setURL($this->getCanonicalUrl($node))
                ->setRelationship('related');

            $this->metadata->addLink($link);
        }
    }

    /**
     * @see Metadata
     */
    protected function setThumbnailUrl(): void
    {
        if (!$this->article->has('image_ref')) {
            return;
        }

        if ($this->metadata->getThumbnailURL()) {
            return;
        }

        $imageUrl = $this->getImageUrl($this->article->get('image_ref'), AspectRatio::R4BY3);
        $this->metadata->setThumbnailURL($imageUrl);
    }

    /**
     * @see Layout
     */
    protected function setLayout(): void
    {
        $layout = new Layout();
        $layout->setColumns(12)->setMargin(40)->setWidth(1024)->setGutter(20);

        $this->document->setLayout($layout);
    }

    /**
     * @see DocumentStyle
     */
    protected function setDocumentStyle(): void
    {
        $documentStyle = new DocumentStyle();
        $documentStyle->setBackgroundColor();

        $this->document->setDocumentStyle($documentStyle);
    }

    /**
     * @see TextStyle
     */
    protected function setTextStyles(): void
    {
        $textStyle = new TextStyle();
        $textStyle->setTextColor('#000000');

        $this->document->setTextStyle('default', $textStyle);
    }

    /**
     * @see ComponentLayout
     */
    protected function setComponentLayouts(): void
    {
        $layout = new ComponentLayout();
        $margin = new Margin();
        $margin->setTop(0)->setBottom(17);
        $layout
            ->setColumnSpan(10)
            ->setColumnStart(1)
            ->setMargin($margin);

        $this->document->setComponentLayout('default', $layout);
    }

    /**
     * @see ComponentStyle
     */
    protected function setComponentStyles(): void
    {
        $this->document->setComponentStyle('default', new ComponentStyle());
    }

    /**
     * @see ComponentTextStyle
     */
    protected function setComponentTextStyles(): void
    {
        $linkStyle = new TextStyle();
        $linkStyle->setTextColor('#cf0000');

        $componentTextStyle = new ComponentTextStyle();
        $componentTextStyle
            ->setFontName('MyriadPro-Regular')
            ->setFontSize(22)
            ->setTextColor('#464646')
            ->setLineHeight(30)
            ->setParagraphSpacingBefore(40)
            ->setLinkStyle($linkStyle);

        $this->document->setComponentTextStyle('default-body', $componentTextStyle);
    }

    /**
     * Iterates through the blocks on the article to create
     * the apple news components.
     */
    protected function createComponents(): void
    {
        if (!$this->article->has('blocks')) {
            return;
        }

        /** @var Message[] $blocks */
        $blocks = $this->article->get('blocks');

        $idx = 0;
        $total = count($blocks);
        $prevBlock = null;
        $rendered = [];

        foreach ($blocks as $block) {
            ++$idx;
            $isFirst = $idx === 1;
            $isLast = $idx === $total;

            $context = [
                'skip_block'   => false,
                'prev_block'   => $prevBlock,
                'rendered'     => $rendered,
                'idx'          => $idx,
                'is_first'     => $isFirst,
                'is_last'      => $isLast,
                'total_blocks' => $total,
            ];

            $this->beforeTransformBlock($block, $context);
            $this->transformBlock($block, $context);
            $prevBlock = $block;
            $rendered[$block::schema()->getCurie()->getMessage()] = true;
            $context['rendered'] = $rendered;
            $this->afterTransformBlock($block, $context);
        }
    }

    /**
     * @param Component $component
     */
    protected function addComponent(Component $component): void
    {
        $this->document->addComponent($component);
    }

    /**
     * @param NodeRef     $nodeRef
     * @param AspectRatio $aspectRatio
     * @param Message     $block
     *
     * @return string
     */
    protected function getImageUrl(NodeRef $nodeRef, AspectRatio $aspectRatio, ?Message $block = null): string
    {
        $aspectRatio = $aspectRatio->value;

        switch ($aspectRatio) {
            case AspectRatio::UNKNOWN;
            case AspectRatio::AUTO;
                $version = AspectRatio::R4BY3;
                break;

            case AspectRatio::CUSTOM;
            case AspectRatio::ORIGINAL;
                $version = 'o';
                break;

            default:
                $version = $aspectRatio;
                break;
        }

        return $this->urlProvider->getUrl(AssetId::fromString($nodeRef->getId()), $version, 'lg');
    }

    /**
     * @param NodeRef $nodeRef
     *
     * @return Message
     */
    protected function getNode(NodeRef $nodeRef): ?Message
    {
        try {
            $node = $this->ncr->getNode($nodeRef);
            if (NodeStatus::PUBLISHED === $node->get('status')) {
                return $node;
            }

            return null;
        } catch (NodeNotFound $nf) {
            return null;
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * @param NodeRef[] $nodeRefs
     *
     * @return Message[]
     */
    protected function getNodes(array $nodeRefs): array
    {
        $nodes = [];
        foreach ($this->ncr->getNodes($nodeRefs) as $nodeRef => $node) {
            if (NodeStatus::PUBLISHED === $node->get('status')) {
                $nodes[$nodeRef] = $node;
            }
        }

        return $nodes;
    }

    /**
     * @param Message $block
     * @param array   $context
     */
    protected function beforeTransformBlock(Message $block, array &$context): void
    {
    }

    /**
     * @param Message $block
     * @param array   $context
     */
    protected function transformBlock(Message $block, array &$context): void
    {
        if ($context['skip_block']) {
            return;
        }

        $schema = $block::schema();
        $method = 'transform' . ucfirst($schema->getHandlerMethodName(false));
        if (!is_callable([$this, $method])) {
            return;
        }

        $component = $this->$method($block, $context);
        if ($component instanceof Component) {
            $this->addComponent($component);
        }
    }

    /**
     * @param Message $block
     * @param array   $context
     */
    protected function afterTransformBlock(Message $block, array &$context): void
    {
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformArticleBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('node_ref')) {
            return null;
        }

        $node = $this->getNode($block->get('node_ref'));
        if (null === $node) {
            return null;
        }

        $container = new Container();
        $container->setIdentifier($block->get('etag') . $context['idx']);

        /** @var NodeRef $imageRef */
        $imageRef = $block->get('image_ref', $node->get('image_ref'));

        if ($block->get('show_image') && $imageRef) {
            $imageUrl = $this->getImageUrl($imageRef, AspectRatio::R1BY1);

            $image = new Image();
            $image->setURL($imageUrl);

            $container->addComponent($image);
        }

        $linkTextComponent = new Body();
        $text = $block->get('link_text', $node->get('title'));

        // A component can only be linked to Apple News or other Apple apps
        if ($node->has('apple_news_share_url')) {
            $componentLinkAddition = new ComponentLink();
            $componentLinkAddition->setURL($node->get('apple_news_share_url'));
            $container->addAddition($componentLinkAddition);

            // entire component is linked, no canonical linking needed
            $linkTextComponent->setText(strip_tags($text));
        } else {
            $html = sprintf('<a href="%s">%s</a>', $this->getCanonicalUrl($node), strip_tags($text));
            $linkTextComponent
                ->setFormat('html')
                ->setText($html);
        }

        $container->addComponent($linkTextComponent);

        return $container;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformAudioBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('node_ref')) {
            return null;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $block->get('node_ref');
        $node = $this->getNode($nodeRef);
        if (null === $node) {
            return null;
        }

        $component = new Audio();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setURL($this->urlProvider->getUrl(AssetId::fromString($nodeRef->getId())));

        $caption = $block->get('launch_text') ?: $node->get('description', '');
        if (!empty($caption)) {
            $component->setCaption(strip_tags($caption));
        }

        if ($block->has('image_ref')) {
            $imageUrl = $this->getImageUrl($block->get('image_ref'), AspectRatio::R16BY9, $block);
            $component->setImageURL($imageUrl);
            if ($context['is_first']) {
                $this->metadata->setThumbnailURL($imageUrl);
            }
        }

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformCodeBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformDividerBlock(Message $block, array &$context): ?Component
    {
        $validStrokeStyles = ['solid', 'dashed', 'dotted'];

        // Make sure stroke style is supported
        $strokeStyle = $block->get('stroke_style', 'solid');
        if (!in_array($strokeStyle, $validStrokeStyles)) {
            $strokeStyle = 'solid';
        }

        $stroke = new StrokeStyle();
        $stroke->setStyle($strokeStyle);
        $component = new Divider();
        $component->setStroke($stroke);

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformDocumentBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('node_ref')) {
            return null;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $block->get('node_ref');
        $url = $this->urlProvider->getUrl(AssetId::fromString($nodeRef->getId()));

        if (!$block->has('image_ref')) {
            $text = strip_tags($block->get('launch_text', 'Download Document'));
            $html = sprintf('<a href="%s">%s</a>', $url, $text);

            $component = new Body();

            return $component
                ->setIdentifier($block->get('etag') . $context['idx'])
                ->setText($html)
                ->setFormat('html');
        }

        $imageUrl = $this->getImageUrl($block->get('image_ref'), AspectRatio::R4BY3, $block);
        $component = new Photo();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setURL($imageUrl)
            ->setCaption(strip_tags($block->get('launch_text', 'Download Document')));

        $link = new ComponentLink();
        $link->setURL($url);
        $component->addAddition($link);

        if ($context['is_first']) {
            $this->metadata->setThumbnailURL($imageUrl);
        }

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformFacebookPostBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('href')) {
            return null;
        }

        $component = new FacebookPost();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setURL($block->get('href'));

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformFacebookVideoBlock(Message $block, array &$context): ?Component
    {
        // todo: backup plan for facebook video
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformGalleryBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('node_ref')) {
            return null;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $block->get('node_ref');
        $node = $this->getNode($nodeRef);
        if (null === $node) {
            return null;
        }

        $component = new Gallery();
        $component->setIdentifier($block->get('etag') . $context['idx']);

        $images = $this->getGalleryImages($nodeRef);
        if (empty($images)) {
            return null;
        }

        foreach ($images as $image) {
            $component->addItem($this->createGalleryItem($block, $node, $image, $context));
        }

        if ($context['is_first']) {
            $item = $component->getItems()[0];
            $this->metadata->setThumbnailURL($item->getURL());
        }

        return $component;
    }

    /**
     * @param NodeRef $nodeRef
     *
     * @return Message[]
     */
    protected function getGalleryImages(NodeRef $nodeRef): array
    {
        $request = SearchAssetsRequestV1::create()
            ->set('gallery_ref', $nodeRef)
            ->set('status', NodeStatus::PUBLISHED)
            ->set('sort', SearchAssetsSort::GALLERY_SEQ_DESC)
            ->set('count', 130)
            ->addToSet('types', ['image-asset']);

        $request->set('ctx_causator_ref', $request->generateMessageRef());

        $response = $this->pbjx->request($request);

        return $response->get('nodes', []);
    }

    /**
     * @param Message $block
     * @param Message $galleryNode
     * @param Message $imageNode
     * @param array   $context
     *
     * @return GalleryItem
     */
    protected function createGalleryItem(
        Message $block,
        Message $galleryNode,
        Message $imageNode,
        array &$context
    ): GalleryItem {
        $item = new GalleryItem();
        $item->setURL($this->getImageUrl(NodeRef::fromNode($imageNode), AspectRatio::ORIGINAL, $block));

        $credit = $imageNode->get('credit', $galleryNode->get('credit'));

        if ($imageNode->has('description')) {
            $captionTemplate = empty($credit) ? '<strong>%s</strong>%s' : '<strong>%s</strong><br />%s';
            $captionDescriptor = new CaptionDescriptor();
            $captionDescriptor
                ->setFormat('html')
                ->setText(sprintf($captionTemplate, $imageNode->get('description'), $credit));
            $item->setCaption($captionDescriptor);
        } elseif (!empty($credit)) {
            $item->setCaption(strip_tags($credit));
        }

        return $item;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformGoogleMapBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('center')) {
            return null;
        }

        $zoom = $block->get('zoom', 0);

        switch ($zoom) {
            case 1:
            case 2:
            case 3:
                $latitudeDelta = .039;
                $longitudeDelta = 123;
                break;
            case 4:
                $latitudeDelta = .035;
                $longitudeDelta = 73;
                break;
            case 5:
                $latitudeDelta = .021;
                $longitudeDelta = 35;
                break;
            case 6:
                $latitudeDelta = .0048;
                $longitudeDelta = 13;
                break;
            case 7:
                $latitudeDelta = .0034;
                $longitudeDelta = 7;
                break;
            case 8:
                $latitudeDelta = .0034;
                $longitudeDelta = 5;
                break;
            case 9:
                $latitudeDelta = .0034;
                $longitudeDelta = 2.0;
                break;
            case 10:
                $latitudeDelta = .0032;
                $longitudeDelta = 1.33;
                break;
            case 11:
                $latitudeDelta = .003;
                $longitudeDelta = .5;
                break;
            case 12:
                $latitudeDelta = .0018;
                $longitudeDelta = .25;
                break;
            case 13:
                $latitudeDelta = .0014;
                $longitudeDelta = .08;
                break;
            case 14:
                $latitudeDelta = .001;
                $longitudeDelta = .02;
                break;
            case 15:
            case 16:
                $latitudeDelta = .0006;
                $longitudeDelta = .018;
                break;
            default:
                $latitudeDelta = .0002;
                $longitudeDelta = .01;
                break;
        }

        /** @var GeoPoint $geoPoint */
        $geoPoint = $block->get('center');

        $mapItem = new MapItem();
        $mapItem
            ->setCaption($block->get('q'))
            ->setLatitude($geoPoint->getLatitude())
            ->setLongitude($geoPoint->getLongitude());

        $map = new Map();
        $map
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setLatitude($geoPoint->getLatitude())
            ->setLongitude($geoPoint->getLongitude())
            ->setCaption($block->get('q'));

        if ('satellite' === $block->get('maptype')) {
            $map->setMapType('hybrid');
        }

        $map->setItems([$mapItem]);

        $mapSpan = new MapSpan();
        $mapSpan
            ->setLatitudeDelta($latitudeDelta)
            ->setLongitudeDelta($longitudeDelta);

        $map->setSpan($mapSpan);

        return $map;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformHeadingBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('text')) {
            return null;
        }

        $component = new Heading();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setText($block->get('text'))
            ->setRole('heading');

        if ($block->get('size') > 0) {
            $component->setRole("heading{$block->get('size')}");
        }

        if ($block->has('url')) {
            $url = $block->get('url');
            $link = new Link();
            $link->setURL($url)->setRangeStart(0)->setRangeLength(strlen($block->get('text')));
            $component->addAddition($link);
        }

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformIframeBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformImageBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('node_ref')) {
            return null;
        }

        $imageUrl = $this->getImageUrl(
            $block->get('node_ref'),
            $block->get('aspect_ratio', AspectRatio::UNKNOWN),
            $block
        );
        $component = new Photo();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setURL($imageUrl)
            ->setExplicitContent($block->get('is_nsfw'));

        if ($block->has('caption')) {
            $component->setCaption(strip_tags($block->get('caption')));
        }

        if ($block->has('url')) {
            // Checking to see if url is a valid apple news url, itunes store, app store, apple podcast or canonical url
            // https://developer.apple.com/documentation/apple_news/componentlink
            $url = $block->get('url');
            $canonicalHost = parse_url($this->getCanonicalUrl($this->article), PHP_URL_HOST);
            $canonicalHost = str_replace(['.'], ['\.'], (string)$canonicalHost);
            if (0 === strpos($url, 'https://apple.news/')
                || 0 === strpos($url, 'https://geo.itunes.apple.com/')
                || preg_match('/^https:\/\/' . $canonicalHost . '\/\d{4}\/\d{2}\/\d{2}/', $url)
            ) {
                $link = new ComponentLink();
                $link->setURL($url);
                $component->addAddition($link);
            }
        }

        if ($context['is_first']) {
            $this->metadata->setThumbnailURL($imageUrl);
        }

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformImgurPostBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformInstagramMediaBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('id')) {
            return null;
        }

        $component = new Instagram();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setURL("https://www.instagram.com/p/{$block->get('id')}");

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformPageBreakBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformPinterestPinBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformPollBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformPollGridBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformQuoteBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('text')) {
            return null;
        }

        $component = $block->get('is_pull_quote') ? new PullQuote() : new Quote();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setText($block->get('text'));

        // fixme: source needs to be linked, not the full url, need to figure out how to display source text or if it will be added
        if ($block->has('source_url')) {
            $sourceUrl = $block->get('source_url');
            $link = new Link();
            $link->setURL($sourceUrl)->setRangeStart(0)->setRangeLength(strlen($sourceUrl));
            $component->addAddition($link);
        }

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformSoundcloudAudioBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformSpotifyEmbedBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformSpotifyTrackBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformTextBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('text')) {
            return null;
        }

        $component = new Body();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setText($block->get('text'))
            ->setFormat('html');

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformTiktokEmbedBlock(Message $block, array &$context): ?Component
    {
        return null;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformTwitterTweetBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('tweet_id') || !$block->has('screen_name')) {
            return null;
        }

        $tweetId = $block->get('tweet_id');
        $screenName = $block->get('screen_name');

        $component = new Tweet();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setURL("https://twitter.com/{$screenName}/status/{$tweetId}");

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformVideoBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('node_ref')) {
            return null;
        }

        /** @var NodeRef $nodeRef */
        $nodeRef = $block->get('node_ref');
        $node = $this->getNode($nodeRef);
        if (null === $node) {
            return null;
        }

        // Check to see if video url exists.  If url does not exist video component cannot be created.
        $videoUrl = $this->getVideoUrl($node);
        if (null === $videoUrl) {
            return null;
        }

        $caption = $block->get('launch_text')
            ?: $node->get('launch_text')
                ?: $node->get('description', '');

        $component = new Video();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setURL($videoUrl)
            ->setCaption(strip_tags($caption));

        /** @var NodeRef $imageRef */
        $imageRef = $block->get('poster_image_ref')
            ?: $node->get('poster_image_ref')
                ?: $node->get('image_ref');

        $imageUrl = $this->getImageUrl($imageRef, AspectRatio::R16BY9, $block);
        $component->setStillURL($imageUrl);

        if ($context['is_first']) {
            $this->metadata
                ->setVideoURL($component->getURL())
                ->setThumbnailURL($component->getStillURL());
        }

        return $component;
    }

    /**
     * @param Message $video
     *
     * @return string
     */
    protected function getVideoUrl(Message $video): ?string
    {
        return $video->get('kaltura_mp4_url');
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformVimeoVideoBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('id')) {
            return null;
        }

        $component = new EmbedWebVideo();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setRole('embedwebvideo')
            ->setURL("https://player.vimeo.com/video/{$block->get('id')}");

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformYoutubePlaylistBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('video_id')) {
            return null;
        }

        $videoId = $block->get('video_id');
        $playlistId = $block->get('playlist_id');

        $component = new EmbedWebVideo();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setRole('embedwebvideo')
            ->setURL("https://www.youtube.com/embed/{$videoId}?list={$playlistId}");

        return $component;
    }

    /**
     * @param Message $block
     * @param array   $context
     *
     * @return Component
     */
    protected function transformYoutubeVideoBlock(Message $block, array &$context): ?Component
    {
        if (!$block->has('id')) {
            return null;
        }

        $component = new EmbedWebVideo();
        $component
            ->setIdentifier($block->get('etag') . $context['idx'])
            ->setRole('embedwebvideo')
            ->setURL("https://www.youtube.com/embed/{$block->get('id')}");

        return $component;
    }
}
