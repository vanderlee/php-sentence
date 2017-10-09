<?php

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
		$this->object = new Sentence;
	}

	/**
	 * @covers Sentence::count
	 */
	public function testCountEmpty()
	{
		$this->assertSame(0, $this->object->count(''));
		$this->assertSame(0, $this->object->count(' '));
		$this->assertSame(0, $this->object->count("\n"));
	}

	/**
	 * @covers Sentence::count
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
	 * @covers Sentence::count
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
	 * @covers Sentence::count
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
	 * @covers Sentence::count
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
	 * @covers Sentence::count
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
	 * @covers Sentence::count
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
	 * @covers Sentence::count
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
	 * @covers Sentence::split
	 */
	public function testSplitEmpty()
	{
		$this->assertSame(array(), $this->object->split(''));
		$this->assertSame(array(), $this->object->split(' '));
		$this->assertSame(array(), $this->object->split("\n"));
	}

	/**
	 * @covers Sentence::cleanupUnicode
	 */
	public function testCleanupUnicode()
	{
		$this->assertSame(array('Fix "these" quotes'), $this->object->split('Fix "these" quotes'));
		$this->assertSame(array('Fix "these" quotes'), $this->object->split("Fix \xC2\xABthese\xC2\xAB quotes"));
	}

	/**
	 * @covers Sentence::split
	 */
	public function testSplitWord()
	{
		$this->assertSame(array('Hello'), $this->object->split('Hello'));
		$this->assertSame(array('Hello.'), $this->object->split('Hello.'));
		$this->assertSame(array('Hello...'), $this->object->split('Hello...'));
		$this->assertSame(array('Hello!'), $this->object->split('Hello!'));
		$this->assertSame(array('Hello?'), $this->object->split('Hello?'));
		$this->assertSame(array('Hello?!'), $this->object->split('Hello?!'));
	}

	/**
	 * @covers Sentence::split
	 */
	public function testSplitMultipleWords()
	{
		$this->assertSame(array('Hello world.', ' Are you there'), $this->object->split('Hello world. Are you there'));
		$this->assertSame(array('Hello world.', ' Are you there?'), $this->object->split('Hello world. Are you there?'));
		$this->assertSame(array('Hello world.', 'Are you there'), $this->object->split('Hello world. Are you there', Sentence::SPLIT_TRIM));
		$this->assertSame(array('Hello world.', 'Are you there?'), $this->object->split('Hello world. Are you there?', Sentence::SPLIT_TRIM));
		$this->assertSame(array('Hello world, Are you there?'), $this->object->split('Hello world, Are you there?'));
		$this->assertSame(array('Hello world: Are you there?'), $this->object->split('Hello world: Are you there?'));
		$this->assertSame(array('Hello world... Are you there?'), $this->object->split('Hello world... Are you there?'));
	}

	/**
	 * @covers Sentence::split
	 */
	public function testSplitLinebreaks()
	{
		$this->assertSame(array("Hello world...\r", "Are you there?"), $this->object->split("Hello world...\rAre you there?"));
		$this->assertSame(array("Hello world...\n", " Are you there?"), $this->object->split("Hello world...\n Are you there?"));
		$this->assertSame(array("Hello world...\n", "Are you there?"), $this->object->split("Hello world...\nAre you there?"));
		$this->assertSame(array("Hello world...\r\n", "Are you there?"), $this->object->split("Hello world...\r\nAre you there?"));
		$this->assertSame(array("Hello world...\r\n\r", "Are you there?"), $this->object->split("Hello world...\r\n\rAre you there?"));
		$this->assertSame(array("Hello world...\n\r\n", "Are you there?"), $this->object->split("Hello world...\n\r\nAre you there?"));
		$this->assertSame(array("Hello world...\n\n", "Are you there?"), $this->object->split("Hello world...\n\nAre you there?"));
		$this->assertSame(array("Hello world...\r\r", "Are you there?"), $this->object->split("Hello world...\r\rAre you there?"));
	}

	/**
	 * @covers Sentence::split
	 */
	public function testSplitAbreviations()
	{
//		$this->markTestIncomplete('This test has not been implemented yet.');
		$this->assertSame(array('Hello mr. Smith.'), $this->object->split("Hello mr. Smith."));
		$this->assertSame(array('Hello, OMG Kittens!'), $this->object->split("Hello, OMG Kittens!"));
		$this->assertSame(array('Hello, abbrev. Kittens!'), $this->object->split("Hello, abbrev. Kittens!"));
		$this->assertSame(array('Hello, O.M.G. Kittens!'), $this->object->split("Hello, O.M.G. Kittens!"));
		$this->assertSame(array('Last week, former director of the A.B.C. John B. Smith was fired.'), $this->object->split("Last week, former director of the A.B.C. John B. Smith was fired."));
		$this->assertSame(array('Mr. Smith was not available for comment..'), $this->object->split("Mr. Smith was not available for comment.."));
		$this->assertSame(array('Hello mr. Smith.', ' Are you there?'), $this->object->split("Hello mr. Smith. Are you there?"));
	}

	/**
	 * @covers Sentence::split
	 */
	public function testSplitOneWordSentences()
	{
		$this->assertSame(array("You?", " Smith?"), $this->object->split("You? Smith?"));
		$this->assertSame(array("You there?", " Smith?"), $this->object->split("You there? Smith?"));
		$this->assertSame(array("You mr. Smith?"), $this->object->split("You mr. Smith?"));
		$this->assertSame(array("Are you there.", " Mister Smith?"), $this->object->split("Are you there. Mister Smith?"));
		$this->assertSame(array("Are you there.", " Smith, sir?"), $this->object->split("Are you there. Smith, sir?"));
		$this->assertSame(array("Are you there.", " Mr. Smith?"), $this->object->split("Are you there. Mr. Smith?"));
	}

	/**
	 * @covers Sentence::split
	 */
	public function testSplitParenthesis()
	{
		$this->assertSame(array("You there (not here!).", " Mister Smith"), $this->object->split("You there (not here!). Mister Smith"));
		$this->assertSame(array("You (not him!) here.", " Mister Smith"), $this->object->split("You (not him!) here. Mister Smith"));
		$this->assertSame(array("(What!) you here.", " Mister Smith"), $this->object->split("(What!) you here. Mister Smith"));
		$this->assertSame(array("You there (not here).", " Mister Smith"), $this->object->split("You there (not here). Mister Smith"));
		$this->assertSame(array("You (not him) here.", " Mister Smith"), $this->object->split("You (not him) here. Mister Smith"));
		$this->assertSame(array("(What) you here.", " Mister Smith"), $this->object->split("(What) you here. Mister Smith"));
	}

}
