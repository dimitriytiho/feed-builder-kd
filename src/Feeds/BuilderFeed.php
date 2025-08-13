<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds;

/**
 * Конструктор фидов.
*/
class BuilderFeed
{
    public function wrap(string $tag, string $content, array $attrs = [], array $attrsOnlyKey = []): string
    {
        $attr = $this->attrs($attrs, $attrsOnlyKey);
        return "<{$tag}{$attr}>\n{$content}</{$tag}>\n";
    }

    /**
     * @param string $tag
     * @param string|int|float|bool|null $value
     * @param array $attrs
     * @param array $attrsOnlyKey
     * @param bool $end
     * @param bool $endSlash
     * @return string
     */
    public function tag(string $tag, string|int|float|bool|null $value = null, array $attrs = [], array $attrsOnlyKey = [], bool $end = true, bool $endSlash = false): string
    {
        $endName = $end ? '>' : null;
        $endTag = $end ? "</{$tag}" : null;
        $endSlash = $endSlash ? '/' : null;
        $attr = $this->attrs($attrs, $attrsOnlyKey);
        $value = $this->boolStr($value);
        return "<{$tag}{$attr}{$endName}{$value}{$endTag}{$endSlash}>\n";
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
     * @return string
     */
    public function currencies(array $currencies = []): string
    {
        $res = '';
        if (!$currencies) {
            $currencies[] = ['id' => 'RUB', 'rate' => 1];
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
     * @return string
     */
    public function offers(array $offers = []): string
    {
        $res = '';
        if ($offers) {
            foreach ($offers as $offer) {
                // Tags
                $tagsAndParams = '';
                if (!empty($offer['tags'])) {
                    foreach ($offer['tags'] as $tag) {
                        if (!empty($tag['tag'])) {
                            $tagsAndParams .= $this->tag($tag['tag'], $tag['value'] ?? null, $tag['attrs'] ?? [], $tag['attrsOnlyKey'] ?? [], $tag['end'] ?? true, $tag['endSlash'] ?? false);
                        }
                    }
                }
                // Params
                if (!empty($offer['params'])) {
                    foreach ($offer['params'] as $param) {
                        if (!empty($tag['tag'])) {
                            $tagsAndParams .= $this->param($param['name'], $param['value'] ?? null, $param['unit'] ?? null, $param['attrs'] ?? [], $param['attrsOnlyKey'] ?? []);
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
}
