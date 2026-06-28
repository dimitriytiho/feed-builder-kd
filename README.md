### Создать xml фид легко, не надо разбираться и настаивать неудобные теги, все настройки происходят в php массиве, настройте его по примеру ниже и получите готовый фид.
### Get feed content:
```php
<?php

// Example data, is managed from array
$name = 'Feed Php'; // Название фида
$company = 'Php'; // Название вашей компании
$url = 'https://php.ru'; // Url вашего сайта
// Пример категории для фида
$categories = [
    [
        'id' => 1,
        'name' => 'cat1',
    ],
    [
        'id' => 2,
        'name' => 'cat2',
        'parent_id' => 1
     ],
];
// Пример офферов для фида
$offers = [
    [
        // Настраиваем тег offer
        'id' => 77, // id в offer
        'attrs' => ['available' => true], // атрибуты в offer массивом, ключ значение
        'attrsOnlyKey' => ['name'], // атрибуты только ключи массивом
        // Настраиваем теги в offer, обязательный параметр только tag
        'tags' => [
            [
                    'tag' => 'barcode', // название тега
                    'value' => '123', // значение тега
                    'attrs' => ['one' => 1], // атрибуты в теге, ключ значение
                    'attrsOnlyKey' => ['data_name'], // атрибуты в теге, только ключи
                    'end' => true, // удалить закрывающий тег
                    'endSlash' => false, // добавить слеш в конце тега
                    'checkSpecialCharset' => true, // проверка на недопустимые символы и если есть выводим в значение в конструкции CDATA, т.е. экранируем
                    'cdata' => true, // всегда экранировать значение
                    'stripTags' => true, // удалить из значения html теги
                    'numberFormat' => true, // преобразуем число в строку с форматом без лишних нулей в конце
                    'implodeArr' => true, // если значение состоит из массива значений, то разбиваем вертикальной чертой каждое значение
                    'implodeJson' => true, // если значение json массив значений, то разбиваем вертикальной чертой каждое значение
                    'skipIfEmpty' => true, // если значение пустое не отображаем тег
                ],
            [
                'tag' => 'price',
                'value' => '777',
            ],
        ],
        // Настраиваем теги params, обязательный параметр только name
        'params' => [
            [
                'name' => 'weight',
                'value' => '11',
                'unit' => 'kg', // единицы измерения
                'attrs' => ['two' => 2],
                'attrsOnlyKey' => ['data_name'],
                'skipIfEmpty' => true, // если значение пустое не отображаем параметр
            ],
            [
                'name' => 'height',
                'value' => '11',
            ],
        ],
        // Если нужно вывести особый тег, добавьте сюда
        'customs' => [
            'you_tag_1',
            'you_tag_2',
        ],
    ],
];

// Get content feed
$feedTemplate = new TemplateFeed($name, $company, $url, $categories, $offers);
$feed = $feedTemplate->content('RUR'); // Здесь используется шаблон из класса TemplateFeed, если данный шаблон не подходит, то создайте свой класс по данному примеру, обязательно реализуйте метод content, например класс: \App\Feed\TemplateFeed.

```

### Поля name, company, url и названия категорий экранируются автоматически (метод BuilderFeed::escape()). Передавайте в них сырые данные — не экранируйте заранее, иначе получите двойное экранирование. Метод escape() публичный, его можно использовать и для своих значений.

### Если вы используете Laravel, то сохранение будет через Storage helper, иначе фид сохранится через file_put_contents
### Generate feed and save:
```php
<?php

GenerateFeed::run(
    $name,
    $company,
    $url,
    $categories,
    $offers,
    $putPath, // путь для сохранения фида
    $disk, // для Laravel можно передать имя диска, необязательный параметр
    $currencyId, // по-умолчанию RUR, можно передать любую другую валюту
    $customClassTemplateFeed // по-умолчанию встроенный шаблон, можно передать название своего класса шаблона фида, например: \App\Feed\TemplateFeed::class
);
```

### Сохранение фида вынесено в отдельные writer-классы (Writers/FileWriter, Writers/LaravelStorageWriter). По-умолчанию обработчик определяется автоматически: Laravel Storage, если доступен, иначе file_put_contents. При необходимости можно передать свой обработчик последним аргументом (реализует Writers\FeedWriterInterface):
```php
GenerateFeed::run($name, $company, $url, $categories, $offers, $putPath, null, 'RUR', null, new MyCustomWriter());
```
### Метод GenerateFeed::run() теперь возвращает сгенерированный контент фида (строку), что не влияет на существующие вызовы.

### Для больших фидов и S3 используйте потоковый writer (запись во временный файл + Storage::writeStream, multipart upload, меньше памяти на заливке):
```php
use Dimitriytiho\FeedBuilderKd\Feeds\Writers\LaravelStorageStreamWriter;

GenerateFeed::run($name, $company, $url, $categories, $offers, $putPath, $disk, 'RUR', null, new LaravelStorageStreamWriter($disk));
```

### Если вам не подходит данное решение через GenerateFeed::run вы можете по данному примеру создать свой класс и делать с контентом фида всё что угодно.
