<?php
/**
 * Боевой модуль парсера на встроенных технологиях WordPress & PHP
 * Версия 1.2.0 — без Pupflare, только парсинг
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
        $random_page = rand(1, 100);
        $target_page_url = rtrim($category_base_url, '/') . "/page/{$random_page}/";

        $html = $this->fetch_html($target_page_url);

        if (empty($html)) {
            return 'https://www.thesimsresource.com/downloads/browse/category/sims4-clothing/';
        }

        if (preg_match_all('/\/downloads\/(?:details\/id\/)?(\d+)/', $html, $matches)) {
            $all_ids = array_unique($matches[1]);

            if (!empty($all_ids)) {
                $random_key = array_rand($all_ids);
                $random_id  = $all_ids[$random_key];

                return "https://www.thesimsresource.com/downloads/{$random_id}";
            }
        }

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

        if (empty($html_content) || strlen($html_content) < 2000) {
            return $this->get_demo_data($item_id);
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html_content);
        $xpath = new DOMXPath($dom);

        // Название
        $title_nodes = $xpath->query('//h1[contains(@class, "browse-info-title-info")]');
        if ($title_nodes && $title_nodes->length > 0) {
            $title = trim($title_nodes->item(0)->nodeValue);
        } else {
            $fallback_title_nodes = $xpath->query('//h1');
            $title = ($fallback_title_nodes && $fallback_title_nodes->length > 0)
                    ? trim($fallback_title_nodes->item(0)->nodeValue)
                    : 'Unknown Sims 4 Mod';
        }

        // Описание
        $p_nodes = $xpath->query('//div[contains(@class, "info-description")]//p');
        $description = '';

        if ($p_nodes && $p_nodes->length > 0) {
            $stop_patterns = [
                    '/created\s+for/ui',
                    '/terms\s+of\s+use/ui',
                    '/tou:/ui',
                    '/mesh\s+by/ui',
                    '/http/ui',
                    '/www\./ui',
                    '/belongs\s+to\s+a\s+set/ui',
                    '/whole\s+set/ui',
                    '/click\s+here/ui',
                    '/item\s*id/ui',
                    '/id:/ui',
                    '/filesize/ui',
                    '/file\s+size/ui',
                    '/hardware\s+requirements/ui'
            ];

            foreach ($p_nodes as $p_node) {
                $text = trim($p_node->nodeValue);
                $text = preg_replace('/[\s\x{00A0}]+/u', ' ', $text);

                if (empty($text)) {
                    continue;
                }

                $is_technical = false;
                foreach ($stop_patterns as $pattern) {
                    if (preg_match($pattern, $text)) {
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

        if (empty($description)) {
            if ($p_nodes && $p_nodes->length > 0) {
                $description = trim($p_nodes->item(0)->nodeValue);
            } else {
                $fallback_nodes = $xpath->query('//*[contains(@class, "item-notes")]') ?: $xpath->query('//*[@id="description"]');
                $description = ($fallback_nodes && $fallback_nodes->length > 0) ? trim($fallback_nodes->item(0)->nodeValue) : 'Sims 4 Custom Content';
            }
        }

        if (!empty($description)) {
            $bad_phrases = [
                    'created for: the sims 4',
                    'created for the sims 4',
                    'creator terms of use',
                    'terms of use',
                    'tsr exclusive',
                    'tsr-exclusive',
                    'patreon exclusive',
                    'exclusive',
                    'custom thumbnail',
                    'disabled for random'
            ];

            foreach ($bad_phrases as $phrase) {
                $pattern = '/' . preg_quote($phrase, '/') . '/ui';
                $description = preg_replace($pattern, '', $description);
            }
        }

        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description, " \t\n\r\0\x0B-_,.");

        // Автор
        $author_nodes = $xpath->query('//*[contains(@class, "artist-name")]');
        $author = ($author_nodes && $author_nodes->length > 0) ? trim($author_nodes->item(0)->nodeValue) : 'Unknown Artist';

        // Тип
        $category_crumbs = $xpath->query('//div[contains(@class, "browse-info-title")]//span') ?: $xpath->query('//p[contains(@class, "content-breadcrumbs")]//*');
        $detected_type = 'Clothing';
        if ($category_crumbs && $category_crumbs->length > 1) {
            $detected_type = trim($category_crumbs->item(1)->nodeValue);
        }

        // Подтип
        $subtype_nodes = $xpath->query('//strong[contains(text(), "Type:")]/following-sibling::text()') ?: $xpath->query('//li[contains(., "Type:")]');
        $detected_subtype = 'Unknown';
        if ($subtype_nodes && $subtype_nodes->length > 0) {
            $detected_subtype = trim(str_ireplace('Type:', '', $subtype_nodes->item(0)->nodeValue));
        }

        // Скриншот
        $screenshot_url = '';
        $img_nodes = $xpath->query('//img[contains(@class, "carousel-image")]');
        if ($img_nodes && $img_nodes->length > 0) {
            $raw_src = trim($img_nodes->item(0)->getAttribute('src'));
            if (!empty($raw_src)) {
                $screenshot_url = (strpos($raw_src, 'http') === 0) ? $raw_src : 'https://www.thesimsresource.com' . $raw_src;
            }
        }

        if (empty($screenshot_url)) {
            $string_id = (string)$item_id;
            $folder_prefix = substr($string_id, 0, 4);
            $screenshot_url = "https://thesimsresource.com/{$folder_prefix}/w-920h-690-{$string_id}.jpg";
        }

        $correct_mod_url = "https://www.thesimsresource.com/downloads/{$item_id}";

        return [
                'title'                => $title,
                'description'          => $description,
                'author'               => $author,
                'type'                 => $detected_type,
                'subtype'              => $detected_subtype,
                'source_original_url'  => $correct_mod_url,
                'item_id'              => $item_id,
                'screenshots'          => [$screenshot_url],
                'images_count'         => 1
        ];
    }

    /**
     * Системный метод cURL с поддержкой сохранения сессии и кук
     */
    private function fetch_html(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $cookie_file = S4_IMPORTER_PATH . 'tasks/tsr_cookies.txt';

        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control: max-age=0',
                'Upgrade-Insecure-Requests: 1',
                'Referer: https://www.thesimsresource.com/'
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $html = curl_exec($ch);
        curl_close($ch);

        return $html ? $html : '';
    }

    /**
     * Демо-данные на случай блокировки Cloudflare
     */
    private function get_demo_data(int $item_id): array
    {
        return [
                'title'               => 'Sweater dress (Fallback Mode)',
                'description'         => 'Created for: The Sims 4. Clothing item. New Meshes. HQ.',
                'author'              => 'Mona',
                'type'                => 'Clothing',
                'source_original_url' => "https://www.thesimsresource.com/downloads/{$item_id}",
                'item_id'             => $item_id,
                'screenshots'         => ["https://thesimsresource.com/" . substr((string)$item_id, 0, 4) . "/{$item_id}.jpg"],
                'images_count'        => 1,
                'note'                => 'Data generated via fallback because of Cloudflare protection.'
        ];
    }
}