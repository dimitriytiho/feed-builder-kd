<?php

namespace Dimitriytiho\FeedBuilderKd\Feeds;

/**
 * Построение JSON-фида в формате NDJSON (JSON Lines): один JSON-объект на строку.
 *
 * Первая строка — заголовок-обёртка с контекстом (name обязателен, остальное опционально):
 * валюта, url, компания, дата, категории. Дальше — по одному товару (offer) на строку.
 *
 * Экранирование выполняет json_encode; кириллица и слеши не экранируются
 * (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).
 */
class JsonBuilder
{
    /**
     * Флаги кодирования: читаемая кириллица, неэкранированные слеши, исключение при ошибке.
     */
    public const FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    /**
     * @param mixed $data
     * @return string
     * @throws \JsonException
     */
    public function encode(mixed $data): string
    {
        return json_encode($data, self::FLAGS);
    }

    /**
     * Заголовок-обёртка фида. Обязателен только $name, остальные поля
     * попадают в вывод только если переданы непустыми. Дата по-умолчанию — текущая (DATE_ATOM).
     *
     * @param string $name - название фида (обязательно).
     * @param string|null $company
     * @param string|null $url
     * @param string|null $currencyId
     * @param array $categories
     * @param string|null $date - если null, подставляется текущая дата в формате DATE_ATOM.
     * @return array
     */
    public function header(
        string $name,
        string|null $company = null,
        string|null $url = null,
        string|null $currencyId = null,
        array $categories = [],
        string|null $date = null
    ): array {
        $header = ['name' => $name];
        if ($company !== null && $company !== '') {
            $header['company'] = $company;
        }
        if ($url !== null && $url !== '') {
            $header['url'] = $url;
        }
        if ($currencyId !== null && $currencyId !== '') {
            $header['currencyId'] = $currencyId;
        }
        $header['date'] = $date ?: date(DATE_ATOM);
        if ($categories) {
            $header['categories'] = array_values($categories);
        }
        return $header;
    }

    /**
     * Строка заголовка NDJSON (с переводом строки).
     *
     * @throws \JsonException
     */
    public function headerLine(
        string $name,
        string|null $company = null,
        string|null $url = null,
        string|null $currencyId = null,
        array $categories = [],
        string|null $date = null
    ): string {
        return $this->encode($this->header($name, $company, $url, $currencyId, $categories, $date)) . "\n";
    }

    /**
     * Строка одного товара NDJSON (с переводом строки).
     *
     * @param array $offer - плоский ассоциативный массив данных товара.
     * @return string
     * @throws \JsonException
     */
    public function offerLine(array $offer): string
    {
        return $this->encode($offer) . "\n";
    }
}
