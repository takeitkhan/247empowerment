<?php
// Register Blog Post custom post type
function mm_register_blog_post_type()
{
    $labels = [
        'name'               => 'Blog Posts',
        'singular_name'      => 'Blog Post',
        'menu_name'          => 'Blog Posts',
        'name_admin_bar'     => 'Blog Post',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Blog Post',
        'new_item'           => 'New Blog Post',
        'edit_item'          => 'Edit Blog Post',
        'view_item'          => 'View Blog Post',
        'all_items'          => 'All Blog Posts',
        'search_items'       => 'Search Blog Posts',
        'not_found'          => 'No blog posts found.',
        'not_found_in_trash' => 'No blog posts found in Trash.',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => true,
        'show_in_rest'       => true,
        'supports'           => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'],
        'has_archive'        => true,
        'rewrite'            => ['slug' => 'blogs'],
        'menu_icon'          => 'dashicons-admin-post',
    ];

    register_post_type('blog', $args);
}
add_action('init', 'mm_register_blog_post_type');


// Register Unique Blog Category taxonomy
function mm_register_blog_category_taxonomy()
{
    $labels = [
        'name'              => 'Blog Categories',
        'singular_name'     => 'Blog Category',
        'search_items'      => 'Search Blog Categories',
        'all_items'         => 'All Blog Categories',
        'parent_item'       => 'Parent Blog Category',
        'parent_item_colon' => 'Parent Blog Category:',
        'edit_item'         => 'Edit Blog Category',
        'update_item'       => 'Update Blog Category',
        'add_new_item'      => 'Add New Blog Category',
        'new_item_name'     => 'New Blog Category Name',
        'menu_name'         => 'Blog Categories',
    ];

    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => ['slug' => 'blog-category'],
    ];

    register_taxonomy('blog_category', ['blog'], $args);
}
add_action('init', 'mm_register_blog_category_taxonomy');


// Register Blog Tags (unique to blog)
function mm_register_blog_tags_taxonomy()
{
    $labels = [
        'name'                       => 'Blog Tags',
        'singular_name'              => 'Blog Tag',
        'search_items'               => 'Search Blog Tags',
        'popular_items'              => 'Popular Blog Tags',
        'all_items'                  => 'All Blog Tags',
        'edit_item'                  => 'Edit Blog Tag',
        'update_item'                => 'Update Blog Tag',
        'add_new_item'               => 'Add New Blog Tag',
        'new_item_name'              => 'New Blog Tag Name',
        'separate_items_with_commas' => 'Separate tags with commas',
        'add_or_remove_items'        => 'Add or remove blog tags',
        'choose_from_most_used'      => 'Choose from most used blog tags',
        'menu_name'                  => 'Blog Tags',
    ];

    $args = [
        'hierarchical'          => false,
        'labels'                => $labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'update_count_callback' => '_update_post_term_count',
        'show_in_rest'          => true,
        'rewrite'               => ['slug' => 'blog-tag'],
    ];

    register_taxonomy('blog_tag', 'blog', $args);
}
add_action('init', 'mm_register_blog_tags_taxonomy');
// Add meta box for short details
function mm_add_short_details_metabox()
{
    add_meta_box(
        'mm_short_details',
        'Short Details',
        'mm_render_short_details_metabox',
        'blog',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'mm_add_short_details_metabox');

function mm_render_short_details_metabox($post)
{
    $short_details = get_post_meta($post->ID, '_short_details', true);
?>
    <textarea name="mm_short_details" rows="4" style="width:100%;"><?php echo esc_textarea($short_details); ?></textarea>
<?php
}

function mm_save_short_details_metabox($post_id)
{
    if (array_key_exists('mm_short_details', $_POST)) {
        update_post_meta($post_id, '_short_details', sanitize_textarea_field($_POST['mm_short_details']));
    }
}
add_action('save_post', 'mm_save_short_details_metabox');


// Add icon upload field to Blog Category taxonomy
function mm_blog_category_icon_field($term)
{
    $icon = get_term_meta($term->term_id, '_blog_category_icon', true);
?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="blog_category_icon">Category Icon URL</label>
        </th>
        <td>
            <input type="text" name="blog_category_icon" id="blog_category_icon" value="<?php echo esc_attr($icon); ?>" style="width:60%;" />
            <p class="description">Enter the URL of the icon for this category.</p>
        </td>
    </tr>
<?php
}
add_action('blog_category_edit_form_fields', 'mm_blog_category_icon_field', 10, 2);
add_action('blog_category_add_form_fields', function () {
?>
    <div class="form-field">
        <label for="blog_category_icon">Category Icon URL</label>
        <input type="text" name="blog_category_icon" id="blog_category_icon" value="" />
        <p class="description">Enter the URL of the icon for this category.</p>
    </div>
<?php
});


function mm_save_blog_category_icon($term_id)
{
    if (isset($_POST['blog_category_icon'])) {
        update_term_meta($term_id, '_blog_category_icon', sanitize_text_field($_POST['blog_category_icon']));
    }
}
add_action('edited_blog_category', 'mm_save_blog_category_icon', 10, 2);
add_action('created_blog_category', 'mm_save_blog_category_icon', 10, 2);
