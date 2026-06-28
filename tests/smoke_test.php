<?php

/**
 * Дымовой тест без зависимостей (composer не требуется).
 * Запуск: php tests/smoke_test.php
 *
 * Проверяет:
 *  - well-formed XML (через DOMDocument);
 *  - корректное экранирование атрибутов и значений;
 *  - что params выводятся даже у оффера без tags (исправленный баг);
 *  - безопасность CDATA при наличии ]]> в значении;
 *  - numberFormat без потери точности.
 */

require __DIR__ . '/../src/Feeds/BuilderFeed.php';
require __DIR__ . '/../src/Feeds/TemplateFeed.php';
require __DIR__ . '/../src/Feeds/Writers/FeedWriterInterface.php';
require __DIR__ . '/../src/Feeds/Writers/FileWriter.php';
require __DIR__ . '/../src/Feeds/GenerateFeed.php';

use Dimitriytiho\FeedBuilderKd\Feeds\BuilderFeed;
use Dimitriytiho\FeedBuilderKd\Feeds\GenerateFeed;
use Dimitriytiho\FeedBuilderKd\Feeds\Writers\FeedWriterInterface;

$failed = 0;
$passed = 0;

function check(string $name, bool $cond): void
{
    global $failed, $passed;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$name}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$name}\n";
    }
}

function assertWellFormed(string $name, string $xml): void
{
    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $ok = $doc->loadXML($xml, LIBXML_NONET);
    $errors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$ok) {
        $msg = $errors ? trim($errors[0]->message) : 'unknown';
        echo "  [FAIL] {$name} — XML не well-formed: {$msg}\n";
        $GLOBALS['failed']++;
    } else {
        echo "  [PASS] {$name} — XML well-formed\n";
        $GLOBALS['passed']++;
    }
}

echo "== Тестовые данные ==\n";
// name/company/url и названия категорий теперь экранируются через escape().
// Передаём НЕэкранированные данные со спецсимволами и проверяем валидность.

$name = 'Feed & Co <Test>';
$company = 'ООО "Рога & Копыта"';
$url = 'https://example.com?a=1&b=2';

$categories = [
    ['id' => 1, 'name' => 'Кабели & провода <тест>'],
    ['id' => 2, 'name' => 'cat2', 'parent_id' => 1],
];

$offers = [
    // Оффер БЕЗ tags, но С params — раньше params терялись
    [
        'id' => 100,
        'attrs' => ['available' => true, 'group' => 'A&B "x"'],
        'params' => [
            ['name' => 'weight', 'value' => '11.500', 'unit' => 'kg', 'numberFormat' => true],
            ['name' => 'note', 'value' => 'a < b & c', 'checkSpecialCharset' => true],
        ],
    ],
    // Обычный оффер с tags и params
    [
        'id' => 200,
        'attrs' => ['available' => true],
        'tags' => [
            ['tag' => 'name', 'value' => 'Кабель <супер> & "лучший"', 'checkSpecialCharset' => true],
            ['tag' => 'price', 'value' => '777.00', 'numberFormat' => true],
            ['tag' => 'desc', 'value' => 'data ]]> end', 'cdata' => true],
            ['tag' => 'empty', 'value' => '', 'skipIfEmpty' => true],
        ],
        'params' => [
            ['name' => 'height', 'value' => '11'],
        ],
    ],
];

$feed = GenerateFeed::run($name, $company, $url, $categories, $offers, null);

echo "\n== Проверки ==\n";

assertWellFormed('Весь фид', $feed);

check('params оффера без tags присутствуют (weight)', str_contains($feed, '<param name="weight"'));
check('params оффера без tags присутствуют (note)', str_contains($feed, '<param name="note"'));
check('height у второго оффера присутствует', str_contains($feed, '<param name="height"'));

check('атрибут с & и кавычками экранирован', str_contains($feed, 'group="A&amp;B &quot;x&quot;"'));
check('skipIfEmpty убрал пустой тег', !str_contains($feed, '<empty'));
check('numberFormat: 11.500 -> 11.5', str_contains($feed, '11.5') && !str_contains($feed, '11.500'));
check('numberFormat: 777.00 -> 777', str_contains($feed, '>777<') || str_contains($feed, '777</price>'));
check('CDATA: нет разрыва ]]> внутри значения', !preg_match('/\]\]>\s*end/', $feed));
check('CDATA содержит защитную вставку', str_contains($feed, ']]]]><![CDATA[>'));

check('name экранирован', str_contains($feed, '<name>Feed &amp; Co &lt;Test&gt;</name>'));
check('company экранирован', str_contains($feed, 'Рога &amp; Копыта'));
check('url экранирован', str_contains($feed, 'a=1&amp;b=2'));
check('category name экранирован', str_contains($feed, 'Кабели &amp; провода &lt;тест&gt;'));

echo "\n== Кастомный writer (DI) ==\n";

$customWriter = new class implements FeedWriterInterface {
    public string $path = '';
    public string $content = '';
    public function write(string $path, string $content): void
    {
        $this->path = $path;
        $this->content = $content;
    }
};

$returned = GenerateFeed::run($name, $company, $url, $categories, $offers, '/tmp/feed.xml', null, 'RUR', null, $customWriter);
check('кастомный writer получил путь', $customWriter->path === '/tmp/feed.xml');
check('кастомный writer получил контент', $customWriter->content !== '');
check('run() вернул тот же контент, что записал writer', $returned === $customWriter->content);

echo "\n== Прямое использование BuilderFeed ==\n";
$b = new BuilderFeed();
assertWellFormed('currencies()', '<root>' . $b->currencies() . '</root>');
assertWellFormed('categories()', '<root>' . $b->categories($categories) . '</root>');

echo "\n----------------------------------------\n";
echo "PASSED: {$passed}, FAILED: {$failed}\n";
echo "----------------------------------------\n";

if ($failed === 0) {
    echo "\n--- Пример вывода фида ---\n{$feed}\n";
}

exit($failed === 0 ? 0 : 1);
