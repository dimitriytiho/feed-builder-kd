<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds\Writers;

/**
 * Сохранение фида через нативный file_put_contents.
 */
class FileWriter implements FeedWriterInterface
{
    /**
     * @param string $path
     * @param string $content
     * @return void
     */
    public function write(string $path, string $content): void
    {
        file_put_contents($path, $content);
    }
}
