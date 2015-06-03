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
	const SPLIT_TRIM		= 0x1;
	
	private $terminals		= array('.', '!', '?');
	private $abbreviators	= array('.');

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
		foreach ($chars as $index => $char) {
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
		$definite_terminals = array_diff($this->terminals, $this->abbreviators);
		
		$merges = array();
		$merge = '';

		foreach ($punctuations as $punctuation) {
			if ($punctuation !== '') {
				$merge.= $punctuation;
				if (mb_strlen($punctuation) === 1 && in_array($punctuation, $this->terminals)) {
					$merges[] = $merge;
					$merge = '';
				} else {
					foreach ($definite_terminals as $terminal) {
						if (mb_strpos($punctuation, $terminal) !== false) {
							$merges[] = $merge;
							$merge = '';
							break;
						}
					}
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
		$non_abbreviating_terminals = array_diff($this->terminals, $this->abbreviators);
		
		$abbreviations = array();
		
		$abbreviation = '';
		
		$previous_word_count = null;
		$previous_word_ending = null;		
		foreach ($fragments as $fragment) {
			$word_count = count(mb_split('\s+', self::mb_trim($fragment)));
			$starts_with_space = mb_ereg_match('^\s+', $fragment);			
			$after_non_abbreviating_terminal = in_array($previous_word_ending, $non_abbreviating_terminals);
			
			if ($after_non_abbreviating_terminal || ($previous_word_count !== null && ($previous_word_count !== 1 || $word_count !== 1) && $starts_with_space)) {
				$abbreviations[] = $abbreviation;
				$abbreviation = '';
			}

			$abbreviation			.= $fragment;					
			$previous_word_count	= $word_count;							
			$previous_word_ending	= mb_substr($fragment, -1);			
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
		$non_abbreviating_terminals = array_diff($this->terminals, $this->abbreviators);

		$sentences = array();

		$sentence = '';					
		$has_words = false;
		$previous_word_ending = null;
		foreach ($shorts as $short) {
			$word_count = count(mb_split('\s+', self::mb_trim($short)));			
			$after_non_abbreviating_terminal = in_array($previous_word_ending, $non_abbreviating_terminals);
			
			if ($after_non_abbreviating_terminal || ($has_words && $word_count > 1)) {
				$sentences[] = $sentence;
				$sentence = '';						
				$has_words = $word_count > 1;
			} else {
				$has_words = $has_words || $word_count > 1;						
			}
			
			$sentence.= $short;			
			$previous_word_ending = mb_substr($short, -1);					
		}
		if (!empty($sentence)) {
			$sentences[] = $sentence;
		}			

		return $sentences;
	}

	public function split($text, $flags = 0) {		
		$sentences = array();

		// Split
		foreach (self::linebreak_split($text) as $line) {				
			if (self::mb_trim($line) !== '') {
				$punctuations	= $this->punctuation_split($line);
				$merges			= $this->punctuation_merge($punctuations);
				$shorts			= $this->abbreviation_merge($merges);
				$sentences		= array_merge($sentences, $this->sentence_merge($shorts));
			}
		}
		
		// Post process
		if ($flags & self::SPLIT_TRIM) {
			foreach ($sentences as &$sentence) {
				$sentence = self::mb_trim($sentence);
			}
			unset($sentence);
		}

		return $sentences;
	}
	
	public function count($text) {
		return count($this->split($text));
	}
}