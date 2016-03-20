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
class Sentence
{

	/**
	 * Specify this flag with the split method to trim whitespace.
	 */
	const SPLIT_TRIM = 0x1;

	/**
	 * List of characters used to terminate sentences.
	 * @var array
	 */
	private $terminals = array('.', '!', '?');

	/**
	 * List of characters used for abbreviations.
	 * @var array
	 */
	private $abbreviators = array('.');

	/**
	 * Multibyte safe version of standard trim() function.
	 * @param string $string
	 * @return string
	 */
	private static function mbTrim($string)
	{
		return mb_ereg_replace('^\s*([\s\S]*?)\s*$', '\1', $string);
	}

	/**
	 * A cross between mb_split and preg_split, adding the preg_split flags
	 * to mb_split.
	 * @param string $pattern
	 * @param string $string
	 * @param int $limit
	 * @param int $flags
	 * @return array
	 */
	private static function mbSplit($pattern, $string, $limit = -1, $flags = 0)
	{
		$strlen = strlen($string);  // bytes!
		mb_ereg_search_init($string);

		$lengths = array();
		$position = 0;
		while (($array = mb_ereg_search_pos($pattern, '')) !== false) {
			// capture split
			$lengths[] = array($array[0] - $position, false, null);

			// move position
			$position = $array[0] + $array[1];

			// capture delimiter
			$regs = mb_ereg_search_getregs();
			$lengths[] = array($array[1], true, isset($regs[1]) && $regs[1]);

			// Continue on?
			if ($position >= $strlen) {
				break;
			}
		}

		// Add last bit, if not ending with split
		$lengths[] = array($strlen - $position, false, null);

		// Substrings
		$parts = array();
		$position = 0;
		$count = 1;
		foreach ($lengths as $length) {
			$is_delimiter = $length[1];
			$is_captured = $length[2];

			if ($limit > 0 && !$is_delimiter && ($length[0] || ~$flags & PREG_SPLIT_NO_EMPTY) && ++$count > $limit) {
				if ($length[0] > 0 || ~$flags & PREG_SPLIT_NO_EMPTY) {
					$parts[] = $flags & PREG_SPLIT_OFFSET_CAPTURE ? array(mb_strcut($string, $position), $position) : mb_strcut($string, $position);
				}
				break;
			} elseif ((!$is_delimiter || ($flags & PREG_SPLIT_DELIM_CAPTURE && $is_captured)) && ($length[0] || ~$flags & PREG_SPLIT_NO_EMPTY)) {
				$parts[] = $flags & PREG_SPLIT_OFFSET_CAPTURE ? array(mb_strcut($string, $position, $length[0]), $position) : mb_strcut($string, $position, $length[0]);
			}

			$position += $length[0];
		}

		return $parts;
	}

	/**
	 * Breaks a piece of text into lines by linebreak.
	 * Eats up any linebreak characters as if one.
	 * 
	 * Multibyte safe
	 * 
	 * @param string $text
	 * @return array
	 */
	private static function linebreakSplit($text)
	{
		$lines = array();
		$line = '';

		foreach (self::mbSplit('([\r\n]+)', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
			$line .= $part;
			if (self::mbTrim($part) === '') {
				$lines[] = $line;
				$line = '';
			}
		}
		$lines[] = $line;

		return $lines;
	}

	/**
	 * Splits an array of lines by (consecutive sequences of)
	 * terminals, keeping terminals.
	 *
	 * Multibyte safe (atleast for UTF-8)
	 *
	 * For example:
	 * 	"There ... is. More!"
	 * 		... becomes ...
	 * 	[ "There ", "...", " is", ".", " More", "!" ]
	 *
	 * @param array $lines
	 * @return array
	 */
	private function punctuationSplit($line)
	{
		$parts = array();

		$chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY); // This is UTF8 multibyte safe!
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
	 * 	[ "There ", "...", " is", ".", " More", "!" ]
	 * 		... becomes ...
	 * 	[ "There ... is.", "More!" ]
	 *
	 * @param array $punctuations
	 * @return array
	 */
	private function punctuationMerge($punctuations)
	{
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
	 * 	[ "There ... is.", "More!" ]
	 * 		... becomes ...
	 * 	[ "There ... is. More!" ]
	 *
	 * @param array $fragments
	 * @return array
	 */
	private function abbreviationMerge($fragments)
	{
		$non_abbreviating_terminals = array_diff($this->terminals, $this->abbreviators);

		$abbreviations = array();

		$abbreviation = '';

		$previous_word_count = null;
		$previous_word_ending = null;
		foreach ($fragments as $fragment) {
			$word_count = count(mb_split('\s+', self::mbTrim($fragment)));
			$starts_with_space = mb_ereg_match('^\s+', $fragment);
			$after_non_abbreviating_terminal = in_array($previous_word_ending, $non_abbreviating_terminals);

			if ($after_non_abbreviating_terminal || ($previous_word_count !== null && ($previous_word_count !== 1 || $word_count !== 1) && $starts_with_space)) {
				$abbreviations[] = $abbreviation;
				$abbreviation = '';
			}

			$abbreviation .= $fragment;
			$previous_word_count = $word_count;
			$previous_word_ending = mb_substr($fragment, -1);
		}
		if ($abbreviation !== '') {
			$abbreviations[] = $abbreviation;
		}

		return $abbreviations;
	}

	/**
	 * Merges any part starting with a closing parenthesis ')' to the previous
	 * part.
	 * @param type $parts
	 */
	private function parenthesesMerge($parts)
	{
		$subsentences = array();

		foreach ($parts as $part) {
			if ($part[0] === ')') {
				$subsentences[count($subsentences) - 1] .= $part;
			} else {
				$subsentences[] = $part;
			}
		}

		return $subsentences;
	}

	/**
	 * Merges items into larger sentences.
	 * 
	 * Multibyte safe
	 * 
	 * @param array $shorts
	 * @return array
	 */
	private function sentenceMerge($shorts)
	{
		$non_abbreviating_terminals = array_diff($this->terminals, $this->abbreviators);

		$sentences = array();

		$sentence = '';
		$has_words = false;
		$previous_word_ending = null;
		foreach ($shorts as $short) {
			$word_count = count(mb_split('\s+', self::mbTrim($short)));
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

	/**
	 * Return the sentences sentences detected in the provided text.
	 * Set the Sentence::SPLIT_TRIM flag to trim whitespace.
	 * @param string $text
	 * @param integer $flags
	 * @return array
	 */
	public function split($text, $flags = 0)
	{
		$sentences = array();

		// Split
		foreach (self::linebreakSplit($text) as $line) {
			if (self::mbTrim($line) !== '') {
				$punctuations = $this->punctuationSplit($line);
				$parentheses = $this->parenthesesMerge($punctuations); // also works after punctuationMerge or abbreviationMerge
				$merges = $this->punctuationMerge($parentheses);
				$shorts = $this->abbreviationMerge($merges);
				$sentences = array_merge($sentences, $this->sentenceMerge($shorts));
			}
		}

		// Post process
		if ($flags & self::SPLIT_TRIM) {
			foreach ($sentences as &$sentence) {
				$sentence = self::mbTrim($sentence);
			}
			unset($sentence);
		}

		return $sentences;
	}

	/**
	 * Return the number of sentences detected in the provided text.
	 * @param string $text
	 * @return integer
	 */
	public function count($text)
	{
		return count($this->split($text));
	}

}
