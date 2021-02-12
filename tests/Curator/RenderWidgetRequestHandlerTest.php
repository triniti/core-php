<?php
declare(strict_types=1);

namespace Triniti\Tests\Curator;

use Acme\Schemas\Curator\Node\CodeWidgetV1;
use Acme\Schemas\Curator\Request\RenderWidgetRequestV1;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Pbj\WellKnown\NodeRef;
use Symfony\Component\HttpFoundation\RequestStack;
use Triniti\Curator\RenderWidgetRequestHandler;
use Triniti\Curator\Twig\CuratorExtension;
use Triniti\Schemas\Common\RenderContextV1;
use Triniti\Tests\AbstractPbjxTest;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class RenderWidgetRequestHandlerTest extends AbstractPbjxTest
{
    protected Environment $twig;
    protected RenderWidgetRequestHandler $handler;
    protected InMemoryNcr $ncr;

    public function setup(): void
    {
        parent::setup();

        $this->ncr = new InMemoryNcr();
        $loader = new FilesystemLoader(__DIR__ . '/Fixtures/templates/');
        $loader->addPath(realpath(__DIR__ . '/Fixtures/templates/'), 'curator_widgets');
        $this->twig = new Environment($loader, ['debug' => true]);
        $this->twig->addExtension(new CuratorExtension($this->pbjx, new RequestStack()));

        $this->handler = new RenderWidgetRequestHandler($this->ncr, $this->twig);
    }

    public function testHandleRequest(): void
    {
        $widget = CodeWidgetV1::create()->set('code', 'test');
        $this->ncr->putNode($widget);

        $context = RenderContextV1::create()
            ->set('platform', 'web')
            ->set('device_view', 'smartphone');

        $request = RenderWidgetRequestV1::create()
            ->set('widget_ref', NodeRef::fromNode($widget))
            ->set('context', $context);

        /** @var Message $response */
        $response = $this->handler->handleRequest($request, $this->pbjx);

        $actual = $response->get('html');
        $expected = "{$widget}{$context}";

        $this->assertSame(trim($expected), trim($actual));
    }
}
