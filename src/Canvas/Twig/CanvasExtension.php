<?php
declare(strict_types=1);

namespace Triniti\Canvas\Twig;

use Gdbots\Pbj\Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Triniti\Schemas\Common\RenderContextV1;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CanvasExtension extends AbstractExtension
{
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    public function __construct(RequestStack $requestStack, ?LoggerInterface $logger = null)
    {
        $this->requestStack = $requestStack;
        $this->logger = $logger ?: new NullLogger();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('canvas_has_block', [$this, 'hasBlock']),
            new TwigFunction(
                'canvas_render_blocks',
                [$this, 'renderBlocks'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    public function hasBlock(string $name, array $blocks = []): bool
    {
        foreach ($blocks as $block) {
            if ($block::schema()->getCurie()->getMessage() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Environment   $twig
     * @param Message[]     $blocks
     * @param Message|array $context
     *
     * @return string
     *
     * @throws \Throwable
     */
    public function renderBlocks(Environment $twig, array $blocks, $context = []): string
    {
        $output = '';

        if (!$context instanceof Message) {
            $context['cache_enabled'] = $context['cache_enabled'] ?? false;
            $container = $context['container'] ?? null;
            if ($container instanceof Message) {
                unset($context['container']);
            }

            $context = RenderContextV1::fromArray($context);
            if ($container instanceof Message) {
                $context->set('container', $container);
            }
        }

        $this->enrichContext($context);
        $inbetweenTemplate = $context->getFromMap('strings', 'inbetween_template');
        $hasInbetween = null !== $inbetweenTemplate;
        $idx = 0;
        $total = count($blocks);
        $prevBlock = null;
        $prevBlockName = null;
        $rendered = [];

        foreach ($blocks as $block) {
            ++$idx;

            $curie = $block::schema()->getCurie();
            $blockName = str_replace('-', '_', $curie->getMessage());
            $name = $this->findTemplate($twig, $context, $blockName);
            $isFirst = $idx === 1;
            $isLast = $idx === $total;

            $nextBlock = $blocks[$idx] ?? null;
            $nextBlockName = $nextBlock
                ? str_replace('-', '_', $nextBlock::schema()->getCurie()->getMessage())
                : null;

            try {
                $output .= $twig->render($name, [
                    'pbj'            => $block,
                    'pbj_name'       => $blockName,
                    'prev_pbj'       => $prevBlock,
                    'prev_pbj_name'  => $prevBlockName,
                    'next_pbj'       => $nextBlock,
                    'next_pbj_name'  => $nextBlockName,
                    'rendered'       => $rendered,
                    'context'        => $context,
                    'idx'            => $idx,
                    'is_first'       => $isFirst,
                    'is_last'        => $isLast,
                    'total_blocks'   => $total,
                    'device_view'    => $context->get('device_view'),
                    'viewer_country' => $context->get('viewer_country'),
                ]);

                if ($hasInbetween && !$isLast) {
                    $output .= $twig->render($inbetweenTemplate, [
                        'pbj'            => $block,
                        'pbj_name'       => $blockName,
                        'prev_pbj'       => $prevBlock,
                        'prev_pbj_name'  => $prevBlockName,
                        'next_pbj'       => $nextBlock,
                        'next_pbj_name'  => $nextBlockName,
                        'rendered'       => $rendered,
                        'context'        => $context,
                        'idx'            => $idx,
                        'is_first'       => $isFirst,
                        'is_last'        => $isLast,
                        'total_blocks'   => $total,
                        'device_view'    => $context->get('device_view'),
                        'viewer_country' => $context->get('viewer_country'),
                    ]);
                }

                $rendered[$curie->getMessage()] = true;
                $rendered[$blockName] = true;
                $prevBlock = $block;
                $prevBlockName = $blockName;
            } catch (\Throwable $e) {
                if ($twig->isDebug()) {
                    throw $e;
                }

                $this->logger->warning(
                    'canvas_render_blocks failed to render [{curie}] with template [{twig_template}].',
                    [
                        'exception'      => $e,
                        'curie'          => $curie->toString(),
                        'twig_template'  => $name,
                        'pbj'            => $block->toArray(),
                        'render_context' => $context->toArray(),
                    ]
                );
            }
        }

        return $output;
    }

    private function findTemplate(Environment $twig, Message $context, string $blockName): string
    {
        $platform = $context->get('platform', 'web');
        $deviceView = $context->get('device_view', '');
        $format = $context->has('format') ? ".{$context->get('format')}" : '';
        $templates = [];

        if ($context->has('section')) {
            $section = strtolower(str_replace('-', '_', $context->get('section')));
            if ($context->has('device_view')) {
                $templates[] = "{$section}/{$blockName}/{$blockName}.{$deviceView}";
            }
            $templates[] = "{$section}/{$blockName}/{$blockName}";
        }

        if ($context->has('device_view')) {
            $templates[] = "{$blockName}/{$blockName}.{$deviceView}";
        }
        $templates[] = "{$blockName}/{$blockName}";

        $loader = $twig->getLoader();
        foreach ($templates as $template) {
            $name = "@canvas_blocks/{$platform}/{$template}{$format}.twig";
            if ($loader->exists($name)) {
                return $name;
            }
        }

        return "@canvas_blocks/{$platform}/missing_block{$format}.twig";
    }

    private function enrichContext(Message $context): void
    {
        if ($context->isFrozen()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        foreach (['device_view', 'viewer_country'] as $key) {
            if (!$context->has($key) && $request->attributes->has($key)) {
                $context->set($key, $request->attributes->get($key));
            }
        }
    }
}
