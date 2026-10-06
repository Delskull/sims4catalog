<?php
if (!defined('ABSPATH')) {
    exit;
}

function sims4_catalog_scripts()
{
    wp_enqueue_style('bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        [], '5.3.3');
    wp_enqueue_style('sims4-header',
        get_template_directory_uri() . '/assets/css/header.css', ['bootstrap'],
        time());
    wp_enqueue_style('sims4-body',
        get_template_directory_uri() . '/assets/css/body.css', ['bootstrap'],
        time());
    wp_enqueue_style('sims4-style', get_stylesheet_uri(), ['bootstrap'], '1.0');
    wp_enqueue_script('bootstrap-js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        [], '5.3.3', true);
}

function get_sims4_mod_data(): array
{
    $raw_thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'large');
    $thumbnail = $raw_thumbnail ? $raw_thumbnail : get_theme_file_uri('src/img/no-image.jpg');
    $mod_id = get_the_ID();

    $screenshots = [];

    // 1. Первой в массив всегда пускаем главную фотку (Изображение записи)
    if (has_post_thumbnail()) {
        $screenshots[] = get_the_post_thumbnail_url($mod_id, 'large');
    }

    $attachments = get_posts([
        'post_type'      => 'attachment',
        'posts_per_page' => -1,          // Забираем абсолютно все картинки
        'post_parent'    => $mod_id,     // Ищем только те, что привязаны к этому моду
        'post_mime_type' => 'image',     // Нам нужны строго изображения, не архивы
        'exclude'        => get_post_thumbnail_id($mod_id), // Исключаем главную, чтобы не дублировать её
        'orderby'        => 'menu_order',// Учитываем порядок сортировки, если вы двигали их в медиабиблиотеке
        'order'          => 'ASC'
    ]);

    // Если прикреплённые картинки найдены — вытаскиваем их чистые URL
    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            $img_url = wp_get_attachment_url($attachment->ID);
            if ($img_url) {
                $screenshots[] = $img_url;
            }
        }
    }

    return [
        'download' => get_field('mod_download_url', $mod_id),
        'author' => get_field('mod_author', $mod_id) ?? 'Неизвестный автор',
        'source' => get_field('mod_source_url', $mod_id),
        'thumbnail' => $thumbnail,
        'screenshots' => $screenshots,
    ];
}

add_action('wp_enqueue_scripts', 'sims4_catalog_scripts');
add_filter('use_block_editor_for_post', '__return_false');
add_theme_support('post-thumbnails');
