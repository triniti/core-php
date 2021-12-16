<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys\Twig;

use Acme\Schemas\Sys\Node\FlagsetV1;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Schemas\Common\Enum\Trinary;
use PHPUnit\Framework\TestCase;
use Triniti\Sys\Flags;
use Triniti\Sys\Twig\FlagsExtension;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class FlagsExtensionTest extends TestCase
{
    private Environment $twig;
    protected Flags $flags;
    protected Message $flagset;
    protected Ncr $ncr;

    protected function setup(): void
    {
        $this->flagset = FlagsetV1::fromArray([
            '_id'       => 'test',
            'booleans'  => [
                'bool_false' => false,
                'bool_true'  => true,
            ],
            'floats'    => [
                'float_1' => -1.1,
                'float_2' => 0.0,
                'float_3' => 1.1,
            ],
            'ints'      => [
                'int_1' => 0,
                'int_2' => 1,
                'int_3' => 2,
            ],
            'strings'   => [
                'string_1' => 'val1',
                'string_2' => 'val2',
            ],
            'trinaries' => [
                'trinary_unknown' => Trinary::UNKNOWN->value,
                'trinary_true'    => Trinary::TRUE_VAL->value,
                'trinary_false'   => Trinary::FALSE_VAL->value,
            ],
        ]);

        $this->ncr = new InMemoryNcr();
        $this->ncr->putNode($this->flagset);
        $this->flags = new Flags($this->ncr, 'acme:flagset:test');

        $this->twig = new Environment(new ArrayLoader(), ['debug' => true]);
        $this->twig->addExtension(new FlagsExtension($this->flags));
    }

    public function testGet(): void
    {
        $types = [
            'booleans',
            'floats',
            'ints',
            'strings',
            'trinaries',
        ];

        foreach ($types as $type) {
            $values = $this->flagset->get($type);

            foreach ($values as $key => $val) {
                switch ($type) {
                    case 'booleans':
                        $template = sprintf('%s={{ flags_get_boolean(\'%s\') ? \'true\' : \'false\' }}', $key, $key);
                        $expected = sprintf('%s=%s', $key, $this->flags->getBoolean($key) ? 'true' : 'false');
                        break;

                    case 'floats':
                        $template = sprintf('%s={{ flags_get_float(\'%s\') }}', $key, $key);
                        $expected = sprintf('%s=%s', $key, $this->flags->getFloat($key));
                        break;

                    case 'ints':
                        $template = sprintf('%s={{ flags_get_int(\'%s\') }}', $key, $key);
                        $expected = sprintf('%s=%s', $key, $this->flags->getInt($key));
                        break;

                    case 'strings':
                        $template = sprintf('%s={{ flags_get_string(\'%s\') }}', $key, $key);
                        $expected = sprintf('%s=%s', $key, $this->flags->getString($key));
                        break;

                    case 'trinaries':
                        $template = sprintf('%s={{ flags_get_trinary(\'%s\') }}', $key, $key);
                        $expected = sprintf('%s=%s', $key, $this->flags->getTrinary($key));
                        break;

                    default:
                        $template = null;
                        $expected = null;
                        break;
                }

                $template = $this->twig->createTemplate($template);
                $actual = $template->render([]);
                $this->assertSame($expected, $actual);
            }
        }
    }

    public function testGetAll(): void
    {
        $expected = json_encode($this->flagset);
        $template = $this->twig->createTemplate('{{ flags_get_all()|json_encode|raw }}');
        $actual = $template->render([]);
        $this->assertSame($expected, $actual);
    }
}
