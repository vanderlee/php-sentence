<?php

namespace Vanderlee\Sentence\Tests;

use PHPUnit_Framework_TestCase;
use Vanderlee\Sentence\Sentence;

/**
 * @coversDefaultClass Sentence
 */
class SentenceTest extends PHPUnit_Framework_TestCase
{
    /**
     * @return Sentence
     */
    private function sentence()
    {
        return new Sentence();
    }

    /**
     * @covers ::count
     */
    public function testCountEmpty()
    {
        $this->assertSame(0, $this->sentence()->count(''));
        $this->assertSame(0, $this->sentence()->count(' '));
        $this->assertSame(0, $this->sentence()->count("\n"));
    }

    /**
     * @covers ::count
     */
    public function testCountWord()
    {
        $this->assertSame(1, $this->sentence()->count('Hello'));
        $this->assertSame(1, $this->sentence()->count('Hello.'));
        $this->assertSame(1, $this->sentence()->count('Hello...'));
        $this->assertSame(1, $this->sentence()->count('Hello!'));
        $this->assertSame(1, $this->sentence()->count('Hello?'));
        $this->assertSame(1, $this->sentence()->count('Hello?!'));
    }

    /**
     * @covers ::count
     */
    public function testCountTwoWords()
    {
        $this->assertSame(1, $this->sentence()->count('Hello world'));
        $this->assertSame(1, $this->sentence()->count('Hello world.'));
        $this->assertSame(1, $this->sentence()->count('Hello world...'));
        $this->assertSame(1, $this->sentence()->count('Hello world!'));
        $this->assertSame(1, $this->sentence()->count('Hello world?'));
        $this->assertSame(1, $this->sentence()->count('Hello world?!'));
    }

    /**
     * @covers ::count
     */
    public function testCountMultipleWords()
    {
        $this->assertSame(2, $this->sentence()->count('Hello world. Are you there'));
        $this->assertSame(2, $this->sentence()->count('Hello world. Are you there?'));
        $this->assertSame(1, $this->sentence()->count('Hello world, Are you there?'));
        $this->assertSame(1, $this->sentence()->count('Hello world: Are you there?'));
        $this->assertSame(1, $this->sentence()->count('Hello world... Are you there?'));
    }

    /**
     * @covers ::count
     */
    public function testCountLinebreaks()
    {
        $this->assertSame(2, $this->sentence()->count("Hello world...\rAre you there?"));
        $this->assertSame(2, $this->sentence()->count("Hello world...\nAre you there?"));
        $this->assertSame(2, $this->sentence()->count("Hello world...\r\nAre you there?"));
        $this->assertSame(2, $this->sentence()->count("Hello world...\r\n\rAre you there?"));
        $this->assertSame(2, $this->sentence()->count("Hello world...\n\r\nAre you there?"));
        $this->assertSame(2, $this->sentence()->count("Hello world...\n\nAre you there?"));
        $this->assertSame(2, $this->sentence()->count("Hello world...\r\rAre you there?"));
    }

    /**
     * @covers ::count
     */
    public function testCountAbbreviations()
    {
        $this->assertSame(1, $this->sentence()->count("Hello mr. Smith."));
        $this->assertSame(1, $this->sentence()->count("Hello, OMG Kittens!"));
        $this->assertSame(1, $this->sentence()->count("Hello, abbrev. Kittens!"));
        $this->assertSame(1, $this->sentence()->count("Hello, O.M.G. Kittens!"));
        $this->assertSame(1, $this->sentence()->count("Last week, former director of the A.B.C. John B. Smith was fired."));
        $this->assertSame(1, $this->sentence()->count("Mr. Smith was not available for comment.."));
    }

    /**
     * @covers ::count
     */
    public function testCountMultiplePunctuation()
    {
        $this->assertSame(2, $this->sentence()->count("Hello there. Brave new world."));
        $this->assertSame(1, $this->sentence()->count("Hello there... Brave new world."));
        $this->assertSame(2, $this->sentence()->count("Hello there?... Brave new world."));
        $this->assertSame(2, $this->sentence()->count("Hello there!... Brave new world."));
        $this->assertSame(2, $this->sentence()->count("Hello there!!! Brave new world."));
        $this->assertSame(2, $this->sentence()->count("Hello there??? Brave new world."));
    }

    /**
     * @covers ::count
     */
    public function testCountOneWordSentences()
    {
        $this->assertSame(2, $this->sentence()->count("You? Smith?"));
        $this->assertSame(2, $this->sentence()->count("You there? Smith?"));
        $this->assertSame(1, $this->sentence()->count("You mr. Smith?"));
        $this->assertSame(2, $this->sentence()->count("Are you there. Mister Smith?"));
        $this->assertSame(2, $this->sentence()->count("Are you there. Smith, sir?"));
        $this->assertSame(2, $this->sentence()->count("Are you there. Mr. Smith?"));
    }

