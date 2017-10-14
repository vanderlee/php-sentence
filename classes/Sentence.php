<?php

/**
 * Segments sentences.
 * Clipping may not be perfect.
 * Sentence count should be VERY close to the truth.
 *
 * Multibyte safe (atleast for UTF-8), but rules based on germanic
 * language stucture (English, Dutch, German). Should work for most
 * latin-alphabet languages.
 *
 * @author Martijn van der Lee (@vanderlee)
 * @author @marktaw
 */
class Sentence {

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
	 * Replace
	 * @staticvar array $chr_map
	 * @param String $string
	 * @return String
	 */
	private static function cleanUnicode($string)
	{
		//https://stackoverflow.com/questions/20025030/convert-all-types-of-smart-quotes-with-php
		static $character_map = array(
			// Windows codepage 1252
			"\xC2\x82" => "'", // U+0082⇒U+201A single low-9 quotation mark
			"\xC2\x84" => '"', // U+0084⇒U+201E double low-9 quotation mark
			"\xC2\x8B" => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
			"\xC2\x91" => "'", // U+0091⇒U+2018 left single quotation mark
			"\xC2\x92" => "'", // U+0092⇒U+2019 right single quotation mark
			"\xC2\x93" => '"', // U+0093⇒U+201C left double quotation mark
			"\xC2\x94" => '"', // U+0094⇒U+201D right double quotation mark
			"\xC2\x9B" => "'", // U+009B⇒U+203A single right-pointing angle quotation mark
			// Regular Unicode     // U+0022 quotation mark (")
			// U+0027 apostrophe     (')
			"\xC2\xAB" => '"', // U+00AB left-pointing double angle quotation mark
			"\xC2\xBB" => '"', // U+00BB right-pointing double angle quotation mark
			"\xE2\x80\x98" => "'", // U+2018 left single quotation mark
			"\xE2\x80\x99" => "'", // U+2019 right single quotation mark
			"\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
			"\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
			"\xE2\x80\x9C" => '"', // U+201C left double quotation mark
			"\xE2\x80\x9D" => '"', // U+201D right double quotation mark
			"\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
			"\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
			"\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
			"\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
		);

		$character = array_keys($character_map); // but: for efficiency you should
		$replace = array_values($character_map); // pre-calculate these two arrays
		return str_replace($character, $replace, html_entity_decode($string, ENT_QUOTES, "UTF-8"));
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
				$merge .= $punctuation;
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
	 * Looks for capitalized abbreviations & includes them with the following fragment.
	 *
	 * For example:
	 * 	[ "Last week, former director of the F.B.I. James B. Comey was fired. Mr. Comey was not available for comment." ]
	 * 		... becomes ...
	 * 	[ "Last week, former director of the F.B.I. James B. Comey was fired." ]
	 *  [ "Mr. Comey was not available for comment." ]
	 *
	 * @param array $fragments
	 * @return array
	 */
	private function abbreviationMerge($fragments)
	{
		$return_fragment = array();

		$previous_string = '';
		$previous_is_abbreviation = false;
		$i = 0;

		foreach ($fragments as $fragment) {
			$current_string = $fragment;
			$words = mb_split('\s+', self::mbTrim($fragment));

			$word_count = count($words);

			// if last word of fragment starts with a Capital, ends in "." & has less than 3 characters, trigger "is abbreviation"
			$last_word = trim($words[$word_count - 1]);
			$last_is_capital = preg_match('#^\p{Lu}#u', $last_word);
			$last_is_abbreviation = substr(trim($fragment), -1) == '.';
			if ($last_is_capital > 0 && $last_is_abbreviation > 0 && mb_strlen($last_word) <= 3) {
				$is_abbreviation = true;
			} else {
				$is_abbreviation = false;
			}
			// merge previous fragment with this
			if ($previous_is_abbreviation == true) {
				$current_string = $previous_string . $current_string;
			}
			$return_fragment[$i] = $current_string;


			$previous_is_abbreviation = $is_abbreviation;
			$previous_string = $current_string;
			// only increment if this isn't an abbreviation
			if ($is_abbreviation == false) {
				$i++;
			}
		}
		return $return_fragment;
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
	  Looks for closing quotes to include them with the previous statement.
	  "That was very interesting," he said.
	  "That was very interesting."
	 */
	private function closeQuotesMerge($statements)
	{
		$i = 0;
		$previous_statement = "";
		foreach ($statements as $statement) {
			// detect end quote - if the entire string is a quotation mark, or it's [quote, space, lowercase]
			if (trim($statement) == '"' || trim($statement) == "'" ||
					(
					( substr($statement, 0, 1) == '"' || substr($statement, 0, 1) == "'" )
					and substr($statement, 1, 1) == " "
					and ctype_lower(substr($statement, 2, 1)) == true
					)
			) {
				$statement = $previous_statement . $statement;
			} else {
				$i++;
			}

			$return[$i] = $statement;
			$previous_statement = $statement;
		}
		return($return);
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

			$sentence .= $short;
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

		// clean funny quotes
		$text = self::cleanUnicode($text);

		// Split
		foreach (self::linebreakSplit($text) as $line) {
			if (self::mbTrim($line) !== '') {
				$punctuations = $this->punctuationSplit($line);
				$parentheses = $this->parenthesesMerge($punctuations); // also works after punctuationMerge or abbreviationMerge
				$merges = $this->punctuationMerge($parentheses);
				$shorts = $this->abbreviationMerge($merges);
				$quotes = $this->closeQuotesMerge($shorts);
				$sentences = array_merge($sentences, $this->sentenceMerge($quotes));
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
