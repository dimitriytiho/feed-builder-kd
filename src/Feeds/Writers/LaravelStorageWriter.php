<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds\Writers;

/**
 * Сохранение фида через Laravel Storage.
 * Если $disk === null, используется диск по-умолчанию (поведение Storage::disk(null)).
 */
class LaravelStorageWriter implements FeedWriterInterface
{
    /**
     * @param string|null $disk
     */
    public function __construct(protected string|null $disk = null)
    {
    }

    /**
     * @param string $path
     * @param string $content
     * @return void
     */
    public function write(string $path, string $content): void
    {
        \Illuminate\Support\Facades\Storage::disk($this->disk)->put($path, $content);
    }
}
