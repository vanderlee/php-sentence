<?php

namespace Vanderlee\Sentence;

/**
 * Multibyte-safe utility functions
 */
class Multibyte
{
    //https://stackoverflow.com/questions/20025030/convert-all-types-of-smart-quotes-with-php
    private static $unicodeCharacterMap = [
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
    ];

    /**
     * Replace
     *
     * @staticvar array $chr_map
     * @param string $string
     * @return string
     */
    public static function cleanUnicode($string)
    {
        $character = array_keys(self::$unicodeCharacterMap); // but: for efficiency you should
        $replace = array_values(self::$unicodeCharacterMap); // pre-calculate these two arrays
        return str_replace($character, $replace, html_entity_decode($string, ENT_QUOTES, "UTF-8"));
    }

    /**
     * Multibyte.php safe version of standard trim() function.
     *
     * @param string $string
     * @return string
     */
    public static function trim($string)
    {
        return mb_ereg_replace('^\s*([\s\S]*?)\s*$', '\1', $string);
    }

    /**
     * A cross between mb_split and preg_split, adding the preg_split flags
     * to mb_split.
     *
     * @param string $pattern
     * @param string $string
     * @param int $limit
     * @param int $flags
     * @return array
     */
    public static function split($pattern, $string, $limit = -1, $flags = 0)
    {
        $offset_capture = (bool)($flags & PREG_SPLIT_OFFSET_CAPTURE);

        $lengths = self::getSplitLengths($pattern, $string);

        // Substrings
        $parts = [];
        $position = 0;
        $count = 1;
        foreach ($lengths as $length) {
            if (self::isLastPart($length, $flags, $limit, $count)) {
                $parts[] = self::makePart($string, $position, null, $offset_capture);
                return $parts;
            }

            if (self::isPart($length, $flags)) {
                $parts[] = self::makePart($string, $position, $length[0], $offset_capture);
            }

            $position += $length[0];
        }

        return $parts;
    }

    /**
     * @param $length
     * @param $flags
     * @param $limit
     * @param $count
     * @return bool
     */
    private static function isLastPart($length, $flags, $limit, &$count)
    {
        $split_empty = !($flags & PREG_SPLIT_NO_EMPTY) || $length[0];
        $is_delimiter = $length[1];

        return $limit > 0
            && !$is_delimiter
            && $split_empty
            && ++$count > $limit;
    }

    /**
     * @param $length
     * @param $flags
     * @return bool
     */
    private static function isPart($length, $flags)
    {
        $split_empty = !($flags & PREG_SPLIT_NO_EMPTY) || $length[0];
        $is_delimiter = $length[1];
        $is_captured = ($flags & PREG_SPLIT_DELIM_CAPTURE) && $length[2];

        return (!$is_delimiter
                || $is_captured)
            && $split_empty;
    }

    /**
     * Make part
     * @param string $string
     * @param integer $position
     * @param integer|null $length
     * @param bool $offset_capture
     * @return array|string
     */
    private static function makePart($string, $position, $length = null, $offset_capture = false)
    {
        $cut = mb_strcut($string, $position, $length);

        return $offset_capture
            ? [$cut, $position]
            : $cut;
    }

    /**
     * Splits the string by pattern and for each element (part or split) returns:
     *  [ 0 => length, 1 => is_delimiter?, 2 =>
     *
     * @param $pattern
     * @param $string
     * @return array
     */
    private static function getSplitLengths($pattern, $string)
    {
        $strlen = strlen($string); // bytes!
        $lengths = [];

        mb_ereg_search_init($string);

        $position = 0;
        while ($position < $strlen
            && ($array = mb_ereg_search_pos($pattern, '')) !== false) {
            // capture split
            $lengths[] = [$array[0] - $position, false, null];

            // move position
            $position = $array[0] + $array[1];

            // capture delimiter
            $regs = mb_ereg_search_getregs();
            $lengths[] = [$array[1], true, isset($regs[1]) && $regs[1]];
        }

        // Add last bit, if not ending with split
        $lengths[] = [$strlen - $position, false, null];

        return $lengths;
    }
}