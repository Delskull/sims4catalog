<?php
/**
 * Plugin Name: Sims 4 Auto Importer & AI Translator
 * Description: Профессиональный робот для парсинга модов, перевода через Gemini API и загрузки на Яндекс.Диск.
 * Version:     1.0.0
 * Author:      deluta
 */

if (!defined('ABSPATH')) {
    exit;
}

define('S4_IMPORTER_PATH', plugin_dir_path(__FILE__));
define('S4_IMPORTER_URL', plugin_dir_url(__FILE__));

ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Инициализация плагина
 */
function s4_importer_init()
{
    if (file_exists(S4_IMPORTER_PATH . 'inc/class-s4-parser.php')) {
        require_once S4_IMPORTER_PATH . 'inc/class-s4-parser.php';
    }
}
add_action('plugins_loaded', 's4_importer_init');

// =========================================================================
// 🚀 ИСПРАВЛЕННЫЙ ТЕСТ-ДРАЙВ ЗАПИСИ JSON
// =========================================================================
add_action('init', function() {
    if (is_admin() && isset($_GET['run_parser']) && $_GET['run_parser'] == '1') {

        $mod_data = [
            'title'               => 'Halloween Latex Nun Costume Top',
            'description'         => 'Created for: The Sims 4. Top - 4 Swatches. New Meshes. HQ.',
            'author'              => 'Harmonia',
            'type'                => 'Tops',
            'source_original_url' => 'https://thesimsresource.com',
            'item_id'             => 1794319,
            'download_page_url'   => 'https://thesimsresource.com',
            'screenshots'         => [
                'https://thesimsresource.com'
            ],
            'images_count'        => 1,
            'status'              => 'raw',
            'parsed_at'           => date('Y-m-d H:i:s')
        ];

        $tasks_dir = S4_IMPORTER_PATH . 'tasks/';
        if (!is_dir($tasks_dir)) {
            mkdir($tasks_dir, 0755, true);
        }

        $file_name = 'task_' . $mod_data['item_id'] . '.json';
        $file_path = $tasks_dir . $file_name;

        $json_text = json_encode($mod_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents($file_path, $json_text);

        // ВЫВОДИМ ИДЕАЛЬНЫЙ ЧИСТЫЙ HTML
        echo "<div style='background: #111; color: #00ff00; padding: 30px; font-family: monospace; font-size: 16px; border: 4px solid #00ff00; position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: auto; z-index: 999999;'>";
        echo "<h1>🎉 УСПЕХ! JSON-ФАЙЛ УСПЕШНО СГЕНЕРИРОВАН НА ДИСК! 🎉</h1>";
        echo "<p><b>Путь к созданному файлу:</b> <span style='color: #fff;'>wp-content/plugins/sims4-auto-importer/tasks/" . $file_name . "</span></p>";
        echo "<hr style='border-color: #00ff00;'>";
        echo "<h3>Содержимое записанного JSON:</h3>";
        echo "<pre style='color: #00ff00; font-size: 14px; font-family: monospace;'>" . esc_html($json_text) . "</pre>";
        echo "</div>";

        die();
    }
});
