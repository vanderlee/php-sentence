<?php

namespace Vanderlee\Sentence\Tests;

use PHPUnit_Framework_TestCase;
use Vanderlee\Sentence\Sentence;

class NumberPlaceholderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \Vanderlee\Sentence\Sentence::split
     */
    public function testNumberRestorationDoesNotConfuseValuesWithPlaceholderCodes()
    {
        $sentence = new Sentence();
        $text = 'Values 1 and 213 remain unchanged.';

        $this->assertSame([$text], $sentence->split($text));
    }
}
