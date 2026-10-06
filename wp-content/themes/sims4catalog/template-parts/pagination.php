<?php

$pages = paginate_links([
    'type'      => 'array',
    'prev_text' => '« Назад',
    'next_text' => 'Вперед »',
]);

if (is_array($pages)) :
    ?>
    <nav aria-label="Навигация по модам" class="mt-5">
        <ul class="pagination justify-content-center">
            <?php foreach ($pages as $page) :
                // Меняем дефолтный класс WP 'page-numbers' на бутстраповский 'page-link'
                $page = str_replace('page-numbers', 'page-link', $page);
                $active_class = strpos($page, 'current') !== false ? 'active' : '';
                ?>
                <li class="page-item <?php echo $active_class; ?>">
                    <?php echo $page; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>
