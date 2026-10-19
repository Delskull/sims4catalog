<?php
/**
 * Plugin Name: Sims 4 Auto Importer & AI Translator
 * Description: Автоматический робот для парсинга модов, генерации случайных задач и интеграции с ИИ.
 * Version:     1.0.0
 * Author:      deluta
 */

if (!defined('ABSPATH')) {
    exit;
}

define('S4_IMPORTER_PATH', plugin_dir_path(__FILE__));
define('S4_IMPORTER_URL', plugin_dir_url(__FILE__));

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
// 🎲 УМНЫЙ СБОР СЛУЧАЙНОГО МОДА ИЗ КАТЕГОРИИ ОДЕЖДЫ И ЗАПИСЬ В JSON
// =========================================================================
add_action('init', function() {
    if (is_admin() && isset($_GET['run_parser']) && $_GET['run_parser'] == '1' && !defined('DOING_AJAX')) {

        require_once S4_IMPORTER_PATH . 'inc/class-s4-parser.php';

        if (class_exists('Sims4_Mod_Parser')) {
            $parser = new Sims4_Mod_Parser();

            // 📂 Ссылка на общую категорию одежды на TSR, которую робот будет сканировать
            $category_url = 'https://thesimsresource.com';

            // 1. 🎲 Робот заходит в каталог и вытаскивает оттуда СЛУЧАЙНЫЙ рабочий URL мода
            $random_target_url = $parser->get_random_mod_url_from_category($category_url);

            // 2. Парсим детальные данные выбранного случайного мода
            $mod_data = $parser->parse_tsr_page($random_target_url);

            // 3. Вычисляем абсолютный системный путь к папке задач внутри Docker
            $tasks_dir = WP_PLUGIN_DIR . '/sims4-auto-importer/tasks/';
            if (!is_dir($tasks_dir)) {
                mkdir($tasks_dir, 0777, true);
            }

            $file_name = 'task_' . $mod_data['item_id'] . '.json';
            $file_path = $tasks_dir . $file_name;

            $mod_data['status'] = 'raw';
            $mod_data['parsed_at'] = date('Y-m-d H:i:s');

            // 4. Записываем свежую JSON таску на диск
            $json_text = json_encode($mod_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            file_put_contents($file_path, $json_text);
            @chmod($file_path, 0777);

            // 5. Выводим сочный зеленый отчет на весь экран
            echo "<div style='background: #111; color: #00ff00; padding: 30px; font-family: monospace; font-size: 16px; border: 4px solid #00ff00; position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: auto; z-index: 999999;'>";
            echo "<h1>🎉 РОБОТ СГЕНЕРИРОВАЛ СЛУЧАЙНУЙ ТАСКУ ИЗ КАТЕГОРИИ! 🎉</h1>";
            echo "<p><b>Случайная ссылка, добытая из каталога:</b> <a href='".esc_url($random_target_url)."' target='_blank' style='color:#fff;'>".esc_html($random_target_url)."</a></p>";
            echo "<p><b>Файл успешно сохранен на диск Docker:</b> <span style='color: #fff;'>tasks/" . $file_name . "</span></p>";
            echo "<hr style='border-color: #00ff00;'>";
            echo "<h3>Содержимое созданного JSON:</h3>";
            echo "<pre style='color: #00ff00; font-size: 14px; font-family: monospace;'>" . htmlspecialchars($json_text) . "</pre>";
            echo "</div>";

            die();
        }
    }
});
