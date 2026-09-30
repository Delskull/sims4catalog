<?php
get_header();
$categories = get_categories([
                'hide_empty' => false,
                'exclude' => [1],
                'order'      => 'ASC'
        ]
);
?>

    <div class="container flex-grow-1">
        <!-- Главный баннер сайта -->
        <div class="p-5 mb-5 bg-light rounded-3 shadow-sm text-center">
            <h1 class="display-5 fw-bold text-primary">Симс 4 Каталог</h1>
            <p class="fs-5 text-muted">Выберите интересующую категорию, чтобы
                открыть список кастомных модов.</p>
        </div>

        <div class="row row-cols-2 row-cols-md-4 g-4 text-center justify-content-center mb-5">

            <!-- 1. Одежда -->
            <?php foreach ($categories as $cat): ?>
                <?php $img_url = get_field('category_image',
                        'category_' . $cat->term_id) ?>
                <div class="col">
                    <a href="<?php echo esc_url(get_category_link($cat -> term_id)); ?>"
                       class="text-decoration-none text-dark h-100 d-block">
                        <div class="card h-100 shadow-sm border border-2 border-dark py-4 hover-card  custom-navbar">
                            <img src="<?= $img_url ?>" class="card-img-top"
                                 alt=""
                                 style="height: 140px; object-fit: contain;">
                            <div class="card-body py-3">
                                <h5 class="card-title fw-bold m-0 text-uppercase"><?= esc_html($cat->name) ?></h5>
                            </div>
                        </div>
                    </a>
                </div>

            <?php endforeach; ?>
        </div>
    </div>

<?php
get_footer();
