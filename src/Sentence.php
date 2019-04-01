<?php

namespace Vanderlee\Sentence;

/**
 * Segments sentences.
 * Clipping may not be perfect.
 * Sentence count should be VERY close to the truth.
 *
 * Multibyte.php safe (atleast for UTF-8), but rules based on germanic
 * language stucture (English, Dutch, German). Should work for most
 * latin-alphabet languages.
 *
 * @author Martijn van der Lee (@vanderlee)
 * @author @marktaw
 */
class Sentence
{

    /**
     * Specify this flag with the split method to trim whitespace.
     */
    const SPLIT_TRIM = 0x1;

    /**
     * List of characters used to terminate sentences.
     *
     * @var string[]
     */
    private $terminals = ['.', '!', '?'];

    /**
     * List of characters used for abbreviations.
     *
     * @var string[]
     */
    private $abbreviators = ['.'];

    /**
     * Breaks a piece of text into lines by linebreak.
     * Eats up any linebreak characters as if one.
     *
     * Multibyte.php safe
     *
     * @param string $text
     * @return string[]
     */
    private static function linebreakSplit($text)
    {
        $lines = [];
        $line = '';

        foreach (Multibyte::split('([\r\n]+)', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            $line .= $part;
            if (Multibyte::trim($part) === '') {
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
     * Multibyte.php safe (atleast for UTF-8)
     *
     * For example:
     *    "There ... is. More!"
     *        ... becomes ...
     *    [ "There ", "...", " is", ".", " More", "!" ]
     *
     * @param string $line
     * @return string[]
     */
    private function punctuationSplit($line)
    {
        $parts = [];

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
     * Multibyte.php safe (atleast for UTF-8)
     *
     * For example:
     *    [ "There ", "...", " is", ".", " More", "!" ]
     *        ... becomes ...
     *    [ "There ... is.", "More!" ]
     *
     * @param string[] $punctuations
     * @return string[]
     */
    private function punctuationMerge($punctuations)
    {
        $definite_terminals = array_diff($this->terminals, $this->abbreviators);

        $merges = [];
        $merge = '';

        $filtered = array_filter($punctuations, function ($p) {
            return $p !== '';
        });

        foreach ($filtered as $punctuation) {
            $merge .= $punctuation;
            if (mb_strlen($punctuation) === 1
                && in_array($punctuation, $this->terminals)) {
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
        if (!empty($merge)) {
            $merges[] = $merge;
        }

        return $merges;
    }

    /**
     * Looks for capitalized abbreviations & includes them with the following fragment.
     *
     * For example:
     *    [ "Last week, former director of the F.B.I. James B. Comey was fired. Mr. Comey was not available for comment." ]
     *        ... becomes ...
     *    [ "Last week, former director of the F.B.I. James B. Comey was fired." ]
     *  [ "Mr. Comey was not available for comment." ]
     *
     * @param string[] $fragments
     * @return string[]
     */
    private function abbreviationMerge($fragments)
    {
        $return_fragment = [];

        $previous_fragment = '';
        $previous_is_abbreviation = false;
        $i = 0;
        foreach ($fragments as $fragment) {
            $is_abbreviation = self::isAbreviation($fragment);

            // merge previous fragment with this
            if ($previous_is_abbreviation) {
                $fragment = $previous_fragment . $fragment;
            }
            $return_fragment[$i] = $fragment;

            $previous_is_abbreviation = $is_abbreviation;
            $previous_fragment = $fragment;

            // only increment if this isn't an abbreviation
            if (!$is_abbreviation) {
                $i++;
            }
        }
        return $return_fragment;
    }

    /**
     * Check if the last word of fragment starts with a Capital, ends in "." & has less than 3 characters.
     *
     * @param $fragment
     * @return bool
     */
    private static function isAbreviation($fragment)
    {
        $words = mb_split('\s+', Multibyte::trim($fragment));

        $word_count = count($words);

        $last_word = Multibyte::trim($words[$word_count - 1]);
        $last_is_capital = preg_match('#^\p{Lu}#u', $last_word);
        $last_is_abbreviation = mb_substr(Multibyte::trim($fragment), -1) === '.';

        return $last_is_capital > 0
            && $last_is_abbreviation > 0
            && mb_strlen($last_word) <= 3;
    }

    /**
     * Merges any part starting with a closing parenthesis ')' to the previous
     * part.
     *
     * @param string[] $parts
     * @return string[]
     */
    private function parenthesesMerge($parts)
    {
        $subsentences = [];

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
     * Looks for closing quotes to include them with the previous statement.
     * "That was very interesting," he said.
     * "That was very interesting."
     *
     * @param string[] $statements
     * @return string[]
     */
    private function closeQuotesMerge($statements)
    {
        $i = 0;
        $previous_statement = '';
        $return = [];
        foreach ($statements as $statement) {
            if (self::isEndQuote($statement)) {
                $statement = $previous_statement . $statement;
            } else {
                $i++;
            }

            $return[$i] = $statement;
            $previous_statement = $statement;
        }

        return $return;
    }

    /**
     * Check if the entire string is a quotation mark or quote, then space, then lowercase.
     *
     * @param $statement
     * @return bool
     */
    private static function isEndQuote($statement)
    {
        $trimmed = Multibyte::trim($statement);
        $first = mb_substr($statement, 0, 1);

        return in_array($trimmed, ['"', '\''])
            || (
                in_array($first, ['"', '\''])
                && mb_substr($statement, 1, 1) === ' '
                && ctype_lower(mb_substr($statement, 2, 1)) === true
            );
    }

    /**
     * Merges items into larger sentences.
     * Multibyte.php safe
     *
     * @param string[] $shorts
     * @return string[]
     */
    private function sentenceMerge($shorts)
    {
        $non_abbreviating_terminals = array_diff($this->terminals, $this->abbreviators);

        $sentences = [];

        $sentence = '';
        $has_words = false;
        $previous_word_ending = null;
        foreach ($shorts as $short) {
            $word_count = count(mb_split('\s+', Multibyte::trim($short)));
            $after_non_abbreviating_terminal = in_array($previous_word_ending, $non_abbreviating_terminals);

            if ($after_non_abbreviating_terminal
                || ($has_words && $word_count > 1)) {

                $sentences[] = $sentence;

                $sentence = '';
                $has_words = false;
            }

            $has_words = $has_words
                || $word_count > 1;

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
     * @return string[]
     */
    public function split($text, $flags = 0)
    {
        static $pipeline = [
            'punctuationSplit',
            'parenthesesMerge', // also works after punctuationMerge or abbreviationMerge
            'punctuationMerge',
            'abbreviationMerge',
            'closeQuotesMerge',
            'sentenceMerge',
        ];

        // clean funny quotes
        $text = Multibyte::cleanUnicode($text);

        // Split
        $sentences = [];
        foreach (self::linebreakSplit($text) as $input) {
            if (Multibyte::trim($input) !== '') {
                foreach ($pipeline as $method) {
                    $input = $this->$method($input);
                }
                $sentences = array_merge($sentences, $input);
            }
        }

        // Post process
        if ($flags & self::SPLIT_TRIM) {
            return self::trimSentences($sentences);
        }

        return $sentences;
    }

    /**
     * Multibyte.php trim each string in an array.
     * @param string[] $sentences
     * @return string[]
     */
    private static function trimSentences($sentences)
    {
        return array_map(function ($sentence) {
            return Multibyte::trim($sentence);
        }, $sentences);
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
