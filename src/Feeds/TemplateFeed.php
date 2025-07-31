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
     $feed = $feedTemplate->content('main');

     Примеры данных
     $categories = [['id' => 1,'name' => 'cat1'],['id' => 2,'name' => 'cat2', 'parent_id'=>1]];
     $offers = [
        [
            // Offer
            'id' => 77,
            'attrs' => ['available' => true],
            'attrsOnlyKey' => ['name'],
            // Tags
            'tags' => [
                [
                    'tag' => 'barcode',
                    'value' => '123',
                    'attrs' => ['one' => 1],
                    'attrsOnlyKey' => ['data_name'],
                    'end' => true,
                    'endSlash' => false,
                ],
                [
                    'tag' => 'price',
                    'value' => '777',
                ],
            ],
            // Params
            'params' => [
                [
                    'name' => 'weight',
                    'value' => '11',
                    'unit' => 'kg',
                    'attrs' => ['two' => 2],
                    'attrsOnlyKey' => ['data_name'],
                ],
                [
                    'name' => 'height',
                    'value' => '11',
                    'unit' => 'kg',
                ],
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
