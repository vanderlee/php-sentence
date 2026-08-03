from pathlib import Path

path = Path('tests/SentenceTest.php')
source = path.read_text(encoding='utf-8')
old = '    protected function setUp()\n'
new = "    /**\n     * @before\n     */\n    protected function setUpFixture()\n"
if source.count(old) != 1:
    raise SystemExit('Expected SentenceTest::setUp exactly once')
path.write_text(source.replace(old, new), encoding='utf-8')

final_workflow = '''name: Tests

on:
  pull_request:
  push:
    branches:
      - master
  workflow_dispatch:

permissions:
  contents: read

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        include:
          - php: '5.4'
            composer: 'v1'
          - php: '5.6'
            composer: '2.2'
          - php: '7.4'
            composer: 'v2'
          - php: '8.0'
            composer: 'v2'
          - php: '8.4'
            composer: 'v2'
          - php: '8.5'
            composer: 'v2'

    name: PHP ${{ matrix.php }}

    steps:
      - name: Check out repository
        uses: actions/checkout@v4

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mbstring, ctype
          tools: composer:${{ matrix.composer }}
          coverage: none

      - name: Validate Composer metadata
        run: composer validate --strict

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run tests
        run: composer test
'''
Path('.github/workflows/tests.yml').write_text(final_workflow, encoding='utf-8')
Path('tools/apply-phpunit-fixture-compat.py').unlink()
