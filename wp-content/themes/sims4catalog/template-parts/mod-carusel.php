<?php
$mod = get_sims4_mod_data();
$slides = $mod['screenshots']; // Наш массив с картинками, который только что вывелся на экран
$mod_id = get_the_ID(); // Получаем уникальный ID текущего мода
if (empty($slides)) {
    $slides = 'https://placehold.co';
}
?><!-- 🔥 НАЧАЛО СЕТКИ МАЛЕНЬКИХ КВАДРАТИКОВ -->
<div class="mb-5">
    <!-- Сетка: по умолчанию 3 в ряд, на больших экранах до 6 в ряд -->
    <div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-2 justify-content-center">
        <?php foreach ($slides as $index => $slide_url) : ?>
            <div class="col">
                <!-- Каждая миниатюра — это кнопка, которая открывает модалку -->
                <!-- Атрибут data-bs-slide-to говорит карусели, какой именно слайд включить! -->
                <a href="#"
                   data-bs-toggle="modal"
                   data-bs-target="#imageLightbox"
                   data-bs-slide-to="<?php echo $index; ?>"
                   class="d-block ratio ratio-1x1 border border-2 border-dark rounded-3 overflow-hidden shadow-sm hover-card">

                    <img src="<?php echo esc_url($slide_url); ?>"
                         class="img-fluid"
                         alt=""
                         style="object-fit: cover; width: 100%; height: 100%;">
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
