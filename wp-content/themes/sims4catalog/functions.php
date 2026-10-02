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
    $mod_id = get_the_ID();
    return [
        'download' => esc_url(get_field('mod_download_url',$mod_id)),
        'author' => esc_html(get_field('mod_author',$mod_id)),
        'source'   => esc_url(get_field('mod_source_url', $mod_id)),
        'thumbnail'    => esc_url(get_the_post_thumbnail_url($mod_id, 'large')),

    ];
}

add_action('wp_enqueue_scripts', 'sims4_catalog_scripts');
add_filter('use_block_editor_for_post', '__return_false');
add_theme_support('post-thumbnails');
