from pathlib import Path

source = Path('src/Sentence.php')
text = source.read_text(encoding='utf-8')
old = """    private static function trimSentences($sentences)
    {
        return array_map(function ($sentence) {
            return Multibyte::trim($sentence);
        }, $sentences);
    }
"""
new = """    private static function trimSentences($sentences)
    {
        $sentences = array_map(function ($sentence) {
            return Multibyte::trim($sentence);
        }, $sentences);

        return array_values(array_filter($sentences, function ($sentence) {
            return $sentence !== '';
        }));
    }
"""
if text.count(old) != 1:
    raise SystemExit('Expected trimSentences method exactly once')
source.write_text(text.replace(old, new, 1), encoding='utf-8')
Path('tools/filter-empty-trimmed-sentences.py').unlink()
