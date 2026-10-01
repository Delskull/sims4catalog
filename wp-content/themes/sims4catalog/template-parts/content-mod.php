<?php
/**
 * Шаблон одной карточки мода для списков
 */

$download_url = get_field('mod_download_url');

$thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
if (!$thumbnail_url) {
    $thumbnail_url = 'https://placehold.co';
}
?>

<div class="col">
    <div class="card h-100 shadow-sm border border-2 border-dark overflow-hidden hover-card">

        <img src="<?php echo esc_url($thumbnail_url); ?>" class="card-img-top" alt="<?php the_title_attribute(); ?>" style="height: 220px; object-fit: cover;">

        <div class="card-body d-flex flex-column text-start">
            <h5 class="card-title fw-bold text-dark"><?php the_title(); ?></h5>
            <div class="card-text text-muted small flex-grow-1 mb-3">
                <?php the_excerpt(); ?>
            </div>

            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                <?php if ($download_url) : ?>
                    <a href="<?php echo esc_url($download_url); ?>" class="btn btn-primary btn-sm px-4 rounded-pill fw-bold" target="_blank">
                        Скачать
                    </a>
                <?php else : ?>
                    <span class="text-muted small">Ссылка отсутствует</span>
                <?php endif; ?>

                <small class="text-muted"><?php echo get_the_date('d.m.Y'); ?></small>
            </div>
        </div>
    </div>
</div>
