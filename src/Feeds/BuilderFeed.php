<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds;

/**
 * Конструктор фидов.
*/
class BuilderFeed
{
    /**
     * @param string $tag
     * @param string|int|float|bool|null $content
     * @param array $attrs
     * @param array $attrsOnlyKey
     * @return string
     */
    public function wrap(string $tag, string|int|float|bool|null $content, array $attrs = [], array $attrsOnlyKey = []): string
    {
        $attr = $this->attrs($attrs, $attrsOnlyKey);
        return "<{$tag}{$attr}>\n{$content}</{$tag}>\n";
    }

    /**
     * @param string $tag - название тега.
     * @param string|int|float|bool|null $value - значение тега.
     * @param array $attrs - атрибуты в теге, ключ значение.
     * @param array $attrsOnlyKey - атрибуты в теге, только ключи.
     * @param bool $end - удалить закрывающий тег.
     * @param bool $endSlash - добавить слеш в конце тега.
     * @param bool $checkSpecialCharset - проверка на недопустимые символы и если есть выводим в значение в конструкции CDATA, т.е. экранируем.
     * @return string
     */
    public function tag(
        string $tag,
        string|int|float|bool|null $value = null,
        array $attrs = [],
        array $attrsOnlyKey = [],
        bool $end = true,
        bool $endSlash = false,
        bool $checkSpecialCharset = false,
    ): string {
        $endName = $end ? '>' : null;
        $endTag = $end ? "</{$tag}" : null;
        $endSlash = $endSlash ? '/' : null;
        $attr = $this->attrs($attrs, $attrsOnlyKey);
        $value = $this->boolStr($value);
        if ($checkSpecialCharset && $this->checkSpecialCharset($value)) {
            $value = $this->cdata($value);
        }
        return "<{$tag}{$attr}{$endName}{$value}{$endTag}{$endSlash}>\n";
    }

    /**
     * @param string|int|float|bool|null $content
     * @return string
     */
    public function cdata(string|int|float|bool|null $content = null): string
    {
        return "\n<![CDATA[{$content}]]>\n";
    }

