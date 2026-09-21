<!-- Search -->
<div>
    <div class="d-flex flex-column flex-md-row align-items-center gap-3 mb-2">
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="d-flex flex-column flex-md-row gap-3 w-100">
            <input type="search" name="s" class="w-100 input" placeholder="Search for the article...">
            <input type="hidden" name="post_type" value="blog">
            <div class="c-btn">
                <button type="submit" class="w-100 custom-btn">Search</button>
            </div>
        </form>
    </div>
</div>

<!-- Dynamic Blog Categories -->
<div class="pt-3 navbar-link sidebar-blog-categories">
    <h5 class="mb-3 fw-semibold">Blog Categories</h5>
    <ul class="d-flex flex-column gap-2 nav">
        <?php
        $categories = get_terms([
            'taxonomy' => 'blog_category',
            'hide_empty' => true,
        ]);

        if (!empty($categories) && !is_wp_error($categories)) :
            foreach ($categories as $cat) :
                //$icon = get_template_directory_uri() . '/images/empower.png'; // optional static icon
        ?>
                <li class="d-flex align-items-center nav-item gap10">
                    <!-- <img src="<?php //echo esc_url($icon); 
                                    ?>" alt="<?php //echo esc_attr($cat->name); 
                                                ?>" class="icon-img"> -->
                    <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="p-0 text"><?php echo esc_html($cat->name); ?></a>
                </li>
        <?php
            endforeach;
        endif;
        ?>
    </ul>
</div>

<!-- Recent Posts -->
<div class="pt-3 navbar-link sidebar-recent-posts">
    <h5 class="mb-3 fw-semibold">Recent Posts</h5>
    <ul class="m-0 list-unstyled">
        <?php
        $recent_posts = new WP_Query(array(
            'post_type'      => 'blog',
            'posts_per_page' => 5,
        ));
        if ($recent_posts->have_posts()) :
            while ($recent_posts->have_posts()) : $recent_posts->the_post(); ?>
                <li class="mb-2">
                    <a href="<?php the_permalink(); ?>" class="text-dark text-decoration-none"><?php the_title(); ?></a>
                </li>
        <?php endwhile;
            wp_reset_postdata();
        else :
            echo '<li class="text-muted">No recent posts.</li>';
        endif;
        ?>
    </ul>
</div>