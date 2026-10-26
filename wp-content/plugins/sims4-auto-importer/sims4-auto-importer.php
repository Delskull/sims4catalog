<?php
/**
 * Plugin Name: Sims 4 Auto Importer & AI Translator
 * Description: Автоматический робот для парсинга модов и генерации случайных задач.
 * Version:     1.2.0
 * Author:      deluta
 */

if (!defined('ABSPATH')) {
    exit;
}

define('S4_IMPORTER_PATH', plugin_dir_path(__FILE__));
define('S4_IMPORTER_URL', plugin_dir_url(__FILE__));

/**
 * Инициализация плагина и подключение класса парсера
 */
function s4_importer_init()
{
    if (file_exists(S4_IMPORTER_PATH . 'inc/class-s4-parser.php')) {
        require_once S4_IMPORTER_PATH . 'inc/class-s4-parser.php';
    }
}
add_action('plugins_loaded', 's4_importer_init');

// =========================================================================
// 🎲 СБОР СЛУЧАЙНОГО МОДА ИЗ КАТЕГОРИИ И ЗАПИСЬ В JSON
// =========================================================================
add_action('init', function() {
    if (is_admin() && isset($_GET['run_parser']) && $_GET['run_parser'] === '1' && !defined('DOING_AJAX')) {

        if (!current_user_can('manage_options')) {
            wp_die('У вас недостаточно прав для запуска этой операции.');
        }

        if (!ini_get('safe_mode')) {
            set_time_limit(180);
        }

        require_once S4_IMPORTER_PATH . 'inc/class-s4-parser.php';

        if (class_exists('Sims4_Mod_Parser')) {
            $parser = new Sims4_Mod_Parser();

            $category_url = 'https://www.thesimsresource.com/downloads/browse/category/sims4-clothing/';

            // 1. Вытаскиваем случайный URL мода из каталога
            $random_target_url = $parser->get_random_mod_url_from_category($category_url);

            // 2. Парсим детальные данные мода
            $mod_data = $parser->parse_tsr_page($random_target_url);

            // 3. Пока просто помечаем, что скачивание ещё не реализовано
            $mod_data['local_file_path'] = '';
            $mod_data['download_status'] = 'pending';
            $mod_data['download_note']   = 'Скачивание будет реализовано через Python-инструмент.';

            // 4. Вычисляем путь к папке задач
            $tasks_dir = S4_IMPORTER_PATH . 'tasks/';
            if (!is_dir($tasks_dir)) {
                mkdir($tasks_dir, 0777, true);
            }

            $file_name = 'task_' . $mod_data['item_id'] . '.json';
            $file_path = $tasks_dir . $file_name;

            $mod_data['status'] = 'raw';
            $mod_data['parsed_at'] = current_time('mysql');

            // 5. Записываем JSON
            $json_text = json_encode($mod_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($file_path, $json_text);
            @chmod($file_path, 0777);

            // 6. Выводим отчёт
            ?>
            <!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <title>Отчет парсера</title>
            </head>
            <body style="background: #111; color: #00ff00; padding: 30px; font-family: monospace; font-size: 16px;">
            <div style="border: 4px solid #00ff00; padding: 20px; max-width: 900px; margin: 0 auto;">
                <h1>🎉 РОБОТ СГЕНЕРИРОВАЛ СЛУЧАЙНУЮ ТАСКУ! 🎉</h1>
                <p><b>Случайная ссылка из каталога:</b> <a href="<?php echo esc_url($random_target_url); ?>" target="_blank" style="color:#fff;"><?php echo esc_html($random_target_url); ?></a></p>
                <p><b>Файл сохранен:</b> <span style="color: #fff;">tasks/<?php echo esc_html($file_name); ?></span></p>

                <p><b>Статус загрузки файла:</b>
                    <span style="color: #ffcc00;">
                        <?php echo esc_html(strtoupper($mod_data['download_status'])); ?>
                    </span>
                </p>
                <p style="color: #ffcc00;"><b>Примечание:</b> <?php echo esc_html($mod_data['download_note']); ?></p>

                <?php if (isset($mod_data['note'])) : ?>
                    <p style="color: #ffcc00;"><b>Внимание:</b> <?php echo esc_html($mod_data['note']); ?></p>
                <?php endif; ?>
                <hr style="border: 1px solid #00ff00;">
                <h3>Итоговая структура JSON:</h3>
                <pre style="background: #222; padding: 15px; color: #fff; overflow-x: auto; border: 1px solid #444;"><?php echo esc_html($json_text); ?></pre>
                <p><a href="<?php echo esc_url(admin_url()); ?>" style="color: #00ff00;">&larr; Вернуться в панель управления WP</a></p>
            </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
});