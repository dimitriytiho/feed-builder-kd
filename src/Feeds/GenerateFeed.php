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
     * @param string $methodName
     * @param string|null $putPath - if no save, then null
     * @param string|null $disk - for Laravel
     * @return void
     */
    public static function run(
        string $name,
        string $company,
        string $url,
        array $categories,
        array $offers,
        string $methodName,
        string|null $putPath = null,
        string|null $disk = null
    ): void {
        $feedTemplate = new TemplateFeed($name, $company, $url, $categories, $offers);
        $feed = $feedTemplate->content($methodName);
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