Sentence
========
[![License](https://img.shields.io/github/license/vanderlee/php-sentence.svg)]()
[![Build Status](https://travis-ci.org/vanderlee/php-sentence.svg?branch=master)](https://travis-ci.org/vanderlee/PHPSwaggerGen)
[![Quality](https://scrutinizer-ci.com/g/vanderlee/php-sentence/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vanderlee/PHPSwaggerGen)

Version 1.0.6

Copyright &copy; 2016-2021 Martijn van der Lee (@vanderlee), parts copyright &copy; 2017 @marktaw.

MIT Open Source license applies.

## Introduction
PHP natural language sentence segmentation (splitting) and counting.
Sentence boundary disambiguation.

Still early, but should support most western languages.
If you find any problems, please let me know.

Supports PHP 5.3 and up, so you can use it on older servers.

## Installation
Requires PHP 5.4 or greater. PHP 5.3 is supported as long as no more recent
features are absolutely necessary.

To install using Composer:

	composer require vanderlee/php-sentence
	
## Methods
### ***`integer`*** `count(`***`string`*** `$text)`
Counts the number of sentences in the text.
Provided for convenience; this is exactly the same as counting the number of
returned array items from `split`, so if you need both results, just do that.

### ***`array`*** `split(`***`string`*** `$text, `***`integer`*** `$flags = 0)`
Splits the text into sentences.

`$flags` is zero (`0`, default) or the following class constant:

-	**`Sentence::SPLIT_TRIM`**: Trim whitespace off the left and right sides of
	each returned sentence.

## Documentation
You can find documentation generated from the source code by ApiGen here: [ApiGen documentation](doc/)

# Examples
	<?php

		// This is the test text we're going to use
		$text	= "Hello there, Mr. Smith. What're you doing today... Smith,"
				. " my friend?\n\nI hope it's good. This last sentence will"
				. " cost you $2.50! Just kidding :)";

		// Create a new instance
		$Sentence	= new \Sentence;

		// Split into array of sentences
		$sentences	= $Sentence->split($text);

		// Count the number of sentences
		$count		= $Sentence->count($text);

	?>

# How it works
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

## Rules
The following is a rough list of the rules used to split sentences.

-	Each linebreak separates sentences.
-	The end of the text indicates the end if a sentence if not otherwise ended
	through proper punctuation.
-	Sentences must be at least two words long, unless a linebreak or end-of-text.
-	An empty line is not a sentence.
-	Each question- or exclamation mark or combination thereof, is considered
	the end of a sentence.
-	A single period is considered the end of a sentence, unless...
	-	It is preceded by one word, or...
	-	It is followed by one word.
-	A sequence of multiple periods is not considered the end of a sentence.

