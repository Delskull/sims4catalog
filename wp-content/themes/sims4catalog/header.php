<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php wp_head(); ?>
</head>
<body <?php body_class('d-flex flex-column min-vh-100'); ?>>

<nav class="navbar navbar-expand-lg custom-navbar mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo esc_url(home_url('/')); ?>">
            <img src="<?php echo get_template_directory_uri(); ?>/src/img/sims4logo.jpg"
                 class="main-logo"
                 alt="logo">
        </a>
    </div>
</nav>