<?php
declare(strict_types=1);

namespace Triniti\Tests\AppleNews;

use Acme\Schemas\Canvas\Block\ArticleBlockV1;
use Acme\Schemas\Canvas\Block\AudioBlockV1;
use Acme\Schemas\Canvas\Block\DividerBlockV1;
use Acme\Schemas\Canvas\Block\DocumentBlockV1;
use Acme\Schemas\Canvas\Block\FacebookPostBlockV1;
use Acme\Schemas\Canvas\Block\GalleryBlockV1;
use Acme\Schemas\Canvas\Block\GoogleMapBlockV1;
use Acme\Schemas\Canvas\Block\HeadingBlockV1;
use Acme\Schemas\Canvas\Block\ImageBlockV1;
use Acme\Schemas\Canvas\Block\InstagramMediaBlockV1;
use Acme\Schemas\Canvas\Block\QuoteBlockV1;
use Acme\Schemas\Canvas\Block\TextBlockV1;
use Acme\Schemas\Canvas\Block\TwitterTweetBlockV1;
use Acme\Schemas\Canvas\Block\VideoBlockV1;
use Acme\Schemas\Canvas\Block\VimeoVideoBlockV1;
use Acme\Schemas\Canvas\Block\YoutubePlaylistBlockV1;
use Acme\Schemas\Canvas\Block\YoutubeVideoBlockV1;
use Acme\Schemas\Curator\Node\GalleryV1;
use Acme\Schemas\Dam\Node\AudioAssetV1;
use Acme\Schemas\Dam\Node\ImageAssetV1;
use Acme\Schemas\Dam\Request\SearchAssetsResponseV1;
use Acme\Schemas\News\Node\ArticleV1;
use Acme\Schemas\Ovp\Node\VideoV1;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\GeoPoint;
use Gdbots\Pbj\WellKnown\NodeRef;
use Gdbots\Pbjx\Pbjx;
use Gdbots\Schemas\Ncr\Enum\NodeStatus;
use Gdbots\UriTemplate\UriTemplateService;
use Triniti\AppleNews\ArticleDocumentMarshaler;
use Triniti\AppleNews\Component\Audio;
use Triniti\AppleNews\Component\Body;
use Triniti\AppleNews\Component\Container;
use Triniti\AppleNews\Component\Divider;
use Triniti\AppleNews\Component\EmbedWebVideo;
use Triniti\AppleNews\Component\FacebookPost;
use Triniti\AppleNews\Component\Gallery;
use Triniti\AppleNews\Component\GalleryItem;
use Triniti\AppleNews\Component\Heading;
use Triniti\AppleNews\Component\Image;
use Triniti\AppleNews\Component\Instagram;
use Triniti\AppleNews\Component\Photo;
use Triniti\AppleNews\Component\Place;
use Triniti\AppleNews\Component\PullQuote;
use Triniti\AppleNews\Component\Quote;
use Triniti\AppleNews\Component\Tweet;
use Triniti\AppleNews\Component\Video;
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

class ArticleDocumentMarshalerTest extends AbstractPbjxTest
{
    protected Message $app;
    protected ArticleDocumentMarshaler $articleDocumentMarshaler;
    protected Message $notification;
    protected UrlProvider $urlProvider;

