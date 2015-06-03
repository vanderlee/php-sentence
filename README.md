Sentence
========
[![Build Status](https://travis-ci.org/vanderlee/phpSentence.svg)](https://travis-ci.org/vanderlee/phpSentence)

Version 0.2

Copyright &copy; 2015 Martijn van der Lee.
MIT Open Source license applies.

Introduction
------------
PHP natural language sentence segmentation (splitting) and counting.
Sentence boundary disambiguation.

Still early, but should support most western languages.
If you find any problems, please let me know.

Supports PHP 5.2 and up, so you can use it on older servers.

Methods
-------
### ***`integer`*** `count(`***`string`*** `$text)`
Counts the number of sentences in the text.
Provided for convenience; this is exactly the same as counting the number of
returned array items from `split`, so if you need both results, just do that.

### ***`array`*** `split(`***`string`*** `$text, `***`integer`*** `$flags = 0)`
Splits the text into sentences.

`$flags` is zero (`0`, default) or the following class constant:

-	**`Sentence::SPLIT_TRIM`**: Trim whitespace off the left and right sides of
	each returned sentence.

Examples
========
	<?php

		// Include the autoloader.
		require_once 'classes/autoloader.php';

		// This is the test text we're going to use
		$text	= "Hello there, Mr. Smith. What're you doing today... Smith,"
				. " my friend?\n\nI hope it's good. This last sentence will"
				. " cost you $2.50! Just kidding :)";			

		// Create a new instance
		$Sentence	= new Sentence;

		// Split into array of sentences			
		$sentences	= $Sentence->split($text);			

		// Count the number of sentences
		$count		= $Sentence->count($text);

	?>

Background
==========
The method used is not based on any on the established or published methods.
It seems to work pretty well, though.

The method follows a number of simple steps in splitting and re-merging the
text into full sentences. You can easily check the steps in the code.

Though the splitting may be a bit off, in particular abbreviations at the start
of sentences tend to be merged with the preceding sentences. In most ordinary
text this should pose no problem. In either case this should not affect the
sentence count except in very uncommon situations.

It should be noted that this algorithm depends on reasonably gramatically
correct punctuation. Do not L33t-5p3ak!!!!!1!1!11!eleven!!