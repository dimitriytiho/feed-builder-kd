<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds;

class GenerateJsonFeed
{
    /**
     * Сгенерировать JSON-фид (NDJSON) и сохранить.
     *
     * Память: $offers принимается как iterable (массив, Generator, LazyCollection/cursor),
     * строки пишутся потоково во временный файл, затем файл заливается на диск стримом
     * (Laravel Storage::writeStream, multipart на S3). Полный контент в памяти не накапливается.
     *
     * Формат NDJSON:
     *  - строка 1 (если $withHeader): заголовок-обёртка { name, company?, url?, currencyId?, date, categories? };
     *  - строки 2..N: по одному товару (плоский ассоциативный массив) на строку.
     *
     * @param string $name - название фида (обязательно).
     * @param iterable $offers - товары: массив или генератор плоских массивов.
     * @param array $categories
     * @param string|null $putPath - путь сохранения. Если null, фид не сохраняется (вернётся пустая строка пути).
     * @param string|null $disk - Laravel-диск (для S3). null = диск по-умолчанию.
     * @param string|null $company
     * @param string|null $url
     * @param string $currencyId
     * @param string|null $date - если null, текущая дата (DATE_ATOM).
     * @param bool $withHeader - добавить строку-заголовок. По-умолчанию true.
     * @param int $thresholdBytes - порог: файл меньше порога заливается прямым put(), иначе writeStream. По-умолчанию 50 МБ.
     * @return string - путь, по которому сохранён фид (или '' если $putPath не задан).
     * @throws \JsonException|\RuntimeException
     */
    public static function run(
        string $name,
        iterable $offers,
        array $categories = [],
        string|null $putPath = null,
        string|null $disk = null,
        string|null $company = null,
        string|null $url = null,
        string $currencyId = 'RUR',
        string|null $date = null,
        bool $withHeader = true,
        int $thresholdBytes = 50 * 1024 * 1024
    ): string {
        $builder = new JsonBuilder();

        $stream = tmpfile();
        if ($stream === false) {
            throw new \RuntimeException('Не удалось создать временный файл для JSON-фида.');
        }

        try {
            // Заголовок-обёртка первой строкой
            if ($withHeader) {
                fwrite($stream, $builder->headerLine($name, $company, $url, $currencyId, $categories, $date));
            }

            // Товары построчно (потоково)
            foreach ($offers as $offer) {
                fwrite($stream, $builder->offerLine((array) $offer));
            }

            // Сохранение
            if ($putPath) {
                rewind($stream);
                self::save($putPath, $stream, $disk, $thresholdBytes);
                return $putPath;
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return '';
    }

    /**
     * Заливка готового потока: Laravel Storage (writeStream) или нативная файловая система.
     *
     * @param string $putPath
     * @param resource $stream
     * @param string|null $disk
     * @param int $thresholdBytes - меньше порога заливаем put(), иначе writeStream.
     * @return void
     */
    protected static function save(string $putPath, $stream, string|null $disk, int $thresholdBytes): void
    {
        if (class_exists('\Illuminate\Support\Facades\Storage')) {
            $stat = fstat($stream);
            $size = $stat['size'] ?? null;
            if ($size !== null && $size < $thresholdBytes) {
                \Illuminate\Support\Facades\Storage::disk($disk)->put($putPath, stream_get_contents($stream));
            } else {
                \Illuminate\Support\Facades\Storage::disk($disk)->writeStream($putPath, $stream);
            }
            return;
        }

        $dest = fopen($putPath, 'w');
        if ($dest === false) {
            throw new \RuntimeException("Не удалось открыть файл для записи: {$putPath}");
        }
        try {
            stream_copy_to_stream($stream, $dest);
        } finally {
            fclose($dest);
        }
    }
}
