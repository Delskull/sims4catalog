<?php
if (!defined('ABSPATH')) {
    exit;
}

function sims4_catalog_scripts() {
    wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css', [], '5.3.3');
    wp_enqueue_style('sims4-style', get_stylesheet_uri(), ['bootstrap'], '1.0');
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', [], '5.3.3', true);
}

add_action('wp_enqueue_scripts', 'sims4_catalog_scripts');
