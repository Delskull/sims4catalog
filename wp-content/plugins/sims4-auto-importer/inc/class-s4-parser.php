<?php
/**
 * Боевой модуль парсера на встроенных технологиях WordPress & PHP
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sims4_Mod_Parser
{
    /**
     * Заходит на случайную страницу пагинации каталога одежды и выбирает оттуда случайный мод
     */
    public function get_random_mod_url_from_category(string $category_base_url): string
    {
        // 1. Генерируем случайную страницу. Ограничим, например, первыми 100 страницами свежего контента.
        // Если хотите вообще по всему сайту (все 5500+ страниц), замените 100 на 5000.
        $random_page = rand(1, 100);

        // Формируем правильный URL с учетом пагинации TSR
        // Было: https://www.thesimsresource.com/downloads/browse/category/sims4-clothing/
        // Станет: https://thesimsresource.com
        $target_page_url = rtrim($category_base_url, '/') . "/page/{$random_page}/";

        // Скачиваем HTML конкретной случайной страницы пагинации
        $html = $this->fetch_html($target_page_url);

        // Если страница пустая или заблокирована, отдаем дефолтный резервный урл первой страницы
        if (empty($html)) {
            return 'https://www.thesimsresource.com/downloads/browse/category/sims4-clothing/';
        }

        // 2. Ищем все ссылки на конкретные моды на этой случайной странице.
        // Регулярное выражение настроено на поиск паттернов вида /downloads/1234567 или /downloads/details/id/1234567
        if (preg_match_all('/\/downloads\/(?:details\/id\/)?(\d+)/', $html, $matches)) {

            // Фильтруем дубликаты ID, чтобы шансы у всех модов на странице были равны
            $all_ids = array_unique($matches[1]);

            if (!empty($all_ids)) {
                // 3. Выбираем случайный ID мода из найденных на ЭТОЙ странице
                $random_key = array_rand($all_ids);
                $random_id  = $all_ids[$random_key];

                // Возвращаем идеальную ссылку на карточку мода
                return "https://www.thesimsresource.com/downloads/{$random_id}";
            }
        }

        // Резервный возврат, если регулярка ничего не нашла из-за изменения верстки
        return 'https://www.thesimsresource.com/downloads/browse/category/sims4-clothing/';
    }

    /**
     * Основной метод для детального парсинга страницы конкретного выбранного мода
     */
    public function parse_tsr_page(string $url): array
    {
        $html_content = $this->fetch_html($url);

        $item_id = 0;
        if (preg_match('/\/downloads\/.*?(\d+)/', $url, $matches)) {
            $item_id = (int)$matches[1];
        }
        if ($item_id === 0) {
            $item_id = 1795031;
        }

        // Если сработала защита Cloudflare (HTML слишком короткий)
        if (empty($html_content) || strlen($html_content) < 2000) {
            return $this->get_demo_data($item_id);
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html_content);
        $xpath = new DOMXPath($dom);

        // =========================================================================
// 🎯 ИСПРАВЛЕННЫЙ БЛОК СБОРА НАЗВАНИЯ (TITLE)
// =========================================================================
// 1. Пробуем найти строго h1 с конкретным классом browse-info-title-info
        $title_nodes = $xpath->query('//h1[contains(@class, "browse-info-title-info")]');

        if ($title_nodes && $title_nodes->length > 0) {
            $title = trim($title_nodes->item(0)->nodeValue);
        } else {
            // 2. Резервный вариант: если класса нет, забираем любой первый h1 на странице
            $fallback_title_nodes = $xpath->query('//h1');
            $title = ($fallback_title_nodes && $fallback_title_nodes->length > 0)
                ? trim($fallback_title_nodes->item(0)->nodeValue)
                : 'Unknown Sims 4 Mod';
        }

        $notes_nodes = $xpath->query('//*[contains(@class, "item-notes")]') ?: $xpath->query('//*[@id="description"]');


        // =========================================================================
        // 🎯 ИСПРАВЛЕННЫЙ БЛОК ОПИСАНИЯ: УМНЫЙ СБОР + ВЫРЕЗАНИЕ СЛОВ-ПАРАЗИТОВ
        // =========================================================================
        $p_nodes = $xpath->query('//div[contains(@class, "info-description")]//p');
        $description = '';

        if ($p_nodes && $p_nodes->length > 0) {
            // 1. Критические стоп-слова. Если абзац СОСТОИТ из этого или содержит жесткий мусор, пропускаем его целиком.
            $stop_words = [
                'created for:',
                'terms of use',
                'tou:',
                'mesh by',
                'http',
                'www.',
                'belongs to a set',
                'whole set',
                'click here',
                'item id:',
                'itemid:',
                'id:',
                'filesize:',
                'file size:',
                'hardware requirements'
            ];

            foreach ($p_nodes as $p_node) {
                $text = trim($p_node->nodeValue);
                $lower_text = mb_strtolower($text, 'UTF-8');

                if (empty($text)) {
                    continue;
                }

                $is_technical = false;
                foreach ($stop_words as $word) {
                    if (mb_strpos($lower_text, $word) !== false) {
                        $is_technical = true;
                        break;
                    }
                }

                if (!$is_technical) {
                    $description = $text;
                    break;
                }
            }
        }

        // Резерв: если живого текста не нашлось, берем первый доступный тег
        if (empty($description)) {
            if ($p_nodes && $p_nodes->length > 0) {
                $description = trim($p_nodes->item(0)->nodeValue);
            } else {
                $fallback_nodes = $xpath->query('//*[contains(@class, "item-notes")]') ?: $xpath->query('//*[@id="description"]');
                $description = ($fallback_nodes && $fallback_nodes->length > 0) ? trim($fallback_nodes->item(0)->nodeValue) : 'Sims 4 Custom Content';
            }
        }

        // =========================================================================
        // ✂️ БЛОК ТОЧЕЧНОЙ ОЧИСТКИ ТЕКСТА (ВЫРЕЗАЕМ ЗАПРЕЩЕННЫЕ СЛОВА)
        // =========================================================================
        if (!empty($description)) {
            // Список фраз, которые нужно молча удалить из текста, не ломая сам текст
            $bad_phrases = [
                'tsr exclusive',
                'tsr-exclusive',
                'patreon exclusive',
                'exclusive',
                'custom thumbnail',
                'disabled for random'
            ];

            foreach ($bad_phrases as $phrase) {
                // Регулярка /.../ui ищет фразу без учета регистра (u — поддержка кириллицы/юникода, i — регистр)
                $pattern = '/' . preg_quote($phrase, '/') . '/ui';
                $description = preg_replace($pattern, '', $description);
            }
        }

        // Финальное сглаживание пробелов после вырезания слов
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description, " \t\n\r\0\x0B-_,."); // Чистим повисшие знаки препинания по краям






        $author_nodes = $xpath->query('//*[contains(@class, "artist-name")]');
        $author = ($author_nodes && $author_nodes->length > 0) ? trim($author_nodes->item(0)->nodeValue) : 'Unknown Artist';

        $correct_mod_url   = "https://www.thesimsresource.com/downloads/{$item_id}";
        $download_page_url = "https://thesimsresource.com{$item_id}";

        $string_id = (string)$item_id;
        $folder_prefix = substr($string_id, 0, 4);
        $screenshot_url = "https://thesimsresource.com{$folder_prefix}/{$string_id}.jpg";

        return [
            'title'               => $title,
            'description'         => $description,
            'author'              => $author,
            'type'                => 'Clothing', // Изменили дефолтный тип на одежду
            'source_original_url' => $correct_mod_url,
            'item_id'             => $item_id,
            'download_page_url'   => $download_page_url,
            'screenshots'         => [$screenshot_url],
            'images_count'        => 1
        ];
    }

    private function fetch_html(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control: max-age=0',
            'Upgrade-Insecure-Requests: 1'
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $html = curl_exec($ch);
        curl_close($ch);

        return $html ? $html : '';
    }

    private function get_demo_data(int $item_id): array
    {
        return [
            'title'               => 'Sweater dress (Fallback Mode)',
            'description'         => 'Created for: The Sims 4. Clothing item. New Meshes. HQ.',
            'author'              => 'Mona',
            'type'                => 'Clothing',
            'source_original_url' => "https://www.thesimsresource.com/downloads/{$item_id}",
            'item_id'             => $item_id,
            'download_page_url'   => "https://thesimsresource.com{$item_id}",
            'screenshots'         => ["https://thesimsresource.com" . substr((string)$item_id, 0, 4) . "/{$item_id}.jpg"],
            'images_count'        => 1,
            'note'                => 'Data generated via fallback because of Cloudflare protection.'
        ];
    }
}
