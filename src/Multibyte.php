<?php

namespace Vanderlee\Sentence;

/**
 * Multibyte-safe utility functions
 */
class Multibyte
{
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
        $split_no_empty = (bool)($flags & PREG_SPLIT_NO_EMPTY);
        $offset_capture = (bool)($flags & PREG_SPLIT_OFFSET_CAPTURE);
        $delim_capture = (bool)($flags & PREG_SPLIT_DELIM_CAPTURE);

        $lengths = self::getSplitLengths($pattern, $string);

        // Substrings
        $parts = [];
        $position = 0;
        $count = 1;
        foreach ($lengths as $length) {
            $split_empty = !$split_no_empty || $length[0];
            $is_delimiter = $length[1];
            $is_captured = $delim_capture && $length[2];

            if ($limit > 0
                && !$is_delimiter
                && $split_empty
                && ++$count > $limit) {

                $cut = mb_strcut($string, $position);

                $parts[] = $offset_capture
                    ? [$cut, $position]
                    : $cut;

                break;
            } elseif ((!$is_delimiter
                    || $is_captured)
                && $split_empty) {

                $cut = mb_strcut($string, $position, $length[0]);

                $parts[] = $offset_capture
                    ? [$cut, $position]
                    : $cut;
            }

            $position += $length[0];
        }

        return $parts;
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