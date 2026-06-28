<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds\Writers;

/**
 * Сохранение фида с порогом по размеру:
 *  - меньше порога — прямой Storage::put() (без временного файла, минимум I/O);
 *  - больше либо равно порогу — потоковая заливка через временный файл (multipart на S3, меньше памяти).
 *
 * По-умолчанию порог 50 МБ.
 */
class LaravelStorageThresholdWriter implements FeedWriterInterface
{
    /**
     * Порог по-умолчанию, байт (50 МБ).
     */
    public const DEFAULT_THRESHOLD = 50 * 1024 * 1024;

    /**
     * @param string|null $disk - имя диска. null = диск по-умолчанию.
     * @param int $thresholdBytes - порог в байтах. Контент меньше порога пишется напрямую.
     * @param array $options - опции Storage (visibility, ContentType и т.п.) для потоковой ветки.
     * @param FeedWriterInterface|null $smallWriter - переопределение обработчика для маленьких файлов (DI/тесты).
     * @param FeedWriterInterface|null $largeWriter - переопределение обработчика для больших файлов (DI/тесты).
     */
    public function __construct(
        protected string|null $disk = null,
        protected int $thresholdBytes = self::DEFAULT_THRESHOLD,
        protected array $options = [],
        protected FeedWriterInterface|null $smallWriter = null,
        protected FeedWriterInterface|null $largeWriter = null
    ) {
    }

    /**
     * @param string $path
     * @param string $content
     * @return void
     */
    public function write(string $path, string $content): void
    {
        // strlen считает байты — это и есть размер файла.
        if (strlen($content) < $this->thresholdBytes) {
            $writer = $this->smallWriter ?? new LaravelStorageWriter($this->disk);
        } else {
            $writer = $this->largeWriter ?? new LaravelStorageStreamWriter($this->disk, $this->options);
        }
        $writer->write($path, $content);
    }
}
