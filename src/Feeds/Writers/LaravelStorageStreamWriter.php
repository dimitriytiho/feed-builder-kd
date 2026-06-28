<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds\Writers;

/**
 * Сохранение фида через временный файл и потоковую загрузку (Laravel Storage::writeStream).
 *
 * Контент пишется во временный файл (tmpfile), затем заливается на диск потоком.
 * Для S3 это включает multipart upload и снижает пиковое потребление памяти на этапе заливки
 * (HTTP-клиент не буферизует весь payload повторно). Временный файл удаляется автоматически
 * при закрытии дескриптора.
 */
class LaravelStorageStreamWriter implements FeedWriterInterface
{
    /**
     * @param string|null $disk - имя диска. null = диск по-умолчанию.
     * @param array $options - опции Flysystem/Storage (visibility, ContentType и т.п.).
     */
    public function __construct(
        protected string|null $disk = null,
        protected array $options = []
    ) {
    }

    /**
     * @param string $path
     * @param string $content
     * @return void
     */
    public function write(string $path, string $content): void
    {
        $stream = tmpfile();
        if ($stream === false) {
            throw new \RuntimeException('Не удалось создать временный файл для записи фида.');
        }
        try {
            fwrite($stream, $content);
            rewind($stream);
            \Illuminate\Support\Facades\Storage::disk($this->disk)
                ->writeStream($path, $stream, $this->options);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
