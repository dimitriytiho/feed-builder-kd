<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds;

use Dimitriytiho\FeedBuilderKd\Feeds\Writers\FeedWriterInterface;
use Dimitriytiho\FeedBuilderKd\Feeds\Writers\FileWriter;
use Dimitriytiho\FeedBuilderKd\Feeds\Writers\LaravelStorageWriter;

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
     * @param string $currencyId - по-умолчанию RUR, можно передать любую другую валюту
     * @param string|null $customClassTemplateFeed - по-умолчанию встроенный шаблон, можно передать название своего класса шаблона фида, например: \App\Feed\TemplateFeed::class.
     * @param FeedWriterInterface|null $writer - необязательно: свой обработчик сохранения. По-умолчанию определяется автоматически (Laravel Storage или file_put_contents).
     * @return string - сгенерированный фид.
     */
    public static function run(
        string $name,
        string $company,
        string $url,
        array $categories,
        array $offers,
        string|null $putPath,
        string|null $disk = null,
        string $currencyId = 'RUR',
        string|null $customClassTemplateFeed = null,
        FeedWriterInterface|null $writer = null
    ): string {
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
            $writer = $writer ?: self::resolveWriter($disk);
            $writer->write($putPath, $feed);
        }

        return $feed;
    }

    /**
     * Определяем обработчик сохранения: Laravel Storage, если доступен, иначе файловая система.
     *
     * @param string|null $disk
     * @return FeedWriterInterface
     */
    protected static function resolveWriter(string|null $disk = null): FeedWriterInterface
    {
        if (class_exists('\Illuminate\Support\Facades\Storage')) {
            return new LaravelStorageWriter($disk);
        }
        return new FileWriter();
    }
}
