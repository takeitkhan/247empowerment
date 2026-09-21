<h3 class="fw-bold market-title">Events</h3>
<?php
$current_user = wp_get_current_user();
$username = $current_user->user_nicename;

$terms = get_terms([
    'taxonomy' => 'course_category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

$username = get_query_var('store_user');
?>

<ul class="flex-column gap-4 mt-3 nav">
    <li class="nav-item">
        <a class="p-0 nav-link mark-title-clr" href="<?php echo esc_url(home_url("/$username/store")); ?>">
            <i class="fa-solid fa-shop"></i>
            Browse all
        </a>
    </li>

    <?php foreach ($terms as $term): ?>
        <li class="nav-item">
            <a class="p-0 nav-link mark-title-clr" href="<?php echo esc_url(home_url("/$username/store?category={$term->slug}")); ?>">
                <i class="fa-solid fa-tag"></i>
                <?php echo esc_html($term->name); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>