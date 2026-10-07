<?php
/**
 * Шаблон одной карточки мода для списков
 */

$mod_repo = new Sims4_mod_repository();
$mod = $mod_repo->get_all_data();
if (!$mod['thumbnail']) {
    $mod['thumbnail'] = 'https://placehold.co';
}
?>

<div class="col">
    <div class="card h-100 shadow-sm border border-2 border-dark overflow-hidden hover-card">
        <a href="<?php the_permalink(); ?>" class="text-decoration-none text-dark d-block h-220">
            <img src="<?php echo esc_url($mod['thumbnail']); ?>" class="card-img-top"
                 alt="<?php the_title_attribute(); ?>"
                 style="height: 220px; object-fit: cover;">

            <div class="card-body d-flex flex-column text-start">
                <h5 class="card-title fw-bold text-dark"><?php the_title(); ?></h5>
                <div class="card-text text-muted small flex-grow-1 mb-3">
                    <?php the_excerpt(); ?>
                </div>
        </a>
                <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                    <?php if ($mod['download']) : ?>
                        <a href="<?php the_permalink(); ?>"
                           class="btn btn-primary btn-sm px-4 rounded-pill fw-bold">
                            <?php esc_html_e('Подробнее', 'sims4catalog'); ?>
                        </a>
                    <?php else : ?>
                        <span class="text-muted small"><?php esc_html_e('Ссылка отсутствует', 'sims4catalog'); ?></span>
                    <?php endif; ?>

                    <small class="text-muted"><?php echo get_the_date('d.m.Y'); ?></small>
                </div>
            </div>

    </div>
</div>
