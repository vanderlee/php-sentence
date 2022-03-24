<?php

namespace Vanderlee\Sentence\Tests;

use PHPUnit_Framework_TestCase;
use Vanderlee\Sentence\Sentence;

/**
 * @coversDefaultClass \Vanderlee\Sentence\Sentence
 */
class SentenceTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var Sentence
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new \Vanderlee\Sentence\Sentence();
    }

    /**
     * @covers ::count
     */
    public function testCountEmpty()
    {
        $this->assertSame(0, $this->object->count(''));
        $this->assertSame(0, $this->object->count(' '));
        $this->assertSame(0, $this->object->count("\n"));
    }

    /**
     * @covers ::count
     */
    public function testCountWord()
    {
        $this->assertSame(1, $this->object->count('Hello'));
        $this->assertSame(1, $this->object->count('Hello.'));
        $this->assertSame(1, $this->object->count('Hello...'));
        $this->assertSame(1, $this->object->count('Hello!'));
        $this->assertSame(1, $this->object->count('Hello?'));
        $this->assertSame(1, $this->object->count('Hello?!'));
    }

    /**
     * @covers ::count
     */
    public function testCountTwoWords()
    {
        $this->assertSame(1, $this->object->count('Hello world'));
        $this->assertSame(1, $this->object->count('Hello world.'));
        $this->assertSame(1, $this->object->count('Hello world...'));
        $this->assertSame(1, $this->object->count('Hello world!'));
        $this->assertSame(1, $this->object->count('Hello world?'));
        $this->assertSame(1, $this->object->count('Hello world?!'));
    }

    /**
     * @covers ::count
     */
    public function testCountMultipleWords()
    {
        $this->assertSame(2, $this->object->count('Hello world. Are you there'));
        $this->assertSame(2, $this->object->count('Hello world. Are you there?'));
        $this->assertSame(1, $this->object->count('Hello world, Are you there?'));
        $this->assertSame(1, $this->object->count('Hello world: Are you there?'));
        $this->assertSame(1, $this->object->count('Hello world... Are you there?'));
    }

    /**
     * @covers ::count
     */
    public function testCountLinebreaks()
    {
        $this->assertSame(2, $this->object->count("Hello world...\rAre you there?"));
        $this->assertSame(2, $this->object->count("Hello world...\nAre you there?"));
        $this->assertSame(2, $this->object->count("Hello world...\r\nAre you there?"));
        $this->assertSame(2, $this->object->count("Hello world...\r\n\rAre you there?"));
        $this->assertSame(2, $this->object->count("Hello world...\n\r\nAre you there?"));
        $this->assertSame(2, $this->object->count("Hello world...\n\nAre you there?"));
        $this->assertSame(2, $this->object->count("Hello world...\r\rAre you there?"));
    }

    /**
     * @covers ::count
     */
    public function testCountAbreviations()
    {
        $this->assertSame(1, $this->object->count("Hello mr. Smith."));
        $this->assertSame(1, $this->object->count("Hello, OMG Kittens!"));
        $this->assertSame(1, $this->object->count("Hello, abbrev. Kittens!"));
        $this->assertSame(1, $this->object->count("Hello, O.M.G. Kittens!"));
        $this->assertSame(1, $this->object->count("Last week, former director of the A.B.C. John B. Smith was fired."));
        $this->assertSame(1, $this->object->count("Mr. Smith was not available for comment.."));
    }

    /**
     * @covers ::count
     */
    public function testCountMultiplePunctuation()
    {
        $this->assertSame(2, $this->object->count("Hello there. Brave new world."));
        $this->assertSame(1, $this->object->count("Hello there... Brave new world."));
        $this->assertSame(2, $this->object->count("Hello there?... Brave new world."));
        $this->assertSame(2, $this->object->count("Hello there!... Brave new world."));
        $this->assertSame(2, $this->object->count("Hello there!!! Brave new world."));
        $this->assertSame(2, $this->object->count("Hello there??? Brave new world."));
    }

    /**
     * @covers ::count
     */
    public function testCountOneWordSentences()
    {
        $this->assertSame(2, $this->object->count("You? Smith?"));
        $this->assertSame(2, $this->object->count("You there? Smith?"));
        $this->assertSame(1, $this->object->count("You mr. Smith?"));
        $this->assertSame(2, $this->object->count("Are you there. Mister Smith?"));
        $this->assertSame(2, $this->object->count("Are you there. Smith, sir?"));
        $this->assertSame(2, $this->object->count("Are you there. Mr. Smith?"));
    }

    /**
     * @covers ::split
     */
    public function testSplitEmpty()
    {
        $this->assertSame([], $this->object->split(''));
        $this->assertSame([], $this->object->split(' '));
        $this->assertSame([], $this->object->split("\n"));
    }

    /**
     * @covers ::cleanupUnicode
     */
    public function testCleanupUnicode()
    {
        $this->assertSame(['Fix "these" quotes'], $this->object->split('Fix "these" quotes'));
        $this->assertSame(['Fix "these" quotes'], $this->object->split("Fix \xC2\xABthese\xC2\xAB quotes"));
    }

    /**
     * @covers ::split
     */
    public function testSplitWord()
    {
        $this->assertSame(['Hello'], $this->object->split('Hello'));
        $this->assertSame(['Hello.'], $this->object->split('Hello.'));
        $this->assertSame(['Hello...'], $this->object->split('Hello...'));
        $this->assertSame(['Hello!'], $this->object->split('Hello!'));
        $this->assertSame(['Hello?'], $this->object->split('Hello?'));
        $this->assertSame(['Hello?!'], $this->object->split('Hello?!'));
    }

    /**
     * @covers ::split
     */
    public function testSplitMultipleWords()
    {
        $this->assertSame(['Hello world.', ' Are you there'], $this->object->split('Hello world. Are you there'));
        $this->assertSame(['Hello world.', ' Are you there?'], $this->object->split('Hello world. Are you there?'));
        $this->assertSame(['Hello world.', 'Are you there'], $this->object->split('Hello world. Are you there', Sentence::SPLIT_TRIM));
        $this->assertSame(['Hello world.', 'Are you there?'], $this->object->split('Hello world. Are you there?', Sentence::SPLIT_TRIM));
        $this->assertSame(['Hello world, Are you there?'], $this->object->split('Hello world, Are you there?'));
        $this->assertSame(['Hello world: Are you there?'], $this->object->split('Hello world: Are you there?'));
        $this->assertSame(['Hello world... Are you there?'], $this->object->split('Hello world... Are you there?'));
    }

    /**
     * @covers ::split
     */
    public function testSplitLinebreaks()
    {
        $this->assertSame(["Hello world...\r", "Are you there?"], $this->object->split("Hello world...\rAre you there?"));
        $this->assertSame(["Hello world...\n", " Are you there?"], $this->object->split("Hello world...\n Are you there?"));
        $this->assertSame(["Hello world...\n", "Are you there?"], $this->object->split("Hello world...\nAre you there?"));
        $this->assertSame(["Hello world...\r\n", "Are you there?"], $this->object->split("Hello world...\r\nAre you there?"));
        $this->assertSame(["Hello world...\r\n\r", "Are you there?"], $this->object->split("Hello world...\r\n\rAre you there?"));
        $this->assertSame(["Hello world...\n\r\n", "Are you there?"], $this->object->split("Hello world...\n\r\nAre you there?"));
        $this->assertSame(["Hello world...\n\n", "Are you there?"], $this->object->split("Hello world...\n\nAre you there?"));
        $this->assertSame(["Hello world...\r\r", "Are you there?"], $this->object->split("Hello world...\r\rAre you there?"));
    }

    /**
     * @covers ::split
     */
    public function testSplitAbreviations()
    {
//		$this->markTestIncomplete('This test has not been implemented yet.');
        $this->assertSame(['Hello mr. Smith.'], $this->object->split("Hello mr. Smith."));
        $this->assertSame(['Hello, OMG Kittens!'], $this->object->split("Hello, OMG Kittens!"));
        $this->assertSame(['Hello, abbrev. Kittens!'], $this->object->split("Hello, abbrev. Kittens!"));
        $this->assertSame(['Hello, O.M.G. Kittens!'], $this->object->split("Hello, O.M.G. Kittens!"));
        $this->assertSame(['Last week, former director of the A.B.C. John B. Smith was fired.'], $this->object->split("Last week, former director of the A.B.C. John B. Smith was fired."));
        $this->assertSame(['Mr. Smith was not available for comment..'], $this->object->split("Mr. Smith was not available for comment.."));
        $this->assertSame(['Hello mr. Smith.', ' Are you there?'], $this->object->split("Hello mr. Smith. Are you there?"));
    }

    /**
     * @covers ::split
     */
    public function testSplitOneWordSentences()
    {
        $this->assertSame(["You?", " Smith?"], $this->object->split("You? Smith?"));
        $this->assertSame(["You there?", " Smith?"], $this->object->split("You there? Smith?"));
        $this->assertSame(["You mr. Smith?"], $this->object->split("You mr. Smith?"));
        $this->assertSame(["Are you there.", " Mister Smith?"], $this->object->split("Are you there. Mister Smith?"));
        $this->assertSame(["Are you there.", " Smith, sir?"], $this->object->split("Are you there. Smith, sir?"));
        $this->assertSame(["Are you there.", " Mr. Smith?"], $this->object->split("Are you there. Mr. Smith?"));
    }

    /**
     * @covers ::split
     */
    public function testSplitParenthesis()
    {
        $this->assertSame(["You there (not here!).", " Mister Smith"], $this->object->split("You there (not here!). Mister Smith"));
        $this->assertSame(["You (not him!) here.", " Mister Smith"], $this->object->split("You (not him!) here. Mister Smith"));
        $this->assertSame(["(What!) you here.", " Mister Smith"], $this->object->split("(What!) you here. Mister Smith"));
        $this->assertSame(["You there (not here).", " Mister Smith"], $this->object->split("You there (not here). Mister Smith"));
        $this->assertSame(["You (not him) here.", " Mister Smith"], $this->object->split("You (not him) here. Mister Smith"));
        $this->assertSame(["(What) you here.", " Mister Smith"], $this->object->split("(What) you here. Mister Smith"));
    }

    /**
     * @covers ::split
     */
    public function testSentenceWithNumericValues()
    {
        $this->assertSame(1, $this->object->count("The price is ￡25.50, including postage and packing."));
        $this->assertSame(1, $this->object->count("The price is 25.50, including postage and packing."));
        $this->assertSame(1, $this->object->count("I went true to size at 10.5 cms."));
        $this->assertSame(2, $this->object->count("The prices are ￡25.50 or ￡27.50, including postage and packing. I went true to size at 10.5 cms."));
    }

    /**
     * @covers ::floatNumberClean
     * @covers ::floatNumberRevert
     *
     * @dataProvider dataSplit
     *
     * @param string[] $expected
     * @param string   $text
     *
     * @return void
     */
    public function testSplit(array $expected, string $text)
    {
        $this->assertSame($expected, $this->object->split($text));
        $this->assertSame(count($expected), $this->object->count($text));
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
