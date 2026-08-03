from pathlib import Path

source = Path('src/Sentence.php')
text = source.read_text(encoding='utf-8')
replacements = {
    "mb_strlen($normalized) !== mb_strlen($original)": "mb_strlen($normalized, 'UTF-8') !== mb_strlen($original, 'UTF-8')",
    "mb_strpos($normalized, $sentence, $offset)": "mb_strpos($normalized, $sentence, $offset, 'UTF-8')",
    "mb_strlen($sentence);": "mb_strlen($sentence, 'UTF-8');",
    "mb_substr($original, $position, $length);": "mb_substr($original, $position, $length, 'UTF-8');",
}
for old, new in replacements.items():
    if text.count(old) != 1:
        raise SystemExit('Expected offset expression exactly once: ' + old)
    text = text.replace(old, new, 1)
source.write_text(text, encoding='utf-8')
Path('.github/workflows/apply-preserve-utf8-offsets.yml').unlink()
Path('tools/fix-preserve-utf8-offsets.py').unlink()
