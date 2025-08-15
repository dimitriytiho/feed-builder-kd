<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds;

class TemplateFeed
{
    protected BuilderFeed $builder;

    /**
     * @param string $name
     * @param string $company
     * @param string $url
     * @param array $categories
     * @param array $offers

     Запуск
    $feedTemplate = new FeedTemplate($name, $company, $url, $categories, $offers);
    $feed = $feedTemplate->content('main'); // Здесь используется шаблон main из соответствующего метода в классе FeedTemplate, если он не подходит, то создайте свой метод с уникальным шаблоном.

     Примеры данных
     // Example data, is managed from array
    $name = 'Feed Php';
    $company = 'Php';
    $url = 'https://php.ru';
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

     */
    public function __construct(
        protected string $name,
        protected string $company,
        protected string $url,
        protected array $categories,
        protected array $offers
    ) {
        $this->builder = new BuilderFeed();
    }

    /**
     * @param string $methodName
     * @return string
     */
    public function content(string $methodName): string
    {
        if (method_exists($this, $methodName)) {
            return $this->{$methodName}();
        }
        return '';
    }

    /**
     * Шаблон для генерации фида.
     *
     * @return string
     */
    protected function main(): string
    {
        $res = $this->builder->titles($this->name, $this->company, $this->url);
        $res .= $this->builder->currencies();
        $res .= $this->builder->categories($this->categories);
        $res .= $this->builder->offers($this->offers);
        $res = $this->builder->wrap('shop', $res);
        $res = $this->builder->wrap('yml_catalog', $res, ['date' => date(DATE_ATOM)]);
        return $this->builder->head() . $res;
    }
}
