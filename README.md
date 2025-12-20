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
$feedTemplate = new FeedTemplate($name, $company, $url, $categories, $offers);
$feed = $feedTemplate->content('RUB'); // Здесь используется шаблон из класса FeedTemplate, если данный шаблон не подходит, то создайте свой класс и по данному примеру, обязательно реализуйте метод content, например класс: \App\Feed\TemplateFeed.

```

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
    $disk // для Laravel можно передать имя диска, необязательный параметр
    $currencyId, // по-умолчанию RUB, можно передать любую другую валюту
    $customClassTemplateFeed // по-умолчанию встроенный шаблон, можно передать название своего класса шаблона фида, например: \App\Feed\TemplateFeed::class
);

### Если вам не подходит данное решение через GenerateFeed::run вы можете по данному примеру создать свой класс и делать с контентом фида всё что угодно.

```