    /**
     * @covers ::split
     */
    public function testSplitEmpty()
    {
        $this->assertSame([], $this->sentence()->split(''));
        $this->assertSame([], $this->sentence()->split(' '));
        $this->assertSame([], $this->sentence()->split("\n"));
    }

    /**
     * @covers ::cleanupUnicode
     */
    public function testCleanupUnicode()
    {
        $this->assertSame(['Fix "these" quotes'], $this->sentence()->split('Fix "these" quotes'));
        $this->assertSame(['Fix "these" quotes'], $this->sentence()->split("Fix \xC2\xABthese\xC2\xAB quotes"));
    }

    /**
     * @covers ::split
     */
    public function testPreserveOriginalUnicode()
    {
        $text = 'Super Tortas is outstanding! I haven’t had as many tortas as I would like.';

        $this->assertSame(
            ['Super Tortas is outstanding!', ' I haven’t had as many tortas as I would like.'],
            $this->object->split($text, Sentence::SPLIT_PRESERVE)
        );
        $this->assertSame(
            ['Super Tortas is outstanding!', 'I haven’t had as many tortas as I would like.'],
            $this->object->split($text, Sentence::SPLIT_PRESERVE | Sentence::SPLIT_TRIM)
        );
    }

    /**
     * @covers ::split
     */
    public function testSplitWord()
    {
        $this->assertSame(['Hello'], $this->sentence()->split('Hello'));
        $this->assertSame(['Hello.'], $this->sentence()->split('Hello.'));
        $this->assertSame(['Hello...'], $this->sentence()->split('Hello...'));
        $this->assertSame(['Hello!'], $this->sentence()->split('Hello!'));
        $this->assertSame(['Hello?'], $this->sentence()->split('Hello?'));
        $this->assertSame(['Hello?!'], $this->sentence()->split('Hello?!'));
    }

    /**
     * @covers ::split
     */
    public function testSplitMultipleWords()
    {
        $this->assertSame(['Hello world.', ' Are you there'], $this->sentence()->split('Hello world. Are you there'));
        $this->assertSame(['Hello world.', ' Are you there?'], $this->sentence()->split('Hello world. Are you there?'));
        $this->assertSame(['Hello world.', 'Are you there'], $this->sentence()->split('Hello world. Are you there', Sentence::SPLIT_TRIM));
        $this->assertSame(['Hello world.', 'Are you there?'], $this->sentence()->split('Hello world. Are you there?', Sentence::SPLIT_TRIM));
        $this->assertSame(['Hello world, Are you there?'], $this->sentence()->split('Hello world, Are you there?'));
        $this->assertSame(['Hello world: Are you there?'], $this->sentence()->split('Hello world: Are you there?'));
        $this->assertSame(['Hello world... Are you there?'], $this->sentence()->split('Hello world... Are you there?'));
    }

    /**
     * @covers ::split
     */
    public function testSplitLinebreaks()
    {
        $this->assertSame(["Hello world...\r", "Are you there?"], $this->sentence()->split("Hello world...\rAre you there?"));
        $this->assertSame(["Hello world...\n", " Are you there?"], $this->sentence()->split("Hello world...\n Are you there?"));
        $this->assertSame(["Hello world...\n", "Are you there?"], $this->sentence()->split("Hello world...\nAre you there?"));
        $this->assertSame(["Hello world...\r\n", "Are you there?"], $this->sentence()->split("Hello world...\r\nAre you there?"));
        $this->assertSame(["Hello world...\r\n\r", "Are you there?"], $this->sentence()->split("Hello world...\r\n\rAre you there?"));
        $this->assertSame(["Hello world...\n\r\n", "Are you there?"], $this->sentence()->split("Hello world...\n\r\nAre you there?"));
        $this->assertSame(["Hello world...\n\n", "Are you there?"], $this->sentence()->split("Hello world...\n\nAre you there?"));
        $this->assertSame(["Hello world...\r\r", "Are you there?"], $this->sentence()->split("Hello world...\r\rAre you there?"));
    }

