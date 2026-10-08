<?php
get_header();
get_template_part('template-parts/categories-grid');

?>

<div class="container">
    <?php if (have_posts()):
        while (have_posts()) : the_post();
            $mod_repo = new Sims4_mod_repository();
            $mod = $mod_repo->get_all_data();
            ?>

            <div class="row justify-content-center text-center">
                <div class="col-lg-8 col-md-10">

                    <h1 class="fw-bold text-dark display-5 mb-4"><?php the_title(); ?></h1>

                    <div class="mb-5">
                        <a href="#" data-bs-toggle="modal"
                           data-bs-target="#imageLightbox"
                           class="d-inline-block hover-zoom shadow rounded-3 overflow-hidden border border-2 border-dark">
                            <img src="<?php echo esc_url($mod['thumbnail']); ?>"
                                 class="img-fluid rounded-3 shadow border-2 border-dark"
                                 alt="<?php the_title_attribute(); ?>"
                                 style="max-height: 550px; width: 100%; object-fit: cover;">
                        </a>
                    </div>

                    <?php get_template_part('template-parts/mod-carousel',null,['mod_data' => $mod]); ?>

                    <div class="mod-description text-secondary fs-5 text-start lh-base mb-5 px-2">
                        <?php the_content(); ?>
                    </div>

                    <div class="card border border-2 border-dark rounded-3 shadow-sm bg-light py-4 px-3 mb-4">
                        <div class="row align-items-center justify-content-center g-3">

                            <div class="col-sm-6 text-sm-start text-center ps-sm-4">
                                <span class="d-block text-muted small text-uppercase fw-bold"><?php esc_html_e('Автор контента', 'sims4catalog') ?></span>
                                <span class="fs-5 text-dark fw-bold">
                                     <?php echo !empty($mod['author'])
                                             ? esc_html($mod['author'])
                                             : 'Не указан'; ?>
                                </span>
                                <span class="d-block text-muted small mt-1">
                                    <?php esc_html_e('Добавлено: ', 'sims4catalog');
                                    echo get_the_date('d.m.Y'); ?></span>
                            </div>

                            <div class="col-sm-6 text-sm-end text-center pe-sm-4">
                                <?php if (!empty($mod['download'])) : ?>
                                    <a href="<?php echo esc_url($mod['download']); ?>"
                                       class="btn btn-success btn-lg px-5 py-3 rounded-pill fw-bold shadow hover-card"
                                       target="_blank"
                                       rel="noopener">
                                        <?php esc_html_e('СКАЧАТЬ МОД', 'sims4catalog') ?>
                                    </a>
                                <?php else : ?>
                                    <button class="btn btn-secondary btn-lg px-5 py-3 rounded-pill fw-bold"
                                            disabled>
                                        <?php esc_html_e('Ссылка отсутствует', 'sims4catalog') ?>
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <?php get_template_part('template-parts/modal-lightbox',null,['mod_data' => $mod]); ?>

        <?php
        endwhile;
    endif;
    ?>
</div>

<?php
get_footer();
?>
