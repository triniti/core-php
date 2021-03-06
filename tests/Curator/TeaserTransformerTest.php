<?php

namespace Gdbots\Tests\Common;

use Acme\Schemas\Canvas\Block\TextBlockV1;
use Acme\Schemas\Curator\Node\ArticleTeaserV1;
use Acme\Schemas\News\Node\ArticleV1;
use Gdbots\Pbj\WellKnown\NodeRef;
use PHPUnit\Framework\TestCase;
use Triniti\Curator\TeaserTransformer;

class TeaserTransformerTest extends TestCase
{
    private array $simpleFieldNames = [
        'ads_enabled',
        'is_unlisted',
        'meta_description',
        'seo_title',
        'swipe',
        'theme',
        'title',
    ];

    private array $refFieldNames = [
        'channel_ref',
        'image_ref',
        'seo_image_ref',
        'sponsor_ref',
    ];

    public function testTransform() {
        $teaser = ArticleTeaserV1::create()
            ->set('ads_enabled', false)
            ->set('description', 'whatever')
            ->set('expires_at', \DateTime::createFromFormat('j-M-Y', '17-Jan-2029'))
            ->set('is_unlisted', false)
            ->set('meta_description', 'whatever')
            ->set('order_date', \DateTime::createFromFormat('j-M-Y', '15-Feb-2009'))
            ->set('seo_title', 'whatever')
            ->set('swipe', 'whatever')
            ->set('theme', 'whatever')
            ->set('title', 'whatever');

        $target = ArticleV1::create()
            ->addToList('blocks', [TextBlockV1::create()->set('text', 'equally-whatever')])
            ->set('ads_enabled', true)
            ->set('expires_at', \DateTime::createFromFormat('j-M-Y', '13-Mar-2129'))
            ->set('is_unlisted', true)
            ->set('meta_description', 'equally-whatever')
            ->set('order_date', \DateTime::createFromFormat('j-M-Y', '15-Feb-2010'))
            ->set('seo_title', 'equally-whatever')
            ->set('swipe', 'equally-whatever')
            ->set('theme', 'equally-whatever')
            ->set('title', 'equally-whatever');

        foreach ($this->simpleFieldNames as $fieldName) {
            $this->assertNotSame($teaser->get($fieldName), $target->get($fieldName));
        }

        foreach($this->refFieldNames as $fieldName) {
            $teaser->set($fieldName, NodeRef::fromString('not:a:real:noderef'));
            $target->set($fieldName, NodeRef::fromString('alsonot:a:real:noderef'));
            $this->assertNotSame($teaser->get($fieldName)->toString(), $target->get($fieldName)->toString());
        }

        $teaser = TeaserTransformer::transform($target, $teaser);

        foreach ($this->simpleFieldNames as $fieldName) {
            $this->assertSame($teaser->get($fieldName), $target->get($fieldName));
        }

        foreach($this->refFieldNames as $fieldName) {
            $this->assertSame($teaser->get($fieldName)->toString(), $target->get($fieldName)->toString());
        }

        $this->assertSame($teaser->get('description'), $target->get('blocks')[0]->get('text'));
    }
}
