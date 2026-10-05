<?php
get_header();
?>

<div class="container">
    <?php if (have_posts()):
        while (have_posts()) : the_post();
            $mod = get_sims4_mod_data();
            $thumbnail_url = $mod['thumbnail'];
            echo print_r(get_post(), 1);
            ?>

            <div class="row justify-content-center text-center">
                <div class="col-lg-8 col-md-10">

                    <h1 class="fw-bold text-dark display-5 mb-4"><?php the_title(); ?></h1>

                    <div class="mb-5">
                        <a href="#" data-bs-toggle="modal"
                           data-bs-target="#imageLightbox"
                           class="d-inline-block hover-zoom shadow rounded-3 overflow-hidden border border-2 border-dark">
                            <img src="<?php echo esc_url($thumbnail_url); ?>"
                                 class="img-fluid rounded-3 shadow border border-2 border-dark"
                                 alt="<?php the_title_attribute(); ?>"
                                 style="max-height: 550px; width: 100%; object-fit: cover;">
                        </a>
                    </div>

                    <div class="mod-description text-secondary fs-5 text-start lh-base mb-5 px-2">
                        <?php the_content(); ?>
                    </div>

                    <div class="card border border-2 border-dark rounded-3 shadow-sm bg-light py-4 px-3 mb-4">
                        <div class="row align-items-center justify-content-center g-3">

                            <div class="col-sm-6 text-sm-start text-center ps-sm-4">
                                <span class="d-block text-muted small text-uppercase fw-bold">Автор контента</span>
                                <span class="fs-5 text-dark fw-bold">
                                     <?php echo !empty($mod['author'])
                                             ? esc_html($mod['author'])
                                             : 'Не указан'; ?>
                                </span>
                                <span class="d-block text-muted small mt-1">Добавлено: <?php echo get_the_date('d.m.Y'); ?></span>
                            </div>

                            <div class="col-sm-6 text-sm-end text-center pe-sm-4">
                                <?php if (!empty($mod['download'])) : ?>
                                    <a href="<?php echo esc_url($mod['download']); ?>"
                                       class="btn btn-success btn-lg px-5 py-3 rounded-pill fw-bold shadow hover-card"
                                       target="_blank">
                                        СКАЧАТЬ МОД
                                    </a>
                                <?php else : ?>
                                    <button class="btn btn-secondary btn-lg px-5 py-3 rounded-pill fw-bold"
                                            disabled>
                                        Ссылка отсутствует
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <?php get_template_part('template-parts/modal', 'lightbox'); ?>

        <?php
        endwhile;
    endif;
    ?>
</div>

<?php
get_footer();
?>
