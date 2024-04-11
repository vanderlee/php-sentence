<?php

namespace Vanderlee\Sentence\Tests;

use PHPUnit_Framework_TestCase;
use Vanderlee\Sentence\Multibyte;

/**
 * @coversDefaultClass \Vanderlee\Sentence\Multibyte
 */
class MultibyteTest extends PHPUnit_Framework_TestCase
{

    /**
     * @covers       Sentence::count
     * @dataProvider dataSplit
     */
    public function testSplit($expected, $pattern, $subject, $limit = -1, $flags = 0)
    {
        $this->assertSame($expected, Multibyte::split($pattern, $subject, $limit, $flags));
    }

    /**
     * @return array[]
     */
    public function dataSplit()
    {
        return [
            [['a', 'b', 'c'], '-', 'a-b-c'],
            [['a', 'b', 'c'], '-', 'a-b-c', 3],
            [['a', 'b', 'c'], '-', 'a-b-c', -1],
            [['a', 'b-c'], '-', 'a-b-c', 2],
            [['a-b-c'], '-', 'a-b-c', 1],
            [['a', 'b', 'c'], '-', 'a-b-c', -1, PREG_SPLIT_DELIM_CAPTURE],
            [['a', '-', 'b', '-', 'c'], '(-)', 'a-b-c', -1, PREG_SPLIT_DELIM_CAPTURE],
        ];
    }

    /**
     * @covers ::
     *
     * @dataProvider dataTrim
     * @param $subject
     * @param $expected
     * @return void
     */
    public function testTrim($subject, $expected=null)
    {
        if ($expected === null) {
            $expected = $subject;
        }
        $this->assertSame($expected, Multibyte::trim($subject));
    }

    /**
     * @return array[]
     */
    public function dataTrim()
    {
        return [
            ['Foo bar', 'Foo bar'],
            [' Foo bar', 'Foo bar'],
            [' Foo bar ', 'Foo bar'],
            ['Foo bar ', 'Foo bar'],
        ];
    }
}
