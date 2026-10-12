<?php
/**
 * Plugin Name: Sims 4 Auto Importer & AI Translator
 * Description: Автоматический робот на чистом PHP для парсинга модов.
 * Version:     1.0.0
 * Author:      deluta
 * License:     GPL2
 */

// Включаем отображение ошибок на максимум прямо в этой строчке
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('S4_IMPORTER_PATH', plugin_dir_path(__FILE__));

// Подключаем парсер ПРЯМО ЗДЕСЬ И СЕЙЧАС, без функций
require_once S4_IMPORTER_PATH . 'inc/class-s4-parser.php';

// И СРАЗУ ЖЕ запускаем парсинг, убивая загрузку сайта!
$parser = new Sims4_Mod_Parser();
$test_url = 'https://thesimsresource.com';

$result = $parser->parse_tsr_page($test_url);

echo "<div style='background: #000; color: #00ff00; padding: 30px; font-family: monospace; font-size: 16px; border: 5px solid #00ff00; position: fixed; top: 0; left: 0; width: 100%; height: 100%; overflow: auto; z-index: 999999;'>";
echo "<h1>🚀 АБСОЛЮТНЫЙ ТЕСТ ПАРСЕРА 🚀</h1>";
print_r($result);
echo "</div>";

// Жёстко останавливаем PHP. Никакая админка дальше этой строчки не прогрузится!
die();
