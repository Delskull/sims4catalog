<?php
/**
 * Модуль парсера для скачивания и разбора страниц сайтов-доноров
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';
use voku\helper\HtmlDomParser;

class Sims4_Mod_Parser
{
    /**
     * Скачивает страницу и вытаскивает из неё данные мода
     */
    public function parse_target_page(string $url): array
    {
        $html_content = $this->fetch_html($url);

        if (empty($html_content)) {
            return ['error' => 'Не удалось скачать страницу'];
        }
        $dom = HtmlDomParser::str_get_html($html_content);

        // Для теста:самый первый тег <h1> со страницы донора
        $title_element = $dom->findOne('h1');
        $title = $title_element ? $title_element->text() : 'Заголовок не найден';

        return [
            'title' => trim($title),
        ];
    }

    /**
     * Внутренний метод cURL для скачивания сырого HTML
     */
    private function fetch_html(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $html = curl_exec($ch);
        curl_close($ch);

        return $html ? $html : '';
    }
}
