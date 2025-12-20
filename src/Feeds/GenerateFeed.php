<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds;

class GenerateFeed
{
    /**
     * Generate feed and save.
     * If Laravel, then save Storage helper.
     *
     * @param string $name
     * @param string $company
     * @param string $url
     * @param array $categories
     * @param array $offers
     * @param string|null $putPath - путь для сохранения фида.
     * @param string|null $disk - для Laravel можно передать имя диска, необязательный параметр.
     * @param string $currencyId - по-умолчанию RUB, можно передать любую другую валюту
     * @param string|null $customClassTemplateFeed - по-умолчанию встроенный шаблон, можно передать название своего класса шаблона фида, например: \App\Feed\TemplateFeed::class.
     * @return void
     */
    public static function run(
        string $name,
        string $company,
        string $url,
        array $categories,
        array $offers,
        string|null $putPath,
        string|null $disk = null,
        string $currencyId = 'RUB',
        string|null $customClassTemplateFeed = null
    ): void {
        // Custom class template feed
        if ($customClassTemplateFeed && class_exists($customClassTemplateFeed) && method_exists($customClassTemplateFeed, 'content')) {
            $feedTemplate = new $customClassTemplateFeed($name, $company, $url, $categories, $offers);
            $feed = $feedTemplate->content($currencyId);
        } else {
            // Default class template feed
            $feedTemplate = new TemplateFeed($name, $company, $url, $categories, $offers);
            $feed = $feedTemplate->content($currencyId);
        }

        // Save feed
        if ($putPath) {
            if (class_exists('\Illuminate\Support\Facades\Storage')) {
                $aws = \Illuminate\Support\Facades\Storage::disk($disk);
                $aws->put($putPath, $feed);
            } else {
                file_put_contents($putPath, $feed);
            }
        }
    }
}