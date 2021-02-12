<?php
declare(strict_types=1);

namespace Triniti\Tests\Sys;

use Acme\Schemas\Sys\Node\FlagsetV1;
use Gdbots\Ncr\Ncr;
use Gdbots\Ncr\Repository\InMemoryNcr;
use Gdbots\Pbj\Message;
use Gdbots\Schemas\Common\Enum\Trinary;
use PHPUnit\Framework\TestCase;
use Triniti\Sys\Flags;

final class FlagsTest extends TestCase
{
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
                'trinary_unknown' => Trinary::UNKNOWN,
                'trinary_true'    => Trinary::TRUE_VAL,
                'trinary_false'   => Trinary::FALSE_VAL,
            ],
        ]);

        $this->ncr = new InMemoryNcr();
        $this->ncr->putNode($this->flagset);
    }

    public function testGet(): void
    {
        $flags = new Flags($this->ncr, 'acme:flagset:test');

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
                        $actual = $flags->getBoolean($key);
                        break;

                    case 'floats':
                        $actual = $flags->getFloat($key);
                        break;

                    case 'ints':
                        $actual = $flags->getInt($key);
                        break;

                    case 'strings':
                        $actual = $flags->getString($key);
                        break;

                    case 'trinaries':
                        $actual = $flags->getTrinary($key);
                        break;

                    default:
                        $actual = null;
                        break;
                }

                $this->assertSame($val, $actual);
            }

            $this->assertSame($values, $flags->getAll()->get($type));
        }
    }

    public function testGetAll(): void
    {
        $flags = new Flags($this->ncr, 'acme:flagset:test');
        $this->assertTrue($this->flagset->equals($flags->getAll()));
    }

    public function testGetDefaults(): void
    {
        $flags = new Flags($this->ncr, 'acme:flagset:non-existent-id');

        $expected = true;
        $actual = $flags->getBoolean('missing', $expected);
        $this->assertSame($expected, $actual);

        $expected = 3.14;
        $actual = $flags->getFloat('missing', $expected);
        $this->assertSame($expected, $actual);

        $expected = 314;
        $actual = $flags->getInt('missing', $expected);
        $this->assertSame($expected, $actual);

        $expected = 'default';
        $actual = $flags->getString('missing', $expected);
        $this->assertSame($expected, $actual);

        $expected = Trinary::FALSE_VAL;
        $actual = $flags->getTrinary('missing', $expected);
        $this->assertSame($expected, $actual);
    }
}
