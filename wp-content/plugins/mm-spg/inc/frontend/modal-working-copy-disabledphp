<?php

/**
 * Render basic guide modal (MVP)
 */
function mm_spg_render_modal()
{
    if (is_admin() || !is_user_logged_in()) {
        return;
    }
?>
    <div id="mm-spg-modal" class="mm-spg-hidden mm-spg-modal">
        <div class="mm-spg-backdrop"></div>

        <div class="mm-spg-dialog">
            <button class="mm-spg-close" aria-label="Close guide">&times;</button>


            <!-- Title -->
            <div class="welcome-header">
                <h1 class="mm-spg-title"></h1>
            </div>

            <div class="mm-spg-avatar"></div>
            <!-- Dynamic body -->
            <div class="mm-spg-body"></div>

            <!-- Actions -->
            <div class="mm-spg-actions">
                <button class="mm-spg-btn mm-spg-pause">Pause</button>
                <button class="mm-spg-btn mm-spg-next custom-btn">Next</button>
            </div>
        </div>
    </div>


    <button id="mm-spg-launcher" class="mm-spg-hidden mm-spg-launcher guide-btn">
        Guide
    </button>


<?php
}
add_action('wp_footer', 'mm_spg_render_modal');
