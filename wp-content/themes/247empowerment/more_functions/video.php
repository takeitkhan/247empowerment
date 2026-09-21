<?php

/**
 * --------------------------------------------
 * Register Video Custom Post Type (Playlist)
 * --------------------------------------------
 */
function mm_register_video_post_type()
{
    $labels = [
        'name'               => 'Video Playlists',
        'singular_name'      => 'Video Playlist',
        'menu_name'          => 'Video Playlists',
        'name_admin_bar'     => 'Playlist',
        'add_new'            => 'Add New Playlist',
        'add_new_item'       => 'Add New Playlist',
        'new_item'           => 'New Playlist',
        'edit_item'          => 'Edit Playlist',
        'view_item'          => 'View Playlist',
        'all_items'          => 'All Playlists',
        'search_items'       => 'Search Playlists',
        'not_found'          => 'No playlists found.',
        'not_found_in_trash' => 'No playlists found in Trash.',
    ];

    $args = [
        'labels'        => $labels,
        'public'        => true,
        'show_in_rest'  => true,
        'supports'      => ['title', 'editor', 'thumbnail'],
        'has_archive'   => true,
        'rewrite'       => ['slug' => 'video-playlists'],
        'menu_icon'     => 'dashicons-youtube',
    ];

    register_post_type('video', $args);
}
add_action('init', 'mm_register_video_post_type');



/**
 * --------------------------------------------
 * Playlist Category Taxonomy
 * --------------------------------------------
 */
function mm_register_video_playlist_taxonomy()
{
    $labels = [
        'name'          => 'Playlist Categories',
        'singular_name' => 'Playlist Category',
        'menu_name'     => 'Playlist Categories',
        'search_items'  => 'Search Categories',
        'all_items'     => 'All Categories',
        'edit_item'     => 'Edit Category',
        'add_new_item'  => 'Add New Category',
    ];

    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'playlist-category'],
    ];

    register_taxonomy('video_playlist', ['video'], $args);
}
add_action('init', 'mm_register_video_playlist_taxonomy');



/**
 * --------------------------------------------
 * Playlist Icon (Category Meta)
 * --------------------------------------------
 */
function mm_video_playlist_icon_field($term)
{
    $icon = get_term_meta($term->term_id, '_video_playlist_icon', true);
?>
    <tr class="form-field">
        <th><label for="video_playlist_icon">Playlist Icon URL</label></th>
        <td>
            <input type="text" name="video_playlist_icon" id="video_playlist_icon"
                value="<?php echo esc_attr($icon); ?>" style="width:60%;" />
        </td>
    </tr>
<?php
}
add_action('video_playlist_edit_form_fields', 'mm_video_playlist_icon_field');

add_action('video_playlist_add_form_fields', function () { ?>
    <div class="form-field">
        <label for="video_playlist_icon">Playlist Icon URL</label>
        <input type="text" name="video_playlist_icon" id="video_playlist_icon" />
    </div>
<?php });

function mm_save_video_playlist_icon($term_id)
{
    if (isset($_POST['video_playlist_icon'])) {
        update_term_meta($term_id, '_video_playlist_icon', sanitize_text_field($_POST['video_playlist_icon']));
    }
}
add_action('edited_video_playlist', 'mm_save_video_playlist_icon');
add_action('created_video_playlist', 'mm_save_video_playlist_icon');



/**
 * --------------------------------------------
 * Register Video Topics (Optional Tag Taxonomy)
 * --------------------------------------------
 */
function mm_register_video_topic_taxonomy()
{
    $labels = [
        'name'          => 'Video Topics',
        'singular_name' => 'Video Topic',
        'menu_name'     => 'Topics',
    ];

    $args = [
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'playlist-topic'],
    ];

    register_taxonomy('video_topic', 'video', $args);
}
add_action('init', 'mm_register_video_topic_taxonomy');



/**
 * --------------------------------------------
 * Playlist Meta Fields (YOUTUBE PLAYLIST URL)
 * --------------------------------------------
 */
function mm_add_video_details_metabox()
{
    add_meta_box(
        'mm_video_details',
        'Playlist Details',
        'mm_render_video_details_metabox',
        'video',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'mm_add_video_details_metabox');

function mm_render_video_details_metabox($post)
{
    $playlist_url   = get_post_meta($post->ID, '_playlist_url', true);
    $is_featured    = get_post_meta($post->ID, '_video_featured', true);
    $total_videos   = get_post_meta($post->ID, '_playlist_total_videos', true); // NEW FIELD
    $top_featured = get_post_meta($post->ID, '_video_top_featured', true);
?>
    <p><strong>YouTube Playlist URL</strong></p>
    <input type="text" name="mm_playlist_url" style="width:100%;"
        value="<?php echo esc_attr($playlist_url); ?>">
    <p>Example: https://www.youtube.com/playlist?list=PLxxxxxxx</p>

    <p>
        <label>
            <input type="checkbox" name="mm_video_featured" value="1"
                <?php checked($is_featured, 1); ?>>
            Feature this Playlist
        </label>
    </p>
    <p>
        <label>
            <input type="checkbox" name="video_top_featured" value="1" <?php checked($top_featured, 1); ?> />
            <strong>Mark as Top Featured Playlist</strong>
        </label>
    </p>

    <p><strong>Total Videos</strong></p>
    <input type="number" name="mm_playlist_total_videos" style="width:100%;"
        value="<?php echo esc_attr($total_videos); ?>" min="0">
    <p>Enter the total number of videos in this playlist.</p>

<?php
}

function mm_save_video_details_metabox($post_id)
{
    // Safety checks
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (get_post_type($post_id) !== 'video') return;

    // Playlist URL
    if (isset($_POST['mm_playlist_url'])) {
        update_post_meta(
            $post_id,
            '_playlist_url',
            esc_url_raw($_POST['mm_playlist_url'])
        );
    }

    // Featured checkbox
    update_post_meta(
        $post_id,
        '_video_featured',
        isset($_POST['mm_video_featured']) ? '1' : '0'
    );

    // ✅ TOP FEATURED checkbox (THIS WAS MISSING)
    update_post_meta(
        $post_id,
        '_video_top_featured',
        isset($_POST['video_top_featured']) ? '1' : '0'
    );

    // Total videos
    if (isset($_POST['mm_playlist_total_videos'])) {
        update_post_meta(
            $post_id,
            '_playlist_total_videos',
            intval($_POST['mm_playlist_total_videos'])
        );
    }
}
add_action('save_post', 'mm_save_video_details_metabox');



function mm_extract_youtube_embed($url)
{
    if (strpos($url, 'list=') !== false) {
        // Playlist embed
        preg_match('/list=([^&]+)/', $url, $matches);
        $playlist_id = $matches[1] ?? '';
        return "https://www.youtube.com/embed/videoseries?list=" . $playlist_id;
    }

    // Normal video
    preg_match('/(?:v=|youtu\.be\/)([^&]+)/', $url, $matches);
    $video_id = $matches[1] ?? '';

    return "https://www.youtube.com/embed/" . $video_id;
}
