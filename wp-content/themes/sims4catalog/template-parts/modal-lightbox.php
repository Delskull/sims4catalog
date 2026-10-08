<?php
/**
 * Шаблон модального окна Lightbox для увеличения картинок с каруселью
 *
 * @var array $args
 */

$mod = $args['mod_data'];
$slides = $mod['screenshots'];

if (empty($slides)) {
    $slides = [$mod['thumbnail']];
}
?>

<div class="modal fade" id="imageLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0 shadow-none position-relative">
            <button type="button"
                    class="btn-close btn-close-white position-absolute top-0 end-0 m-3 fs-4"
                    data-bs-dismiss="modal" aria-label="Close"
                    style="z-index: 1060;"></button>
            <div class="modal-body text-center p-0">
                <div id="lightboxCarousel" class="carousel slide"
                     data-bs-ride="false">
                    <div class="carousel-inner">
                        <?php foreach ($slides as $index => $slide_url) : ?>
                            <div class="carousel-item <?php echo $index === 0
                                    ? 'active' : ''; ?>">
                                <img src="<?php echo esc_url($slide_url); ?>"
                                     class="img-fluid rounded-3 border border-2 border-light"
                                     alt="<?php the_title_attribute(); ?>"
                                     style="max-height: 85vh; object-fit: contain; width: auto; margin: 0 auto;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($slides) > 1) : ?>
                        <button class="carousel-control-prev" type="button"
                                data-bs-target="#lightboxCarousel"
                                data-bs-slide="prev">
                            <span class="carousel-control-prev-icon"
                                  aria-hidden="true"></span>
                            <span class="visually-hidden"><?php esc_html_e('Назад')?></span>
                        </button>
                        <button class="carousel-control-next" type="button"
                                data-bs-target="#lightboxCarousel"
                                data-bs-slide="next">
                            <span class="carousel-control-next-icon"
                                  aria-hidden="true"></span>
                            <span class="visually-hidden"><?php esc_html_e('Вперёд')?></span>
                        </button>
                    <?php endif; ?>

                </div>

            </div>

        </div>
    </div>
</div>