<?php
/**
 * Шаблон модального окна Lightbox для увеличения картинок
 */
$mod = $args['mod_data'];
$thumbnail_url = $mod['thumbnail'];
?>

<div class="modal fade" id="imageLightbox" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0 shadow-none position-relative">

            <button type="button"
                    class="btn-close btn-close-white position-absolute top-0 end-0 m-3 fs-4"
                    data-bs-dismiss="modal" aria-label="Close"
                    style="z-index: 1060;"></button>

            <div class="modal-body text-center p-0">
                <img src="<?php echo esc_url($thumbnail_url); ?>"
                     class="img-fluid rounded-3 border border-2 border-light"
                     alt="<?php the_title_attribute(); ?>"
                     style="max-height: 90vh; object-fit: contain;">
            </div>

        </div>
    </div>
</div>
