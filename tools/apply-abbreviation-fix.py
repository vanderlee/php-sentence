from pathlib import Path

source_path = Path('src/Sentence.php')
source = source_path.read_text(encoding='utf-8')
anchor = "$last_is_capital = preg_match('#^\\p{Lu}#u', $last_word);"
insertion = anchor + "\n        $last_is_initial = preg_match('#^\\p{L}\\.$#u', $last_word);"
if source.count(anchor) != 1:
    raise SystemExit('Expected capitalization check exactly once')
source = source.replace(anchor, insertion)
old_return = 'return $last_is_capital > 0'
new_return = 'return ($last_is_capital > 0 || $last_is_initial > 0)'
if source.count(old_return) != 1:
    raise SystemExit('Expected abbreviation return exactly once')
source_path.write_text(source.replace(old_return, new_return), encoding='utf-8')

test_path = Path('tests/SentenceTest.php')
tests = test_path.read_text(encoding='utf-8')
test = r'''

    /**
     * @covers ::split
     * @covers ::isAbbreviation
     */
    public function testSplitLowercaseInitialAbbreviations()
    {
        $this->assertSame(
            ['I get up at 7 a.m. every day.'],
            $this->object->split('I get up at 7 a.m. every day.')
        );
        $this->assertSame(
            ["Let's meet at 10:00 a.m..", ' How about Greg?'],
            $this->object->split("Let's meet at 10:00 a.m.. How about Greg?")
        );
        $this->assertSame(
            ["Let's meet at 10:00 A.M..", ' How about Greg?'],
            $this->object->split("Let's meet at 10:00 A.M.. How about Greg?")
        );
    }
'''
closing = tests.rfind('\n}')
if closing == -1:
    raise SystemExit('Could not locate SentenceTest class closing brace')
test_path.write_text(tests[:closing] + test + tests[closing:], encoding='utf-8')

Path('.github/workflows/apply-abbreviation-fix.yml').unlink()
Path('tools/apply-abbreviation-fix.py').unlink()
