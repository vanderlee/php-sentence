<?php

/**
 * Segments sentences.
 * Clipping may not be perfect.
 * Sentence count should be VERY close to the truth.
 * 
 * Multibyte safe (atleast for UTF-8), but rules based on germanic
 * language stucture (English, Dutch, German). Should work for most
 * latin-alphabet languages.
 */
class Sentence {
	private $terminals = array('.', '!', '?');

	/**
	 * Multibyte safe version of standard trim() function.
	 * @param string $string
	 * @return string
	 */
	private static function mb_trim($string) {
		return mb_ereg_replace('^\s*([\s\S]*?)\s*$', '\1', $string);
	}

	/**
	 * Breaks a piece of text into lines by linebreak.
	 * 
	 * Multibyte safe
	 * 
	 * @param string $text
	 * @return array
	 */
	private static function linebreak_split($text) {
		return mb_split('\r\n|\r|\n', $text);
	}

	/**
	 * Splits an array of lines by (consecutive sequences of)
	 * terminals, keeping terminals.
	 * 
	 * Multibyte safe (atleast for UTF-8)
	 * 
	 * For example:
	 *	"There ... is. More!"
	 *		... becomes ...
	 *	[ "There ", "...", " is", ".", " More", "!" ]
	 * 
	 * @param array $lines
	 * @return array
	 */
	private function punctuation_split($line) {										
		$parts = array();

		$chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);	// This is UTF8 multibyte safe!
		$is_terminal = in_array($chars[0], $this->terminals);
		
		$part = '';
		foreach ($chars as $char) {
			if (in_array($char, $this->terminals) !== $is_terminal) {
				$parts[] = $part;
				$part = '';
				$is_terminal = !$is_terminal;
			}
			$part .= $char;							
		}
		
		if (!empty($part)) {
			$parts[] = $part;							
		}

		return $parts;
	}

	/**
	 * Appends each terminal item after it's preceding
	 * non-terminals.
	 * 
	 * Multibyte safe (atleast for UTF-8)
	 * 
	 * For example:
	 *	[ "There ", "...", " is", ".", " More", "!" ]
	 *		... becomes ...
	 *	[ "There ... is.", "More!" ]
	 * 
	 * @param array $punctuations
	 * @return array
	 */
	private function punctuation_merge($punctuations) {
		$merges = array();
		$merge = '';

		foreach ($punctuations as $punctuation) {
			if ($punctuation !== '') {
				$merge.= $punctuation;
				if (mb_strlen($punctuation) === 1 && in_array($punctuation, $this->terminals)) {
					$merges[] = $merge;
					$merge = '';
				}
			}			
		}
		if (!empty($merge)) {
			$merges[] = $merge;
		}

		return $merges;
	}

	/**
	 * Merges any one-word items with it's preceding items.
	 * 
	 * Multibyte safe
	 * 
	 * For example:
	 *	[ "There ... is.", "More!" ]
	 *		... becomes ...
	 *	[ "There ... is. More!" ]
	 * 
	 * @param array $fragments
	 * @return array
	 */
	private function abbreviation_merge($fragments) {
		$abbreviations = array();
		$abbreviation = '';

		$prevwordcount = null;
		foreach ($fragments as $fragment) {
			$wordcount = count(mb_split('\s+', self::mb_trim($fragment)));

			if ($prevwordcount !== null && ($prevwordcount !== 1 || $wordcount !== 1)) {
				$abbreviations[] = $abbreviation;
				$abbreviation = '';
			}

			$abbreviation.= $fragment;					
			$prevwordcount = $wordcount;							
		}
		if ($abbreviation !== '') {
			$abbreviations[] = $abbreviation;
		}

		return $abbreviations;
	}

	/**
	 * Merges items into larger sentences.
	 * 
	 * Multibyte safe
	 * 
	 * @param array $shorts
	 * @return array
	 */
	private function sentence_merge($shorts) {
		$sentences = array();

		$sentence = '';					
		$has_words = false;
		foreach ($shorts as $short) {
			$wordcount = count(mb_split('\s+', self::mb_trim($short)));

			if ($has_words && $wordcount > 1) {
				$sentences[] = $sentence;
				$sentence = '';						
				$has_words = $wordcount > 1;
			} else {
				$has_words = $has_words || $wordcount > 1;						
			}
			$sentence.= $short;
		}
		if (!empty($sentence)) {
			$sentences[] = $sentence;
		}			

		return $sentences;
	}

	public function split($text) {		
		$sentences = array();
		
		foreach (self::linebreak_split($text) as $line) {	
			$line = self::mb_trim($line);
			if (!empty($line)) {
				$punctuations	= $this->punctuation_split($line);
				$merge			= $this->punctuation_merge($punctuations);
				$shorts			= $this->abbreviation_merge($merge);
				$sentences		= array_merge($sentences, $this->sentence_merge($shorts));
			}
		}
var_dump($sentences);		

		return $sentences;
	}
	
	public function count($text) {
		return count($this->split($text));
	}
}