<?php
get_header();
$categories = get_categories([
        'hide_empty' => false,
        'exclude' => [1],
        'order' => 'ASC'
    ]
);

get_template_part('template-parts/categories-grid');

get_footer();
