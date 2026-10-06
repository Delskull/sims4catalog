<?php
get_header();
get_template_part('template-parts/categories-grid'); ?>

<div class="container mb-5">

    <div class="py-3 mb-4 border-bottom text-start">
        <h2 class="fw-bold text-primary">
            Раздел: <?php single_cat_title(); ?></h2>
        <p class="text-muted m-0">Ниже представлены все кастомные моды,
            доступные в этой категории.</p>
    </div>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">

        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                get_template_part('template-parts/content', 'mod');

            endwhile;
        else :
            ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted fs-5">В этой категории пока нет загруженных
                    модов.</p>
            </div>
        <?php endif; ?>

    </div>

    <?php
    get_template_part('template-parts/pagination');
    ?>

</div>

<?php get_footer(); ?>
