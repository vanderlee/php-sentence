from pathlib import Path

source = Path('src/Sentence.php')
text = source.read_text(encoding='utf-8')

old = """    private static function isAbbreviation($fragment)
    {
        $words = mb_split('\\s+', Multibyte::trim($fragment));

        $word_count = count($words);

        $last_word = Multibyte::trim($words[$word_count - 1]);
        $last_is_capital = preg_match('#^\\p{Lu}#u', $last_word);
        $last_is_abbreviation = mb_substr(Multibyte::trim($fragment), -1) === '.';

        return $last_is_capital > 0
            && $last_is_abbreviation > 0
            && mb_strlen($last_word) <= 3;
    }
"""
new = """    private static function isAbbreviation($fragment)
    {
        $words = mb_split('\\s+', Multibyte::trim($fragment));
        $lastWord = Multibyte::trim($words[count($words) - 1]);

        if (mb_substr($lastWord, -1) !== '.') {
            return false;
        }

        $token = mb_substr($lastWord, 0, -1);
        $known = [
            'dr', 'e.g', 'etc', 'i.e', 'jr', 'mr', 'mrs', 'ms', 'no',
            'prof', 'sr', 'st', 'vs',
        ];

        return in_array(mb_strtolower($token, 'UTF-8'), $known, true)
            || preg_match('#^\\p{L}$#u', $token) > 0
            || preg_match('#^(?:\\p{L}\\.){2,}$#u', $lastWord) > 0
            || preg_match('#^[ivxlcdm]+$#i', $token) > 0
            || preg_match('#^\\d+$#', $token) > 0;
    }
"""
if text.count(old) != 1:
    raise SystemExit('Expected abbreviation method exactly once')
text = text.replace(old, new, 1)

old = """            $word_count = count(mb_split('\\s+', Multibyte::trim($short)));
            $after_non_abbreviating_terminal = in_array($previous_word_ending, $non_abbreviating_terminals);

            if ($after_non_abbreviating_terminal
                || ($has_words && $word_count > 1)) {
"""
new = """            $word_count = count(mb_split('\\s+', Multibyte::trim($short)));
            $after_non_abbreviating_terminal = in_array($previous_word_ending, $non_abbreviating_terminals);
            $after_single_word_period = $previous_word_ending === '.'
                && !$has_words
                && $sentence !== '';

            if ($after_non_abbreviating_terminal
                || $after_single_word_period
                || ($has_words && $word_count > 1)) {
"""
if text.count(old) != 1:
    raise SystemExit('Expected sentence merge anchor exactly once')
source.write_text(text.replace(old, new, 1), encoding='utf-8')

tests = Path('tests/SentenceTest.php')
test_text = tests.read_text(encoding='utf-8')

old = "        $this->assertSame(2, $this->object->count(\"You? Smith?\"));\n"
new = old + """        $this->assertSame(3, $this->object->count("Go. Stop. Wait."));
        $this->assertSame(3, $this->object->count("See it. Report it. Sorted."));
"""
if test_text.count(old) != 1:
    raise SystemExit('Expected count regression anchor exactly once')
test_text = test_text.replace(old, new, 1)

old = "        $this->assertSame([\"You?\", \" Smith?\"], $this->object->split(\"You? Smith?\"));\n"
new = old + """        $this->assertSame(["Go.", " Stop.", " Wait."], $this->object->split("Go. Stop. Wait."));
        $this->assertSame(["See it.", " Report it.", " Sorted."], $this->object->split("See it. Report it. Sorted."));
"""
if test_text.count(old) != 1:
    raise SystemExit('Expected split regression anchor exactly once')
test_text = test_text.replace(old, new, 1)

marker = """    /**
     * @covers ::split
     */
    public function testSplitParenthesis()
"""
addition = """    /**
     * @covers ::split
     */
    public function testOrderedListMarkersStayWithTheirItems()
    {
        $numeric = "1. Set Lofty Goals.\n2. Visualize Success.\n3. Learn from Others.";
        $roman = "I. Set Lofty Goals.\nII. Visualize Success.\nXI. Learn from Others.";
        $letters = "a. Set Lofty Goals.\nb. Visualize Success.\nc. Learn from Others.";

        $this->assertSame(
            ['1. Set Lofty Goals.', '2. Visualize Success.', '3. Learn from Others.'],
            $this->object->split($numeric, Sentence::SPLIT_TRIM)
        );
        $this->assertSame(
            ['I. Set Lofty Goals.', 'II. Visualize Success.', 'XI. Learn from Others.'],
            $this->object->split($roman, Sentence::SPLIT_TRIM)
        );
        $this->assertSame(
            ['a. Set Lofty Goals.', 'b. Visualize Success.', 'c. Learn from Others.'],
            $this->object->split($letters, Sentence::SPLIT_TRIM)
        );
    }

""" + marker
if test_text.count(marker) != 1:
    raise SystemExit('Expected ordered-list test marker exactly once')
tests.write_text(test_text.replace(marker, addition, 1), encoding='utf-8')

readme = Path('README.md')
readme_text = readme.read_text(encoding='utf-8')
old = "-\tSentences must be at least two words long, unless a linebreak or end-of-text.\n"
new = """-\tSingle-word sentences ending in a period are recognized when the token is not
\ta known abbreviation, an initial, or an ordered-list marker.
"""
if readme_text.count(old) != 1:
    raise SystemExit('Expected README sentence rule exactly once')
readme.write_text(readme_text.replace(old, new, 1), encoding='utf-8')

Path('.github/workflows/apply-single-word-period-fix.yml').unlink()
Path('tools/apply-single-word-period-fix.py').unlink()
