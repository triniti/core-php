<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator\Twig;

use Acme\Schemas\Curator\Node\AdWidgetV1;
use Acme\Schemas\Curator\Node\CodeWidgetV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\WellKnown\NodeRef;
use Symfony\Component\HttpFoundation\RequestStack;
use Triniti\Curator\RenderWidgetRequestHandler;
use Triniti\Curator\Twig\CuratorExtension;
use Triniti\Schemas\Common\RenderContextV1;
use Triniti\Schemas\Curator\Enum\SlotRendering;
use Triniti\Schemas\Curator\Request\RenderWidgetRequestV1;
use Triniti\Tests\AbstractPbjxTest;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class CuratorExtensionTest extends AbstractPbjxTest
{
    protected Environment $twig;
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();
        $this->ncr = new InMemoryNcr();
        $loader = new FilesystemLoader(__DIR__ . '/../Fixtures/templates/');
        $loader->addPath(realpath(__DIR__ . '/../Fixtures/templates/'), 'curator_widgets');
        $this->twig = new Environment($loader, ['debug' => true]);
        $this->twig->addExtension(new CuratorExtension($this->pbjx, new RequestStack()));

        $this->locator->registerRequestHandler(
            RenderWidgetRequestV1::schema()->getCurie(),
            new RenderWidgetRequestHandler($this->ncr, $this->twig)
        );
    }

    public function testWithPlatform(): void
    {
        $widget = CodeWidgetV1::create()->set('code', 'test');
        $this->ncr->putNode($widget);

        $context = RenderContextV1::create()
            ->set('platform', 'web')
            ->set('device_view', 'smartphone');

        $actual = $this->twig->render('curator_render_widget.twig', [
            'pbj'     => NodeRef::fromNode($widget),
            'context' => $context,
        ]);
        $expected = "{$widget}{$context}";

        $this->assertSame(trim($expected), trim($actual));
    }

    public function testWithPlatformAndLazy(): void
    {
        $widget = CodeWidgetV1::create()->set('code', 'test');
        $this->ncr->putNode($widget);

        $context = RenderContextV1::create()
            ->set('platform', 'web')
            ->set('device_view', 'smartphone')
            ->addToMap('strings', 'rendering', SlotRendering::LAZY);

        $actual = $this->twig->render('curator_render_widget.twig', [
            'pbj'     => NodeRef::fromNode($widget),
            'context' => $context,
        ]);
        $expected = "LAZY={$widget}{$context}";

        $this->assertSame(trim($expected), trim($actual));
    }

    public function testAdWidgetWithPlatformAndLazy(): void
    {
        $widget = AdWidgetV1::create()->set('dfp_ad_unit_path', '/taco/spice');
        $this->ncr->putNode($widget);

        $context = RenderContextV1::create()
            ->set('platform', 'web')
            ->set('device_view', 'desktop')
            ->addToMap('strings', 'rendering', SlotRendering::LAZY);

        $actual = $this->twig->render('curator_render_widget.twig', [
            'pbj'     => NodeRef::fromNode($widget),
            'context' => $context,
        ]);
        $expected = "LAZY_AD_WIDGET={$widget}{$context}";

        $this->assertSame(trim($expected), trim($actual));
    }
}
