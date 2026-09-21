<?php
defined('ABSPATH') || exit;

add_action('add_meta_boxes', function () {
    add_meta_box(
        'spg_step_blocks',
        'Step Blocks',
        'spg_step_blocks_cb',
        'spg_step',
        'normal',
        'high'
    );
});

function spg_step_blocks_cb($post)
{
    $blocks = get_post_meta($post->ID, '_spg_blocks', true);
    $blocks = is_array($blocks) ? $blocks : [];
    wp_nonce_field('spg_step_blocks_save', 'spg_step_blocks_nonce');

?>

    <div id="spg-blocks">
        <?php foreach ($blocks as $i => $block): ?>
            <div class="spg-block" data-index="<?= $i ?>">
                <div class="spg-block-header">
                    <strong>Block #<?= $i + 1 ?></strong>
                    <span class="spg-remove">Remove</span>
                </div>

                <div class="spg-grid">
                    <div class="spg-field">
                        <label>Block Type</label>
                        <select name="spg_blocks[<?= $i ?>][type]">
                            <?php foreach (['text', 'video', 'button', 'shortcode', 'redirect'] as $type): ?>
                                <option value="<?= $type ?>" <?= selected($block['type'] ?? '', $type); ?>>
                                    <?= ucfirst($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="spg-field">
                        <label>Button Label</label>
                        <input type="text" name="spg_blocks[<?= $i ?>][label]" value="<?= esc_attr($block['label'] ?? '') ?>">
                    </div>

                    <!-- <div class="spg-field spg-full">
                        <label>Text Content</label>
                        <textarea name="spg_blocks[<?= $i ?>][content]"><?= esc_textarea($block['content'] ?? '') ?></textarea>
                    </div> -->

                    <div class="spg-field spg-full">
                        <label>Text Content</label>

                        <?php
                        $editor_id = 'spg_blocks_' . $i . '_content';

                        wp_editor(
                            $block['content'] ?? '',
                            $editor_id,
                            [
                                'textarea_name' => "spg_blocks[$i][content]",
                                'media_buttons' => false,
                                'teeny'         => true,
                                'textarea_rows' => 6,
                                'editor_class'  => 'spg-wysiwyg',
                                'quicktags'     => true,
                            ]
                        );
                        ?>
                    </div>


                    <div class="spg-field">
                        <label>Video Source</label>
                        <input type="text" name="spg_blocks[<?= $i ?>][src]" value="<?= esc_attr($block['src'] ?? '') ?>">
                    </div>

                    <div class="spg-field">
                        <label>Redirect URL</label>
                        <input type="text" name="spg_blocks[<?= $i ?>][url]" value="<?= esc_attr($block['url'] ?? '') ?>">
                    </div>

                    <div class="spg-field spg-full">
                        <label>Shortcode</label>
                        <input type="text" name="spg_blocks[<?= $i ?>][shortcode]" value="<?= esc_attr($block['shortcode'] ?? '') ?>">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <button type="button" class="button button-primary" id="spg-add-block">
        + Add Block
    </button>

<?php
}
