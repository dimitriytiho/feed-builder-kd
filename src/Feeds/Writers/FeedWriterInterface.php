<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds\Writers;

interface FeedWriterInterface
{
    /**
     * Сохранить содержимое фида по указанному пути.
     *
     * @param string $path
     * @param string $content
     * @return void
     */
    public function write(string $path, string $content): void;
}