    /**
     * @covers ::split
     */
    public function testSplitAbbreviations()
    {
        $this->assertSame(['Hello mr. Smith.'], $this->sentence()->split("Hello mr. Smith."));
        $this->assertSame(['Hello, OMG Kittens!'], $this->sentence()->split("Hello, OMG Kittens!"));
        $this->assertSame(['Hello, abbrev. Kittens!'], $this->sentence()->split("Hello, abbrev. Kittens!"));
        $this->assertSame(['Hello, O.M.G. Kittens!'], $this->sentence()->split("Hello, O.M.G. Kittens!"));
        $this->assertSame(['Last week, former director of the A.B.C. John B. Smith was fired.'], $this->sentence()->split("Last week, former director of the A.B.C. John B. Smith was fired."));
        $this->assertSame(['Mr. Smith was not available for comment..'], $this->sentence()->split("Mr. Smith was not available for comment.."));
        $this->assertSame(['Hello mr. Smith.', ' Are you there?'], $this->sentence()->split("Hello mr. Smith. Are you there?"));
    }

    /**
     * @covers ::split
     */
    public function testSplitOneWordSentences()
    {
        $this->assertSame(["You?", " Smith?"], $this->sentence()->split("You? Smith?"));
        $this->assertSame(["You there?", " Smith?"], $this->sentence()->split("You there? Smith?"));
        $this->assertSame(["You mr. Smith?"], $this->sentence()->split("You mr. Smith?"));
        $this->assertSame(["Are you there.", " Mister Smith?"], $this->sentence()->split("Are you there. Mister Smith?"));
        $this->assertSame(["Are you there.", " Smith, sir?"], $this->sentence()->split("Are you there. Smith, sir?"));
        $this->assertSame(["Are you there.", " Mr. Smith?"], $this->sentence()->split("Are you there. Mr. Smith?"));
    }

    /**
     * @covers ::split
     */
    public function testSplitParenthesis()
    {
        $this->assertSame(["You there (not here!).", " Mister Smith"], $this->sentence()->split("You there (not here!). Mister Smith"));
        $this->assertSame(["You (not him!) here.", " Mister Smith"], $this->sentence()->split("You (not him!) here. Mister Smith"));
        $this->assertSame(["(What!) you here.", " Mister Smith"], $this->sentence()->split("(What!) you here. Mister Smith"));
        $this->assertSame(["You there (not here).", " Mister Smith"], $this->sentence()->split("You there (not here). Mister Smith"));
        $this->assertSame(["You (not him) here.", " Mister Smith"], $this->sentence()->split("You (not him) here. Mister Smith"));
        $this->assertSame(["(What) you here.", " Mister Smith"], $this->sentence()->split("(What) you here. Mister Smith"));
    }

    /**
     * @covers ::split
     */
    public function testSentenceWithNumericValues()
    {
        $this->assertSame(1, $this->sentence()->count("The price is ￡25.50, including postage and packing."));
        $this->assertSame(1, $this->sentence()->count("The price is 25.50, including postage and packing."));
        $this->assertSame(1, $this->sentence()->count("I went true to size at 10.5 cms."));
        $this->assertSame(2, $this->sentence()->count("The prices are ￡25.50 or ￡27.50, including postage and packing. I went true to size at 10.5 cms."));
        $this->assertSame(1, $this->sentence()->count("Prices will go up for 8.6% and because of that it is expensive."));
    }

    /**
     * @covers ::replaceFloatNumbers
     * @covers ::restoreReplacements
     *
     * @dataProvider dataSplit
     *
     * @param string[] $expected
     * @param string   $text
     *
     * @return void
     */
    public function testSplit($expected, $text)
    {
        $this->assertSame($expected, $this->sentence()->split($text));
        $this->assertSame(count($expected), $this->sentence()->count($text));
    }

    public function dataSplit()
    {
        return [
            'repeat 2'                            => [
                [
                    'He got £2.',
                    ' He lost £2.',
                    ' He had £2.',
                ],
                'He got £2. He lost £2. He had £2.',
            ],
            'times'                               => [
                [
                    'If at 8:00 pm, do something, there is a good chance that by 8:45 pm we do something else.',
                    ' This is another sentence',
                ],
                'If at 8:00 pm, do something, there is a good chance that by 8:45 pm we do something else. This is another sentence',
            ],
            'lead/trailing zeroes'                => [
                [
                    'Number 00.20 it is',
                ],
                'Number 00.20 it is',
            ],
            'Bug report #15; ))) -1 index offset' => [
                [
                    ')))',
                ],
                ')))',
            ],
            'Price'                               => [
                [
                    'The price is 25.50, including postage and packing.',
                ],
                'The price is 25.50, including postage and packing.',
            ],
            'Recursive replacement'               => [
                [
                    'From 11 to 12.',
                    ' From 11 to 15.',
                ],
                'From 11 to 12. From 11 to 15.',
            ],
        ];
    }
}
