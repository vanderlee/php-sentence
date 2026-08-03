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
     * @covers ::trim
     *
     * @dataProvider dataTrim
     * @param string $subject
     * @param string|null $expected
     * @return void
     */
    public function testTrim($subject, $expected = null)
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
            ["\xC2\xA0Foo bar\xC2\xA0", 'Foo bar'],
        ];
    }

    /**
     * @covers ::trim
     */
    public function testTrimHandlesLongInputWithoutMbRegexRetryFailure()
    {
        $subject = str_repeat(' ', 100000).'Foo bar'.str_repeat(' ', 100000);

        $this->assertSame('Foo bar', Multibyte::trim($subject));
    }
}
