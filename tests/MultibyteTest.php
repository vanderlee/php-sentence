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
     * @param $subject
     * @param $expected
     * @return void
     */
    public function testTrim($subject, $expected=null)
    {
        // Make excessive backtracking fail reliably on PHP 7.4 and newer.
        if (ini_get('mbstring.regex_retry_limit') !== false) {
            $this->iniSet('mbstring.regex_retry_limit', '1000');
        }
        if ($expected === null) {
            $expected = $subject;
        }
        $encoding = mb_regex_encoding();
        mb_regex_encoding('UTF-8');
        try {
            $actual = Multibyte::trim($subject);
        } catch (\Exception $exception) {
            mb_regex_encoding($encoding);
            throw $exception;
        }
        mb_regex_encoding($encoding);
        $this->assertSame($expected, $actual);
    }

    /**
     * @return array[]
     */
    public function dataTrim()
    {
        // Exact spacing from the reproducer in issue #27.
        $reported = '^ Old' . str_repeat(' ', 801) . '^ New' . str_repeat(' ', 1348) . '^';

        return [
            ['Foo bar', 'Foo bar'],
            [' Foo bar', 'Foo bar'],
            [' Foo bar ', 'Foo bar'],
            ['Foo bar ', 'Foo bar'],
            ['', ''],
            [" \t\r\n", ''],
            ["\t\r\nFoo bar\r\n\t", 'Foo bar'],
            [" Foo \n bar ", "Foo \n bar"],
            ["\xC2\xA0\xE3\x80\x80caf\xC3\xA9\xE3\x80\x80\xC2\xA0", "caf\xC3\xA9"],
            ["\xC2\xA0\xE3\x80\x80", ''],
            ['^ Old    ^  New       ^'],
            [$reported],
            [" \t" . $reported . "\r\n ", $reported],
            ['a' . str_repeat(' ', 20000) . 'b'],
            [str_repeat(' ', 20000), ''],
        ];
    }
}
