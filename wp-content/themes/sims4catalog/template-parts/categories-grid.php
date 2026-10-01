<?php

$categories = get_categories([
                'hide_empty' => false,
                'exclude' => [1],
                'order' => 'ASC'
        ]
);
$current_cat_id = is_archive() ? get_queried_object_id() : 0;

?>

<div class="container flex-grow-1">

    <?php if (!$current_cat_id): ?>
        <!-- Главный баннер сайта -->
        <div class="p-5 mb-5 bg-light rounded-3 shadow-sm text-center">
            <h1 class="display-5 fw-bold text-primary">Симс 4 Каталог</h1>
            <p class="fs-5 text-muted">Выберите интересующую категорию, чтобы
                открыть список кастомных модов.</p>
        </div>

    <?php endif; ?>

    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 row-cols-lg-4 g-3 text-center justify-content-center mb-5">

        <!-- 1. Одежда -->
        <?php foreach ($categories as $cat):
            $img_url = esc_url(get_field('category_image', 'category_' . $cat->term_id));
            $category = esc_url(get_category_link($cat->term_id));
            $active_class = $cat->term_id === $current_cat_id
                    ? 'text-white border-gold' : 'border-dark text-dark';
            $small_category_css = (!$current_cat_id) ? 'col' : 'col-xl-1 col-md-2 col-6 flex-grow-1';
            ?>
            <div class="<?php echo $small_category_css?>">
                <a href="<?= $category ?>"
                   class="text-decoration-none text-dark h-100 d-block">
                    <div class="card h-100 shadow-sm  border-2  py-2 hover-card  custom-navbar <?php echo $active_class; ?>">
                        <img src="<?= $img_url ?>" class="card-img-top"
                             alt=""
                             style="height: 80px; object-fit: contain;">
                        <div class="card-body py-3">
                            <h5 class="card-title fw-bold m-0 text-uppercase"><?= esc_html($cat->name) ?></h5>
                        </div>
                    </div>
                </a>
            </div>

        <?php endforeach; ?>
    </div>
</div>