    /**
     * Create a ReflectionClass to make protected method public during tests
     *
     * @param string $name
     *
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    protected static function getMethod(string $name): \ReflectionMethod
    {
        $class = new \ReflectionClass('\Triniti\AppleNews\ArticleDocumentMarshaler');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function testMarshal(): void
    {
        $actual = $this->articleDocumentMarshaler->marshal($this->getContent());

        try {
            $actual->validate();
        } catch (\Assert\AssertionFailedException $afe) {
            $this->assertTrue(false);
        }

        $this->assertNotEmpty($actual->getComponents());
        $this->assertNotEmpty($actual->getComponentLayouts());
        $this->assertNotEmpty($actual->getComponentStyles());
        $this->assertNotEmpty($actual->getComponentTextStyles());
        $this->assertNotEmpty($actual->getIdentifier());
        $this->assertNotEmpty($actual->getLanguage());
        $this->assertNotEmpty($actual->getLayout());
        $this->assertNotEmpty($actual->getTitle());
    }

    public function testSetLayout(): void
    {
        $actual = $this->articleDocumentMarshaler->marshal($this->getContent());

        $expected = new Layout();
        $expected->setColumns(12)->setMargin(40)->setWidth(1024)->setGutter(20);

        //$actualLayout = $setLayout->invokeArgs($this->articleDocumentMarshaler, []);
        $this->assertEquals(
            $expected,
            $actual->getLayout(),
            'it should create a default triniti layout'
        );

        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($actual->getLayout()));
    }

    public function testGetDocumentStyle(): void
    {
        $actual = $this->articleDocumentMarshaler->marshal($this->getContent())->getDocumentStyle();

        $expected = new DocumentStyle();
        $expected->setBackgroundColor();

        $this->assertEquals(
            $expected,
            $actual,
            'documentStyle must be set with default background color'
        );
    }

    public function testGetTextStyles(): void
    {
        $actual = $this->articleDocumentMarshaler->marshal($this->getContent());

        $textStyle = new TextStyle();
        $textStyle->setTextColor('#000000');
        $expected = ['default' => $textStyle];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expected),
            json_encode($actual->getTextStyles()),
            'it should returns a array with default key'
        );
    }

    public function testGetComponentLayouts(): void
    {
        $actual = $this->articleDocumentMarshaler->marshal($this->getContent());

        $layout = new ComponentLayout();
        $margin = new Margin();
        $margin->setTop(0)->setBottom(17);
        $layout
            ->setColumnSpan(10)
            ->setColumnStart(1)
            ->setMargin($margin);

        $expected = [
            'default' => $layout,
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expected),
            json_encode($actual->getComponentLayouts()),
            'it should create a default triniti component layout'
        );
    }

    public function testGetComponentStyles(): void
    {
        $actual = $this->articleDocumentMarshaler->marshal($this->getContent());

        $style = new ComponentStyle();
        $expected = ['default' => $style];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expected),
            json_encode($actual->getComponentStyles()),
            'it should create a default triniti component style'
        );
    }

    public function testGetComponentTextStyles(): void
    {
        $actual = $this->articleDocumentMarshaler->marshal($this->getContent());

        $componentTextStyle = new ComponentTextStyle();
        $linkStyle = new TextStyle();
        $linkStyle->setTextColor('#cf0000');

        $componentTextStyle
            ->setFontName('MyriadPro-Regular')
            ->setFontSize(22)
            ->setTextColor('#464646')
            ->setLineHeight(30)
            ->setParagraphSpacingBefore(40)
            ->setLinkStyle($linkStyle);

        $expected = ['default-body' => $componentTextStyle];

        $this->assertJsonStringEqualsJsonString(
            json_encode($expected),
            json_encode($actual->getComponentTextStyles()),
            'it should create a default body triniti component text style'
        );
    }

    /**
     * Test to see if empty blocks does not create any components
     */
    public function testCreateComponentsEmpty(): void
    {
        $content = $this->getContent()->clear('blocks');
        $actual = $this->articleDocumentMarshaler->marshal($content);

        $this->assertSame(
            [],
            $actual->getComponents(),
            'it should return empty array if where were no blocks'
        );
    }

    public function testCreateComponents(): void
    {
        $actual = $this->articleDocumentMarshaler->marshal($this->getContent());

        $this->assertEquals(1, count($actual->getComponents()));
    }

