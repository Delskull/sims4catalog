<?php
/** @var array $args */
$mod = $args['mod_data'];
$slides = $mod['screenshots'];
?>

<div class="mb-5">
    <div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-2 justify-content-center">
        <?php foreach ($slides as $index => $slide_url) : ?>
            <div class="col">
                <a href="#"
                   data-bs-slide-to="<?php echo $index; ?>"
                   class="d-block ratio ratio-1x1 border border-2 border-dark rounded-3 overflow-hidden shadow-sm hover-card js-open-lightbox">
                    <img src="<?php echo esc_url($slide_url); ?>"
                         class="img-fluid"
                         alt="Скриншот мода Sims 4"
                         style="object-fit: cover; width: 100%; height: 100%;">
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