    /**
     * @param string|int|float|bool|null $value
     * @return string
     */
    private function boolStr(string|int|float|bool|null $value): string
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        return (string) $value;
    }

    /**
     * @param string $name
     * @param string|int|float|bool|null $value
     * @param string|null $unit
     * @param array $attrs
     * @param array $attrsOnlyKey
     * @return string
     */
    public function param(string $name, string|int|float|bool|null $value = null, string|null $unit = null, array $attrs = [], array $attrsOnlyKey = []): string
    {
        $attr['name'] = $name;
        if ($unit) {
            $attr['unit'] = $unit;
        }
        $attrs = array_merge($attr, $attrs);
        return $this->tag('param', $value, $attrs, $attrsOnlyKey);
    }

    /**
     * @param array $attrs
     * @param array $attrsOnlyKey
     * @return string
     */
    public function attrs(array $attrs = [], array $attrsOnlyKey = []): string
    {
        $res = '';
        if ($attrs) {
            foreach ($attrs as $attrKey => $attrVal) {
                if ($attrKey) {
                    $attrVal = $this->boolStr($attrVal);
                    $res .= "{$attrKey}=\"{$attrVal}\" ";
                }
            }
        }
        if ($attrsOnlyKey) {
            foreach ($attrsOnlyKey as $attrOnlyVal) {
                if ($attrOnlyVal) {
                    $res .= $attrOnlyVal . ' ';
                }
            }
        }
        // Добавляем пробел в начале, в конце удаляем
        if ($res) {
            $res = ' ' .  rtrim($res);
        }
        return $res;
    }

    /**
     * @param string $tag
     * @return string
     */
    public function customTag(string $tag): string
    {
        return $tag . "\n";
    }

    /**
     * Проверка на недопустимые символы xml.
     *
     * @param string|float|int|bool|null $string
     * @return bool
     */
    protected function checkSpecialCharset(string|float|int|bool|null $string): bool
    {
        $pattern = '/&(?!amp;|lt;|gt;|quot;|apos;)|[<>\'"]/';
        return (bool) preg_match($pattern, $string);
    }

    /**
     * @param array $attrs
     * @param array $attrsOnlyKey
     * @return string
     */
    public function head(array $attrs = [], array $attrsOnlyKey = []): string
    {
        $res = $this->customTag('<?xml version="1.0" encoding="UTF-8"?>');
        $res .= '<!DOCTYPE';
        $attr = $this->attrs($attrs, $attrsOnlyKey);
        if (!$attr) {
            $attr = " yml_catalog SYSTEM \"shops.dtd\"";
        }
        $res .= "{$attr}>\n";
        return $res;
    }

    /**
     * @param string $name
     * @param string $company
     * @param string $url
     * @return string
     */
    public function titles(string $name, string $company, string $url): string
    {
        $res = $this->tag('name', $name);
        $res .= $this->tag('company', $company);
        $res .= $this->tag('url', $url);
        return $res;
    }

    /**
     * Key - code currency, value - rate, default RUB.
     *
     * @param array $currencies['id','rate]
     * @param string $currencyIdDefault default RUB
     * @return string
     */
    public function currencies(array $currencies = [], string $currencyIdDefault = 'RUB'): string
    {
        $res = '';
        if (!$currencies) {
            $currencies[] = ['id' => $currencyIdDefault, 'rate' => 1];
        }
        foreach ($currencies as $currency) {
            $res .= $this->tag('currency', '', ['id' => $currency['id'], 'rate' => $currency['rate']], [], false, true);
        }
        return $this->wrap('currencies', $res);
    }

    /**
     * @param array $categories['id', 'parent_id', 'name']
     * @return string
     */
    public function categories(array $categories): string
    {
        $res = '';
        foreach ($categories as $cat) {
            $attrs = ['id' => $cat['id']];
            if (!empty($cat['parent_id'])) {
                $attrs['parentId'] = $cat['parent_id'];
            }
            $res .= $this->tag('category', $cat['name'], $attrs);
        }
        return $this->wrap('categories', $res);
    }

    /**
     * Offer key ID, may be keys: 'id', attrs, attrsOnlyKey.
     *
     * In offer many arrays options['tag'=>string, 'value'=>string, 'attrs'=>array, 'attrsOnlyKey'=>array, 'end'=>bool, 'endSlash'=>bool], key tag - required
     *
     * @param array $offers
     * @param string $currencyId default RUB
     * @return string
     */
    public function offers(array $offers = [], string $currencyId = 'RUB'): string
    {
        $res = '';
        if ($offers) {
            foreach ($offers as $offer) {
                // Tags
                $tagsAndParams = '';
                if (!empty($offer['tags'])) {
                    foreach ($offer['tags'] as $tag) {
                        if (!empty($tag['tag'])) {
                            // Подготавливаем значение
                            $value = $this->prepareValue($tag);
                            // Экранирование значения
                            $checkSpecialCharset = $tag['checkSpecialCharset'] ?? false;
                            if (!empty($tag['cdata'])) {
                                $value = $this->cdata($value);
                                $checkSpecialCharset = false;
                            }
                            // Tag
                            $tagsAndParams .= $this->tag(
                                $tag['tag'],
                                $value,
                                $tag['attrs'] ?? [],
                                $tag['attrsOnlyKey'] ?? [],
                                $tag['end'] ?? true,
                                $tag['endSlash'] ?? false,
                                $checkSpecialCharset,
                            );
                        }
                    }
                }
                // Currency
                if ($currencyId) {
                    $tagsAndParams .= $this->tag('currencyId', $currencyId);
                }
                // Customs если нужно вывести особые теги
                if (!empty($offer['customs'])) {
                    foreach ($offer['customs'] as $custom) {
                        $tagsAndParams .= $custom;
                    }
                }
                // Params
                if (!empty($offer['params'])) {
                    foreach ($offer['params'] as $param) {
                        if (!empty($tag['tag'])) {
                            // Подготавливаем значение
                            $value = $this->prepareValue($param);
                            $tagsAndParams .= $this->param($param['name'], $value, $param['unit'] ?? null, $param['attrs'] ?? [], $param['attrsOnlyKey'] ?? []);
                        }
                    }
                }
                // Offer
                $offerAttrs = $offer['attrs'] ?? [];
                if (!empty($offer['id'])) {
                    $offerAttrs['id'] = $offer['id'];
                }
                $res .= $this->wrap('offer', $tagsAndParams, $offerAttrs, $offer['attrsOnlyKey'] ?? []);
            }
        }
        return $this->wrap('offers', $res);
    }

    /**
     * Удалить из значения html теги.
     *
     * @param string|int|float|bool|null $value
     * @return string
     */
    public function stripTags(string|int|float|bool|null $value = null): string
    {
        return strip_tags((string) $value);
    }

    /**
     * Преобразуем число в строку с форматом без лишних нулей в конце.
     *
     * @param string|int|float|bool|null $value
     * @return string
     */
    public function numberFormat(string|int|float|bool|null $value): string
    {
        // Удаляем 0 с конца строки с помощью регулярного выражения
        return (string) preg_replace('/\.0+$/', '', (float) $value);
    }

    /**
     * Если значение состоит из массива значений, то разбиваем вертикальной чертой каждое значение.
     *
     * @param array|null $arr
     * @return string
     */
    public function implodeArr(array|null $arr): string
    {
        return implode('|', $arr ?: []);
    }

    /**
     * Если значение json массив значений, то разбиваем вертикальной чертой каждое значение.
     *
     * @param string|null $json
     * @return string
     */
    public function implodeJson(string|null $json): string
    {
        return $this->implodeArr(json_decode($json, true));
    }

    /**
     * Подготавливаем значение.
     *
     * @param array $data
     * @return string
     */
    protected function prepareValue(array $data): string
    {
        $value = $data['value'] ?? null;
        // Удалить из значения html теги
        if (!empty($data['stripTags'])) {
            $value = $this->stripTags($value);
        }
        // Преобразуем число в строку с форматом без лишних нулей в конце
        if (!empty($data['numberFormat'])) {
            $value = $this->numberFormat($value);
        }
        // Если значение состоит из массива значений, то разбиваем вертикальной чертой каждое значение
        if (!empty($data['implodeArr'])) {
            $value = $this->implodeArr($value);
        }
        // Если значение json массив значений, то разбиваем вертикальной чертой каждое значение
        if (!empty($data['implodeJson'])) {
            $value = $this->implodeJson($value);
        }
        return (string) $value;
    }
}
