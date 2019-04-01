<?php

namespace Vanderlee\Sentence\Tests;

use Vanderlee\Sentence\Multibyte;

class MultibyteTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers       Sentence::count
     * @dataProvider dataSplit
     */
    public function testSplit($expected, $pattern, $subject, $limit = -1, $flags = 0)
    {
        $this->assertSame($expected, Multibyte::split($pattern, $subject, $limit, $flags));
    }

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
}
