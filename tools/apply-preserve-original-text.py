from pathlib import Path

source = Path('src/Sentence.php')
text = source.read_text(encoding='utf-8')


def replace_once(old, new):
    global text
    if text.count(old) != 1:
        raise SystemExit('Expected source anchor exactly once: ' + old[:80])
    text = text.replace(old, new, 1)


replace_once(
    "    const SPLIT_TRIM = 0x1;\n",
    """    const SPLIT_TRIM = 0x1;

    /**
     * Preserve the original Unicode characters in returned sentences.
     */
    const SPLIT_PRESERVE = 0x2;
"""
)

replace_once(
    """    /**
     * Return the sentences detected in the provided text.
""",
    """    /**
     * Project normalized sentence boundaries back onto the original text.
     *
     * Quote normalization is character-for-character, so offsets remain stable.
     * If another normalization changes the character count, retain the normalized
     * output rather than risk returning incorrect slices.
     *
     * @param string[] $sentences
     * @param string   $normalized
     * @param string   $original
     *
     * @return string[]
     */
    private static function preserveOriginalText($sentences, $normalized, $original)
    {
        if (mb_strlen($normalized) !== mb_strlen($original)) {
            return $sentences;
        }

        $result = [];
        $offset = 0;

        foreach ($sentences as $sentence) {
            $position = mb_strpos($normalized, $sentence, $offset);
            if ($position === false) {
                return $sentences;
            }

            $length = mb_strlen($sentence);
            $result[] = mb_substr($original, $position, $length);
            $offset = $position + $length;
        }

        return $result;
    }

    /**
     * Return the sentences detected in the provided text.
"""
)

replace_once(
    """        // clean funny quotes
        $text = Multibyte::cleanUnicode($text);
""",
    """        $originalText = $text;

        // clean funny quotes for boundary detection
        $text = Multibyte::cleanUnicode($text);
"""
)

replace_once(
    """        // Post process
        if ($flags & self::SPLIT_TRIM) {
""",
    """        // Post process
        if ($flags & self::SPLIT_PRESERVE) {
            $sentences = self::preserveOriginalText($sentences, $text, $originalText);
        }

        if ($flags & self::SPLIT_TRIM) {
"""
)
source.write_text(text, encoding='utf-8')

tests = Path('tests/SentenceTest.php')
test_text = tests.read_text(encoding='utf-8')
marker = """    /**
     * @covers ::split
     */
    public function testSplitWord()
"""
addition = """    /**
     * @covers ::split
     */
    public function testPreserveOriginalUnicode()
    {
        $text = 'Super Tortas is outstanding! I haven’t had as many tortas as I would like.';

        $this->assertSame(
            ['Super Tortas is outstanding!', ' I haven’t had as many tortas as I would like.'],
            $this->object->split($text, Sentence::SPLIT_PRESERVE)
        );
        $this->assertSame(
            ['Super Tortas is outstanding!', 'I haven’t had as many tortas as I would like.'],
            $this->object->split($text, Sentence::SPLIT_PRESERVE | Sentence::SPLIT_TRIM)
        );
    }

""" + marker
if test_text.count(marker) != 1:
    raise SystemExit('Expected test insertion marker exactly once')
tests.write_text(test_text.replace(marker, addition, 1), encoding='utf-8')

readme = Path('README.md')
readme_text = readme.read_text(encoding='utf-8')
old = """-\t**`Sentence::SPLIT_TRIM`**: Trim whitespace off the left and right sides of
\teach returned sentence.
"""
new = old + """-\t**`Sentence::SPLIT_PRESERVE`**: Preserve original Unicode quote and
\tapostrophe characters in returned sentences instead of returning their normalized
\tASCII equivalents. It can be combined with `SPLIT_TRIM` using `|`.
"""
if readme_text.count(old) != 1:
    raise SystemExit('Expected README flag description exactly once')
readme.write_text(readme_text.replace(old, new, 1), encoding='utf-8')

Path('.github/workflows/apply-preserve-original-text.yml').unlink()
Path('tools/apply-preserve-original-text.py').unlink()
