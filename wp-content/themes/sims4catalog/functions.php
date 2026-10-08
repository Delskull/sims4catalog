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
    wp_enqueue_script(
        'sims4-lightbox',
        get_theme_file_uri('assets/js/lightbox-clicker.js'),
        array(),
        '1.0.0',
        true
    );
}

// хелпер для archive
function sims4_mod(int $mod_id = 0): array
{
    static $memory = [];

    if ($mod_id === 0) {
        $mod_id = get_the_ID();
    }
    if (!isset($memory[$mod_id])) {
        $repo = new Sims4_Mod_Repository($mod_id);
        $memory[$mod_id] = $repo->get_all_data();
    }

    return $memory[$mod_id];
}

add_action('wp_enqueue_scripts', 'sims4_catalog_scripts');
add_filter('use_block_editor_for_post', '__return_false');
add_theme_support('post-thumbnails');
require_once get_theme_file_path('inc/Sims4_mod_repository.php');
