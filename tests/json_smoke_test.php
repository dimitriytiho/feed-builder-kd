<?php

/**
 * Дымовой тест JSON-фида (NDJSON) без зависимостей (composer/Laravel не требуются).
 * Запуск: php tests/json_smoke_test.php
 *
 * Проверяет:
 *  - NDJSON: по одному валидному JSON-объекту на строку;
 *  - заголовок-обёртка первой строкой (name обязателен, опциональные поля только если переданы);
 *  - дата проставляется автоматически;
 *  - кириллица не экранируется (нет \uXXXX), слеши не экранируются;
 *  - потоковый ввод через генератор;
 *  - сохранение в файл (ветка без Laravel Storage).
 */

require __DIR__ . '/../src/Feeds/JsonBuilder.php';
require __DIR__ . '/../src/Feeds/GenerateJsonFeed.php';

use Dimitriytiho\FeedBuilderKd\Feeds\GenerateJsonFeed;

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

echo "== Данные ==\n";

$categories = [
    ['id' => 1, 'name' => 'Категория «А» & Б'],
    ['id' => 2, 'name' => 'cat2', 'parent_id' => 1],
];

// Генератор — проверяем потоковый ввод (не массив целиком в памяти)
$offersGen = (function () {
    yield [
        'id' => 100,
        'name' => 'Кабель «Люкс» & провод <про>',
        'url' => 'https://site.ru/catalog/p?a=1&b=2',
        'price' => 1234.5,
        'available' => true,
        'params' => ['Вес' => 1.5, 'Цвет' => 'белый'],
    ];
    yield [
        'id' => 200,
        'name' => 'Тест "кавычки"',
        'price' => 0,
        'available' => false,
    ];
})();

$tmp = tempnam(sys_get_temp_dir(), 'ndjson_');

$returnedPath = GenerateJsonFeed::run(
    name: 'Мой фид',
    offers: $offersGen,
    categories: $categories,
    putPath: $tmp,
    company: 'ООО "Рога & Копыта"',
    url: 'https://site.ru',
    currencyId: 'RUR',
);

echo "\n== Проверки ==\n";

check('run() вернул путь сохранения', $returnedPath === $tmp);

$content = file_get_contents($tmp);
$lines = $content === '' ? [] : explode("\n", rtrim($content, "\n"));

check('строк = заголовок + 2 товара = 3', count($lines) === 3);

// Каждая строка — валидный JSON-объект
$allValid = true;
foreach ($lines as $line) {
    json_decode($line, false, 512, JSON_THROW_ON_ERROR);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $allValid = false;
    }
}
check('каждая строка — валидный JSON', $allValid);

// Заголовок
$header = json_decode($lines[0], true);
check('заголовок: name обязателен', ($header['name'] ?? null) === 'Мой фид');
check('заголовок: company передан', ($header['company'] ?? null) === 'ООО "Рога & Копыта"');
check('заголовок: url передан', ($header['url'] ?? null) === 'https://site.ru');
check('заголовок: currencyId передан', ($header['currencyId'] ?? null) === 'RUR');
check('заголовок: дата проставлена', !empty($header['date']));
check('заголовок: категории на месте', count($header['categories'] ?? []) === 2);

// Товары
$o1 = json_decode($lines[1], true);
$o2 = json_decode($lines[2], true);
check('товар 1: id', ($o1['id'] ?? null) === 100);
check('товар 1: типы сохранены (price float, available bool)', $o1['price'] === 1234.5 && $o1['available'] === true);
check('товар 1: вложенные params', ($o1['params']['Цвет'] ?? null) === 'белый');
check('товар 2: available=false', ($o2['available'] ?? null) === false);

// Экранирование
check('кириллица НЕ экранирована (нет \\u)', !str_contains($content, '\\u04'));
check('слеши в URL НЕ экранированы', str_contains($content, 'https://site.ru/catalog/p?a=1&b=2'));
check('спецсимволы значения не ломают JSON (< & " внутри строки)', str_contains($content, 'Кабель «Люкс» & провод <про>'));

// Без заголовка
$tmp2 = tempnam(sys_get_temp_dir(), 'ndjson2_');
GenerateJsonFeed::run(name: 'X', offers: [['id' => 1]], putPath: $tmp2, withHeader: false);
$lines2 = explode("\n", rtrim(file_get_contents($tmp2), "\n"));
check('withHeader=false: только товары', count($lines2) === 1 && (json_decode($lines2[0], true)['id'] ?? null) === 1);

// Опциональные поля заголовка опускаются, если не переданы
$tmp3 = tempnam(sys_get_temp_dir(), 'ndjson3_');
GenerateJsonFeed::run(name: 'OnlyName', offers: [], putPath: $tmp3);
$h3 = json_decode(rtrim(file_get_contents($tmp3), "\n"), true);
check('опциональные поля опущены (нет company/url/categories)', !isset($h3['company']) && !isset($h3['url']) && !isset($h3['categories']));
check('name и date присутствуют всегда', ($h3['name'] ?? null) === 'OnlyName' && !empty($h3['date']));

@unlink($tmp);
@unlink($tmp2);
@unlink($tmp3);

echo "\n----------------------------------------\n";
echo "PASSED: {$passed}, FAILED: {$failed}\n";
echo "----------------------------------------\n";

if ($failed === 0) {
    echo "\n--- Пример NDJSON ---\n{$content}\n";
}

exit($failed === 0 ? 0 : 1);
