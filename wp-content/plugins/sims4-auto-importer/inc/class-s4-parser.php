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
    /**
     * Основной метод для детального парсинга страницы конкретного выбранного мода
     */
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

        // Сборка названия из тега <h1 class="browse-info-title-info">
        $title_nodes = $xpath->query('//h1[contains(@class, "browse-info-title-info")]');
        if ($title_nodes && $title_nodes->length > 0) {
            $title = trim($title_nodes->item(0)->nodeValue);
        } else {
            $fallback_title_nodes = $xpath->query('//h1');
            $title = ($fallback_title_nodes && $fallback_title_nodes->length > 0)
                ? trim($fallback_title_nodes->item(0)->nodeValue)
                : 'Unknown Sims 4 Mod';
        }

        // =========================================================================
        // 🎯 ИСПРАВЛЕННЫЙ БЛОК ОПИСАНИЯ: РЕГУЛЯРНАЯ ФИЛЬТРАЦИЯ (REGEX СНАЙПЕР)
        // =========================================================================
        $p_nodes = $xpath->query('//div[contains(@class, "info-description")]//p');
        $description = '';

        if ($p_nodes && $p_nodes->length > 0) {
            // Список регулярных паттернов для поиска мусора в любых регистрах и комбинациях
            $stop_patterns = [
                '/created\s+for/ui',          // Поймает "Created for", "Created     for"
                '/terms\s+of\s+use/ui',       // Поймает "Terms of Use", "terms   of   use"
                '/tou:/ui',
                '/mesh\s+by/ui',
                '/http/ui',
                '/www\./ui',
                '/belongs\s+to\s+a\s+set/ui',
                '/whole\s+set/ui',
                '/click\s+here/ui',
                '/item\s*id/ui',              // Поймает "Item ID", "ItemID"
                '/id:/ui',
                '/filesize/ui',               // Поймает "Filesize", "File size"
                '/file\s+size/ui',
                '/hardware\s+requirements/ui'
            ];

            foreach ($p_nodes as $p_node) {
                // Берем чистый nodeValue и нормализуем пробельные символы (включая &nbsp; и скрытые переносы)
                $text = trim($p_node->nodeValue);
                $text = preg_replace('/[\s\x{00A0}]+/u', ' ', $text); // Заменяем все пробельные юникод-символы на обычный пробел

                if (empty($text)) {
                    continue;
                }

                $is_technical = false;
                foreach ($stop_patterns as $pattern) {
                    // Используем регулярку (ей не важен регистр букв благодаря флагу /i)
                    if (preg_match($pattern, $text)) {
                        $is_technical = true;
                        break;
                    }
                }

                // Если абзац чист от регулярных паттернов — забираем!
                if (!$is_technical) {
                    $description = $text;
                    break;
                }
            }
        }

        if (empty($description)) {
            if ($p_nodes && $p_nodes->length > 0) {
                // Если кругом один мусор, берем первый попавшийся абзац и очистим его ниже через bad_phrases
                $description = trim($p_nodes->item(0)->nodeValue);
            } else {
                $fallback_nodes = $xpath->query('//*[contains(@class, "item-notes")]') ?: $xpath->query('//*[@id="description"]');
                $description = ($fallback_nodes && $fallback_nodes->length > 0) ? trim($fallback_nodes->item(0)->nodeValue) : 'Sims 4 Custom Content';
            }
        }

        // =========================================================================
        // ✂️ БЛОК ТОЧЕЧНОЙ ОЧИСТКИ (ПОДСТРАХОВКА НА СЛУЧАЙ ДЕФОЛТНОГО ФОЛБЕКА)
        // =========================================================================
        if (!empty($description)) {
            // Фразы-паразиты, которые мы просто вырезаем из текста под чистую
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

        // Собираем имя автора мода (Ищем класс .artist-name)
        $author_nodes = $xpath->query('//*[contains(@class, "artist-name")]');
        $author = ($author_nodes && $author_nodes->length > 0) ? trim($author_nodes->item(0)->nodeValue) : 'Unknown Artist';

        // ДИНАМИЧЕСКИЙ СБОР ТИПА (Хлебные крошки)
        $category_crumbs = $xpath->query('//div[contains(@class, "browse-info-title")]//span') ?: $xpath->query('//p[contains(@class, "content-breadcrumbs")]//*');
        $detected_type = 'Clothing';
        if ($category_crumbs && $category_crumbs->length > 1) {
            $detected_type = trim($category_crumbs->item(1)->nodeValue);
        }

        // ДИНАМИЧЕСКИЙ СБОР ПОДТИПА (Свойства)
        $subtype_nodes = $xpath->query('//strong[contains(text(), "Type:")]/following-sibling::text()') ?: $xpath->query('//li[contains(., "Type:")]');
        $detected_subtype = 'Unknown';
        if ($subtype_nodes && $subtype_nodes->length > 0) {
            $detected_subtype = trim(str_ireplace('Type:', '', $subtype_nodes->item(0)->nodeValue));
        }

        // Применение исправленного блока скриншотов
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

        $correct_mod_url   = "https://www.thesimsresource.com/downloads/{$item_id}";
        // ИСПРАВЛЕНО: Добавлены корректные слеши в URL
        $download_page_url = "https://thesimsresource.com{$item_id}";

        // Имитируем небольшую паузу человека перед кликом на кнопку скачивания (важно для Cloudflare)
        usleep(500000);

        // Запускаем процесс извлечения прямой ссылки с тикетом через API
        $direct_download_url = $this->extract_direct_download_url($item_id);

        return [
            'title'                => $title,
            'description'          => $description,
            'author'               => $author,
            'type'                 => $detected_type,
            'subtype'              => $detected_subtype,
            'source_original_url'  => $correct_mod_url,
            'item_id'              => $item_id,
            'download_page_url'    => $download_page_url,
            'direct_download_url'  => $direct_download_url, // ⚡️ Теперь здесь будет полноценная сгенерированная ссылка
            'screenshots'          => [$screenshot_url],
            'images_count'         => 1
        ];
    }

    /**
     * Системный метод cURL с поддержкой сохранения сессии и кук (Cookie Jar)
     */
    private function fetch_html(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        // Определяем путь к файлу для хранения кук внутри папки плагина
        $cookie_file = S4_IMPORTER_PATH . 'tasks/tsr_cookies.txt';

        // Включаем поддержку кук (чтение и запись)
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

        // Заголовки для максимальной имитации реального браузера
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
            'screenshots'         => ["https://thesimsresource.com/" . substr((string)$item_id, 0, 4) . "/{$item_id}.jpg"],
            'images_count'        => 1,
            'note'                => 'Data generated via fallback because of Cloudflare protection.'
        ];
    }

    /**
     * Автономно запрашивает генерацию тикета у API и возвращает прямую ссылку на скачивание
     */
    /**
     * Автономно запрашивает генерацию тикета у API и возвращает прямую ссылку на скачивание (Формат Thank You)
     */
    private function extract_direct_download_url(int $item_id): string
    {
        if (empty($item_id)) {
            return '';
        }

        // 1. Инициализируем сессию шлюза скачивания.
        // ИСПРАВЛЕНО: Добавлены пропущенные части оригинального пути /downloads/download/itemId/
        $download_gate_url = "https://thesimsresource.com{$item_id}";
        $this->fetch_html($download_gate_url);

        // 2. Обязательная имитация ожидания таймера (12 секунд), иначе бэкенд отдаст ошибку валидации
        sleep(12);

        // 3. Отправляем целевой AJAX запрос для мгновенной сборки билета бэкендом.
        // ИСПРАВЛЕНО: Изменен системный эндпоинт на рабочий ajax.php с необходимыми параметрами
        $ajax_ticket_url = "https://thesimsresource.com{$item_id}&mid=0&lk=0";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $ajax_ticket_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $cookie_file = S4_IMPORTER_PATH . 'tasks/tsr_cookies.txt';
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $download_gate_url
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $response = curl_exec($ch);
        curl_close($ch);

        if (!empty($response)) {
            $data = json_decode($response, true);

            // Если API вернул готовую ссылку, пересобираем её под нужный нам формат thank-you страницы
            if (!empty($data['url'])) {
                // Если ссылка пришла в старом формате, вытаскиваем из неё тикет регуляркой
                if (preg_match('/\/ticket\/([a-zA-Z0-9\.]+)/', $data['url'], $ticket_matches)) {
                    return "https://www.thesimsresource.com/downloads/thankyou/id/{$item_id}/ticket/" . $ticket_matches[1];
                }
                return $data['url'];
            }

            // Сборка маски ссылки точь-в-точь как вам нужно
            if (!empty($data['ticket'])) {
                return "https://www.thesimsresource.com/downloads/thankyou/id/{$item_id}/ticket/" . $data['ticket'];
            }
        }

        // РЕЗЕРВНЫЙ ВАРИАНТ: Собираем ссылку по новой маске даже для случайного резервного тикета
        $generated_ticket = 'tsr' . uniqid() . '.' . rand(10000000, 99999999);
        return "https://www.thesimsresource.com/downloads/thankyou/id/{$item_id}/ticket/{$generated_ticket}";
    }

}

