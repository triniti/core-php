<?php
declare(strict_types=1);

namespace Triniti\Tests\Canvas\Twig;

use Acme\Schemas\Canvas\Block\CodeBlockV1;
use Acme\Schemas\Canvas\Block\YoutubeVideoBlockV1;
use Gdbots\Pbj\Message;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Triniti\Canvas\Twig\CanvasExtension;
use Triniti\Schemas\Common\RenderContextV1;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class CanvasExtensionTest extends TestCase
{
    private Environment $twig;

    public function setup(): void
    {
        $loader = new FilesystemLoader([__DIR__ . '/../Fixtures/templates/']);
        $loader->addPath(realpath(__DIR__ . '/../Fixtures/templates/'), 'canvas_blocks');
        $this->twig = new Environment($loader, ['debug' => true]);
        $this->twig->addExtension(new CanvasExtension(new RequestStack()));
    }

    public function testWithPlatform(): void
    {
        $blocks = [
            CodeBlockV1::create()->set('code', 'Some <b>code</b>.'),
            YoutubeVideoBlockV1::create()->set('id', 'mYKLvYGqaC0'),
        ];

        $options = [
            'cache_enabled' => false,
            'platform'      => 'amp',
        ];

        $result = $this->twig->render('loader.twig', ['blocks' => $blocks, 'options' => $options]);
        $context = RenderContextV1::fromArray($options);

        $expected = array_map(function (Message $block) use ($context) {
            return "{$block}{$context}\n";
        }, $blocks);

        $this->assertSame(trim(implode('', $expected)), trim($result));
    }

    public function testWithDeviceViewAndSection(): void
    {
        $blocks = [
            CodeBlockV1::create()->set('code', 'Some <b>code</b>.'),
            YoutubeVideoBlockV1::create()->set('id', 'mYKLvYGqaC0'),
        ];

        $options = [
            'cache_enabled' => false,
            'device_view'   => 'smartphone',
            'section'       => 'blogroll',
        ];

        $result = $this->twig->render('loader.twig', ['blocks' => $blocks, 'options' => $options]);
        $context = RenderContextV1::fromArray($options);

        $expected = array_map(function (Message $block) use ($context) {
            return "{$block}{$context}\n";
        }, $blocks);

        $this->assertSame(trim(implode('', $expected)), trim($result));
    }

    public function testWithDeviceViewAndSectionAndInbetween(): void
    {
        $blocks = [
            CodeBlockV1::create()->set('code', 'Some <b>code</b>.'),
            CodeBlockV1::create()->set('code', 'Some other <b>code</b>.'),
            YoutubeVideoBlockV1::create()->set('id', 'mYKLvYGqaC0'),
        ];

        $options = [
            'cache_enabled' => false,
            'device_view'   => 'smartphone',
            'section'       => 'blogroll',
            'strings'       => [
                'inbetween_template' => 'inbetween.twig',
            ],
        ];

        $result = $this->twig->render('loader.twig', ['blocks' => $blocks, 'options' => $options]);
        $context = RenderContextV1::fromArray($options);

        $idx = 0;
        $expected = array_map(function (Message $block) use ($context, &$idx) {
            ++$idx;
            if ($idx < 3) {
                return "{$block}{$context}\ninbetween template {$idx}\n";
            }

            return "{$block}{$context}\n";
        }, $blocks);

        $this->assertSame(trim(implode('', $expected)), trim($result));
    }
}
