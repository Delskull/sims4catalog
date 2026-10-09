<?php
/**
 * Plugin Name: Sims 4 Auto Importer & AI Translator
 * Description: Автоматический робот на чистом PHP для парсинга модов, перевода через Gemini API и загрузки на Яндекс.Диск.
 * Version:     1.0.0
 * Author:      deluta
 * License:     GPL2
 */

// Защита от прямого вызова файла из браузера вне WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Определяем глобальные константы путей плагина, чтобы удобно подключать файлы
define('S4_IMPORTER_PATH', plugin_dir_path(__FILE__));
define('S4_IMPORTER_URL', plugin_dir_url(__FILE__));

/**
 * Инициализация плагина
 */
function s4_importer_init()
{
    // 🚀 Подключаем файл нашего парсера
    if (file_exists(S4_IMPORTER_PATH . 'inc/class-s4-parser.php')) {
        require_once S4_IMPORTER_PATH . 'inc/class-s4-parser.php';
    }
}
add_action('plugins_loaded', 's4_importer_init');


// =========================================================================
// ВРЕМЕННЫЙ ТЕСТ-ДРАЙВ ПАРСЕРА ДЛЯ ПРОВЕРКИ
// =========================================================================
add_action('admin_notices', function() {
    // Принудительно подключаем файл парсера для теста
    require_once S4_IMPORTER_PATH . 'inc/class-s4-parser.php';

    // Проверяем, существует ли класс, чтобы PHP не выдал ошибку
    if (class_exists('Sims4_Mod_Parser')) {
        $parser = new Sims4_Mod_Parser();

        // Ссылка на тестовый мод с TSR, которую мы разбираем
        $test_url = 'https://thesimsresource.com';

        // Запускаем парсинг
        $result = $parser->parse_tsr_page($test_url);

        // Выводим результат в красивом черном окне вверху админки
        echo "<pre style='background: #111; color: #0f0; padding: 20px; margin: 20px; border-radius: 5px; z-index: 9999; position: relative;'>";
        print_r($result);
        echo "</pre>";
    }
});