    public function testTransformArticleBlock(): void
    {
        $node1 = ArticleV1::create()
            ->set('title', 'Article with an Article Block')
            ->set('status', NodeStatus::PUBLISHED());

        $this->ncr->putNode($node1);

        $etag = '0a2214175a51abae85922f73cbd0b1b1';
        $idx = 0;
        $expected1 = new Container();
        $expected1->setIdentifier($etag . $idx);

        $imageAssetNode = ImageAssetV1::create()
            ->set('_id', AssetId::fromString('image_jpg_20180906_78337d01b62e46c3ac4019b35810a834'));

        $imageUrl = $this->urlProvider->getUrl($imageAssetNode->get('_id'), '1by1', 'lg');
        $image = new Image();
        $image->setURL($imageUrl);
        $expected1->addComponent($image);
        $expected2 = clone $expected1;

        $linkText = 'view story';
        $block = new ArticleBlockV1();
        $block
            ->set('node_ref', NodeRef::fromNode($node1))
            ->set('image_ref', NodeRef::fromNode($imageAssetNode))
            ->set('show_image', true)
            ->set('link_text', $linkText)
            ->set('etag', $etag);
        $transformArticleBlock = self::getMethod('transformArticleBlock');
        $context = ['idx' => $idx];

        // without the Apple News Share Url
        $linkTextComponent1 = new Body();
        $url = UriTemplateService::expand(
            "{$node1::schema()->getQName()}.canonical",
            $node1->getUriTemplateVars()
        );
        $html = sprintf('<a href="%s">%s</a>', $url, $linkText);
        $linkTextComponent1
            ->setFormat('html')
            ->setText($html);
        $expected1->addComponent($linkTextComponent1);

        $this->assertEquals(
            $expected1,
            $transformArticleBlock->invokeArgs($this->articleDocumentMarshaler, [$block, &$context]),
            'it should transform article-block correctly without a Apple News Share Url'
        );

        // with the Apple News Share Url
        $linkTextComponent2 = new Body();
        $linkTextComponent2->setText($linkText);
        $expected2->addComponent($linkTextComponent2);

        $appleNewsShareUrl = 'https://apple.news/AlT0YBw7ZRDuxBxjsNj0-0w';
        $node2 = clone $node1;
        $node2->set('apple_news_share_url', $appleNewsShareUrl);
        $block->set('node_ref', NodeRef::fromNode($node2));

        $this->ncr->putNode($node2);

        $componentLinkAddition = new ComponentLink();
        $componentLinkAddition->setURL($appleNewsShareUrl);
        $expected2->addAddition($componentLinkAddition);

        $this->assertEquals(
            $expected2,
            $transformArticleBlock->invokeArgs($this->articleDocumentMarshaler, [$block, &$context]),
            'it should transform article-block correctly with a Apple News Share Url'
        );
    }

    public function testTransformAudioBlock(): void
    {
        $transformAudioBlock = self::getMethod('transformAudioBlock');

        $audioRef = NodeRef::fromString('acme:audio-asset:audio_mp3_20180906_78337d01b62e46c3ac4019b35810a834');
        $imageRef = NodeRef::fromString('acme:image-block:image_jpg_20180906_78337d01b62e46c3ac4019b35810a834');

        $audioUrl = $this->urlProvider->getUrl(AssetId::fromString('audio_mp3_20180906_78337d01b62e46c3ac4019b35810a834'));
        $imageUrl = $this->urlProvider->getUrl(AssetId::fromString('image_jpg_20180906_78337d01b62e46c3ac4019b35810a834'),
            '16by9',
            'lg');

        $audioBlock = new AudioBlockV1();
        $audioBlock->set('node_ref', $audioRef)->set('image_ref', $imageRef)->set('launch_text', 'launch')->set('etag',
            '123');

        $node = AudioAssetV1::create()
            ->set('_id', AssetId::fromString('audio_mp3_20180906_78337d01b62e46c3ac4019b35810a834'))
            ->set('status', NodeStatus::PUBLISHED())
            ->set('mime_type', 'audio/mp3');
        $this->ncr->putNode($node);

        $expected = new Audio();
        $expected
            ->setURL($audioUrl)
            ->setImageURL($imageUrl)
            ->setCaption('launch')
            ->setIdentifier('1230');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformAudioBlock->invokeArgs($this->articleDocumentMarshaler, [$audioBlock, &$context]),
            'it should transform audio-block correctly'
        );
    }

    public function testTransformDividerBlock(): void
    {
        $transformDividerBlock = self::getMethod('transformDividerBlock');

        $dividerBlock = DividerBlockV1::create()
            ->set('etag', 'etag')
            ->set('text', 'test')
            ->set('stroke_style', 'solid')
            ->set('stroke_color', 'primary');


        $strokeStyle = new StrokeStyle();
        $strokeStyle
            ->setStyle('solid');

        $expected = new Divider();
        $expected->setStroke($strokeStyle);

        $context = [];

        $this->assertEquals(
            $expected,
            $transformDividerBlock->invokeArgs($this->articleDocumentMarshaler, [$dividerBlock, &$context]),
            'it should transform divider block correctly'
        );
    }

    public function testTransformDocumentBlock(): void
    {
        $transformDocumentBlock = self::getMethod('transformDocumentBlock');
        $docRef = NodeRef::fromString('acme:document-asset:document_pdf_20190125_c1a42987b38048a0adf5ef965e5daa34');
        $docUrl = $this->urlProvider->getUrl(AssetId::fromString('document_pdf_20190125_c1a42987b38048a0adf5ef965e5daa34'));

        $documentBlock = new DocumentBlockV1();
        $documentBlock->set('node_ref', $docRef);
        $documentBlock->set('etag', '123');

        $expected = new Body();
        $downloadMessage = "Download Document";
        $documentText = sprintf('<a href="%s">%s</a>', $docUrl, $downloadMessage);
        $expected
            ->setText($documentText)
            ->setFormat('html')
            ->setIdentifier('1230');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformDocumentBlock->invokeArgs($this->articleDocumentMarshaler, [$documentBlock, &$context]),
            'it should transform document-block correctly'
        );
    }

    public function testTransformDocumentBlockWithImage(): void
    {
        $transformDocumentBlock = self::getMethod('transformDocumentBlock');
        $docRef = NodeRef::fromString('acme:document-asset:document_pdf_20190125_c1a42987b38048a0adf5ef965e5daa34');
        $docUrl = $this->urlProvider->getUrl(AssetId::fromString('document_pdf_20190125_c1a42987b38048a0adf5ef965e5daa34'));

        $imageRef = NodeRef::fromString('acme:image-block:image_jpg_20180906_78337d01b62e46c3ac4019b35810a834');
        $imageUrl = $this->urlProvider->getUrl(AssetId::fromString($imageRef->getId()), AspectRatio::R4BY3, 'lg');


        $documentBlock = new DocumentBlockV1();
        $documentBlock->set('node_ref', $docRef)->set('etag', '123')->set('image_ref', $imageRef);

        $expected = new Photo();
        $expected
            ->setIdentifier('1230')
            ->setURL($imageUrl)
            ->setCaption('Download Document');

        $link = new ComponentLink();
        $link->setURL($docUrl);
        $expected->addAddition($link);

        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformDocumentBlock->invokeArgs($this->articleDocumentMarshaler, [$documentBlock, &$context]),
            'it should transform document-block correctly'
        );
    }

    public function testTransformFacebookPostBlock(): void
    {
        $transformFacebookPostBlock = self::getMethod('transformFacebookPostBlock');

        $facebookPostBlock = FacebookPostBlockV1::create()->set('href',
            'https://www.facebook.com/post/12345')->set('etag', '123');

        $expected = new FacebookPost();
        $expected->setIdentifier('1230')->setURL('https://www.facebook.com/post/12345');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformFacebookPostBlock->invokeArgs($this->articleDocumentMarshaler, [$facebookPostBlock, &$context]),
            'it should transform facebook-post block correctly'
        );
    }

    public function testTransformGalleryBlock(): void
    {
        $transformGalleryBlock = self::getMethod('transformGalleryBlock');

        //ImageNode
        $imageNode = ImageAssetV1::create()
            ->set('_id', AssetId::create('image', 'jpg'))
            ->set('mime_type', 'image/jpg')
            ->set('status', NodeStatus::PUBLISHED());

        $mockPbjx = $this->getMockBuilder(Pbjx::class)->getMock();
        $searchAssetResponse = SearchAssetsResponseV1::create()->addToList('nodes', [$imageNode]);
        $mockPbjx->method('request')->willReturn($searchAssetResponse);

        //GalleryNode
        $galleryNode = GalleryV1::create()->set('status', NodeStatus::PUBLISHED());
        $galleryRef = NodeRef::fromNode($galleryNode);
        $this->ncr->putNode($galleryNode);

        $galleryBlock = GalleryBlockV1::create();
        $galleryBlock->set('node_ref', $galleryRef)->set('etag', '123');
        $context = ['is_first' => false, 'idx' => 0];

        $expected = new Gallery();
        $expected->setIdentifier('1230');
        $item = new GalleryItem();
        $item->setURL($this->urlProvider->getUrl($imageNode->get('_id'), 'o', 'lg'));
        $expected->setItems([$item]);

        $galleryArticleDocumentMarshaler = new ArticleDocumentMarshaler($this->ncr, $mockPbjx, $this->urlProvider);
        $this->assertEquals(
            $expected,
            $transformGalleryBlock->invokeArgs($galleryArticleDocumentMarshaler, [$galleryBlock, &$context]),
            'it should transform gallery block correctly'
        );
    }

    public function testTransformGoogleMapBlock(): void
    {
        $transformGoogleMapBlock = self::getMethod('transformGoogleMapBlock');

        $googleMapBlock = new GoogleMapBlockV1();
        $googleMapBlock
            ->set('etag', '123')
            ->set('q', "disneyland")
            ->set('center', GeoPoint::fromString('33.812092, -117.918976'));

        $expected = new Place();
        $expected
            ->setLongitude(-117.918976)
            ->setLatitude(33.812092)
            ->setCaption('disneyland')
            ->setIdentifier('1230');

        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformGoogleMapBlock->invokeArgs($this->articleDocumentMarshaler, [$googleMapBlock, &$context]),
            'it should transform google-map-block correctly'
        );
    }

    public function testTransformHeadingBlock(): void
    {
        $transformGoogleMapBlock = self::getMethod('transformHeadingBlock');

        $headingBlock = new HeadingBlockV1();
        $headingBlock->set('size', 2)->set('etag', '123')->set('text', 'heading');

        $expected = new Heading();
        $expected
            ->setIdentifier('1230')
            ->setText('heading')
            ->setRole('heading2');

        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformGoogleMapBlock->invokeArgs($this->articleDocumentMarshaler, [$headingBlock, &$context]),
            'it should transform google-map-block correctly'
        );
    }

    public function testTransformImageBlock(): void
    {
        $transformImageBlock = self::getMethod('transformImageBlock');

        $imageRef = NodeRef::fromString('acme:image-block:image_jpg_20180906_78337d01b62e46c3ac4019b35810a834');
        $imageUrl = $this->urlProvider->getUrl(AssetId::fromString('image_jpg_20180906_78337d01b62e46c3ac4019b35810a834'),
            '4by3',
            'lg');
        $imageBlock = ImageBlockV1::create()
            ->set('node_ref', $imageRef)
            ->set('is_nsfw', true)
            ->set('caption', 'wow check this out')
            ->set('etag', '123');

        $expected = new Photo();
        $expected
            ->setURL($imageUrl)
            ->setCaption('wow check this out')
            ->setExplicitContent(true)
            ->setIdentifier('1230');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformImageBlock->invokeArgs($this->articleDocumentMarshaler, [$imageBlock, &$context]),
            'it should transform image-block correctly'
        );
    }

    public function testTransformImageBlockNoUrl(): void
    {
        $transformImageBlock = self::getMethod('transformImageBlock');

        $imageRef = NodeRef::fromString('acme:image-block:image_jpg_20180906_78337d01b62e46c3ac4019b35810a834');
        $imageUrl = $this->urlProvider->getUrl(AssetId::fromString('image_jpg_20180906_78337d01b62e46c3ac4019b35810a834'),
            '4by3',
            'lg');

        $imageBlock = ImageBlockV1::create();
        $imageBlock->set('node_ref', $imageRef)->set('caption', 'wow world')->set('is_nsfw', true)->set('etag', '123');

        $expected = new Photo();
        $expected
            ->setURL($imageUrl)
            ->setCaption('wow world')
            ->setExplicitContent(true)
            ->setIdentifier('1230');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformImageBlock->invokeArgs($this->articleDocumentMarshaler, [$imageBlock, &$context]),
            'it should transform image-block correctly when there is no link url provided'
        );
    }

    public function testTransformInstagramMediaBlock(): void
    {
        $transformInstagramMediaBlock = self::getMethod('transformInstagramMediaBlock');

        $instagramMediaBlock = InstagramMediaBlockV1::create()->set('etag', '123')->set('id', 'instagram-id-123');

        $expected = new Instagram();
        $expected->setIdentifier('1230')->setURL('https://www.instagram.com/p/instagram-id-123');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformInstagramMediaBlock->invokeArgs($this->articleDocumentMarshaler,
                [$instagramMediaBlock, &$context]),
            'it should transform instagram-media-block correctly'
        );
    }

    public function testTransformQuoteBlock(): void
    {
        $transformQuoteBlock = self::getMethod('transformQuoteBlock');

        $quoteBlock = QuoteBlockV1::create()
            ->set('text', 'a quote in test')
            ->set('source', 'the store')
            ->set('etag', '123')
            ->set('source_url', 'https://www.example.com');

        $expected = new Quote();
        $link = new Link();
        $link->setURL('https://www.example.com')->setRangeStart(0)->setRangeLength(strlen('https://www.example.com'));
        $expected->setText('a quote in test')->addAddition($link)->setIdentifier('1230');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformQuoteBlock->invokeArgs($this->articleDocumentMarshaler, [$quoteBlock, &$context]),
            'it should transform quote-block correctly'
        );
    }

    public function testTransformQuoteBlockIsPullQuote(): void
    {
        $transformQuoteBlock = self::getMethod('transformQuoteBlock');

        $quoteBlock = QuoteBlockV1::create()
            ->set('etag', '123')
            ->set('text', 'a quote in test')
            ->set('source', 'the store')
            ->set('source_url', 'https://www.example.com')
            ->set('is_pull_quote', true);

        $expected = new PullQuote();
        $link = new Link();
        $link->setURL('https://www.example.com')->setRangeStart(0)->setRangeLength(strlen('https://www.example.com'));
        $expected->setIdentifier('1230')->setText('a quote in test')->addAddition($link);
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformQuoteBlock->invokeArgs($this->articleDocumentMarshaler, [$quoteBlock, &$context]),
            'it should transform quote-block correctly'
        );
    }

    public function testTransformTextBlock(): void
    {
        $transformTextBlock = self::getMethod('transformTextBlock');

        $textBlock = new TextBlockV1();
        $textBlock->set('etag', '123')->set('text', 'a text block text');

        $expected = new Body();
        $expected->setIdentifier('1230')->setText('a text block text')->setFormat('html');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformTextBlock->invokeArgs($this->articleDocumentMarshaler, [$textBlock, &$context]),
            'it should transform text-block correctly'
        );
    }

    public function testTransformTwitterTweetBlock(): void
    {
        $transformTwitterTweetBlock = self::getMethod('transformTwitterTweetBlock');

        $twitterTweetBlock = TwitterTweetBlockV1::fromArray([
            'tweet_id'    => '123',
            'screen_name' => 'theName',
            'etag'        => '123',
        ]);

        $expected = new Tweet();
        $expected->setIdentifier('1230')->setURL('https://twitter.com/theName/status/123');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformTwitterTweetBlock->invokeArgs($this->articleDocumentMarshaler, [$twitterTweetBlock, &$context]),
            'it should correctly transform twitter-tweet-block into a Tweet component'
        );
    }

    public function testTransformVideoBlock(): void
    {
        $transformVideoBlock = self::getMethod('transformVideoBlock');

        $videoNode = VideoV1::create();
        $videoNode
            ->set('etag', '123')
            ->set('kaltura_mp4_url', 'http://test.com/test.mp4')
            ->set('launch_text', 'caption')
            ->set('status', NodeStatus::PUBLISHED());

        $videoRef = NodeRef::fromNode($videoNode);
        $imageAssetRef = NodeRef::fromString('acme:image-block:image_jpg_20180906_78337d01b62e46c3ac4019b35810a834');
        $imageUrl = $this->urlProvider->getUrl(AssetId::fromString($imageAssetRef->getId()), '16by9', 'lg');

        $this->ncr->putNode($videoNode);

        //$videoRef = NodeRef::fromString('acme:video:1234');

        $videoBlock = VideoBlockV1::create()
            ->set('node_ref', $videoRef)
            ->set('poster_image_ref', $imageAssetRef)
            ->set('etag', '123');

        $expected = new Video();
        $expected
            ->setURL('http://test.com/test.mp4')
            ->setStillURL($imageUrl)
            ->setIdentifier('1230')
            ->setCaption('caption');

        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformVideoBlock->invokeArgs($this->articleDocumentMarshaler, [$videoBlock, &$context]),
            'it should transform video-block correctly'
        );
    }

    public function testTransformVideoBlockWithStillImage(): void
    {
        $transformVideoBlock = self::getMethod('transformVideoBlock');

        $imageAssetRef = NodeRef::fromString('acme:image-block:image_jpg_20180906_78337d01b62e46c3ac4019b35810a834');
        $imageUrl = $this->urlProvider->getUrl(AssetId::fromString('image_jpg_20180906_78337d01b62e46c3ac4019b35810a834'),
            '16by9',
            'lg');
        //$videoRef = NodeRef::fromString('acme:video:1234');
        $videoNode = VideoV1::create();
        $videoNode
            ->set('etag', '123')
            ->set('kaltura_mp4_url', 'http://test.com/test.mp4')
            ->set('image_ref', $imageAssetRef)
            ->set('status', NodeStatus::PUBLISHED());

        $videoRef = NodeRef::fromNode($videoNode);

        $this->ncr->putNode($videoNode);

        $videoBlock = VideoBlockV1::create()
            ->set('node_ref', $videoRef)
            ->set('poster_image_ref', $imageAssetRef)
            ->set('etag', '123');

        $expected = new Video();
        $expected
            ->setURL('http://test.com/test.mp4')
            ->setStillURL($imageUrl)
            ->setCaption('')
            ->setIdentifier('1230');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformVideoBlock->invokeArgs($this->articleDocumentMarshaler, [$videoBlock, &$context]),
            'it should transform video-block correctly'
        );
    }

    public function testTransformVimeoVideoBlock(): void
    {
        $transformVimeoVideoBlock = self::getMethod('transformVimeoVideoBlock');

        $vimeoVideoBlock = VimeoVideoBlockV1::fromArray([
            'etag' => '123',
            'id'   => '123123',
        ]);

        $expected = new EmbedWebVideo();
        $expected->setIdentifier('1230')->setURL('https://player.vimeo.com/video/123123')->setRole('embedwebvideo');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformVimeoVideoBlock->invokeArgs($this->articleDocumentMarshaler, [$vimeoVideoBlock, &$context]),
            'it should transform vimeo-video-block correctly'
        );
    }

    public function testTransformYoutubePlaylistBlock(): void
    {
        $transformYoutubeVideoBlock = self::getMethod('transformYoutubePlaylistBlock');

        $youtubePlaylistBlock = YoutubePlaylistBlockV1::fromArray([
            'video_id'    => 'abc123',
            'playlist_id' => 'def123',
        ]);
        $youtubePlaylistBlock->set('etag', '123');

        $expected = new EmbedWebVideo();
        $expected->setURL('https://www.youtube.com/embed/abc123?list=def123')->setRole('embedwebvideo')->setIdentifier('1230');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformYoutubeVideoBlock->invokeArgs($this->articleDocumentMarshaler,
                [$youtubePlaylistBlock, &$context]),
            'it should transform youtube-video-block correctly'
        );
    }

    public function testTransformYoutubeVideoBlock(): void
    {
        $transformYoutubeVideoBlock = self::getMethod('transformYoutubeVideoBlock');

        $youtubeVideoBlock = YoutubeVideoBlockV1::fromArray([
            'id'   => 'abc123',
            'etag' => '123',
        ]);

        $expected = new EmbedWebVideo();
        $expected->setIdentifier('1230')->setURL('https://www.youtube.com/embed/abc123')->setRole('embedwebvideo');
        $context = ['is_first' => false, 'idx' => 0];

        $this->assertEquals(
            $expected,
            $transformYoutubeVideoBlock->invokeArgs($this->articleDocumentMarshaler, [$youtubeVideoBlock, &$context]),
            'it should transform youtube-video-block correctly'
        );
    }

    protected function setup(): void
    {
        parent::setup();
        $this->urlProvider = new UrlProvider(
            [
                'default' => 'https://someurl',
                'image'   => 'https://someurl',
            ]
        );

        UriTemplateService::registerGlobals([
            'web_base_url' => 'https://www.acme.com/',
        ]);

        UriTemplateService::registerTemplates([
            'acme:article.canonical' => '{+web_base_url}{+slug}/',
        ]);

        $this->articleDocumentMarshaler = new ArticleDocumentMarshaler($this->ncr, $this->pbjx, $this->urlProvider);
    }

    /**
     * Create a content node
     */
    protected function getContent()
    {
        return ArticleV1::fromArray([
            "_schema"          => "pbj:acme:news:node:article:1-0-0",
            "_id"              => "1ccd64a3-e71d-48d7-9cc5-476facde7779",
            "status"           => "published",
            "etag"             => "67991520f5dedbdec055f1e568ba6d3d",
            "created_at"       => "1530837469596788",
            "updated_at"       => "1531440869366729",
            "title"            => "Hot Dog Champ Miki Sudo Wants Equal Pay, Not Attention, To Joey Chestnut",
            "published_at"     => "2018-07-13T00:14:29.310Z",
            "slug"             => "2018/08/08/cardi-b-files-15",
            "blocks"           => [
                [
                    "_schema" => "pbj:acme:canvas:block:text-block:1-0-0",
                    "etag"    => "58d55d134b9b140f26ce88265b2c9585",
                    "text"    => "<p><a href=\"http://www.google.com/\" rel=\"noopener noreferrer\">this is a block from unit test</a></p>",
                ],
            ],
            "seo_title"        => 'custom seo title',
            "seo_image_ref"    => null,
            "meta_description" => null,
            "meta_keywords"    => ['a', 'b', 'c'],
        ]);
    }
}
