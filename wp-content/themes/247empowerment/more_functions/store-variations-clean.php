<?php
/**
 * Course / Product Variations with Rich Text Editor
 * Single editor instance per variation with HTML support
 */

if (!defined('ABSPATH')) exit;

// Register meta box
add_action('add_meta_boxes', function () {
    add_meta_box(
        'mm_course_variations',
        'Product / Course Variations',
        'mm_course_variations_meta_box_html',
        'course',
        'normal',
        'high'
    );
});

function mm_course_variations_meta_box_html($post)
{
    wp_nonce_field('mm_course_variations_save', 'mm_course_variations_nonce');
    $variations = mm_get_course_variations($post->ID);
    ?>
    <style>
        .mm-var-wrap { margin-top: 10px; }
        .mm-var-row { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 15px; margin-bottom: 15px; }
        .mm-var-top { display: grid; grid-template-columns: 2fr 1fr 1.5fr 1fr auto; gap: 12px; margin-bottom: 15px; }
        .mm-var-top > div > label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 12px; text-transform: uppercase; color: #555; }
        .mm-var-top input, .mm-var-top select { width: 100%; padding: 6px; border: 1px solid #ddd; border-radius: 3px; }
        .mm-var-desc-box { border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px; }
        .mm-var-desc-box label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 12px; text-transform: uppercase; color: #555; }
        .mm-var-remove { background: #dc3232; color: #fff; border: 0; padding: 8px 12px; border-radius: 3px; cursor: pointer; font-size: 13px; margin-top: 24px; }
        .mm-var-remove:hover { background: #a00; }
        #mm-var-add { background: #2271b1; color: #fff; border: 0; padding: 10px 20px; border-radius: 3px; cursor: pointer; font-size: 14px; margin-top: 10px; }
        #mm-var-add:hover { background: #135e96; }
        .mm-var-empty { color: #999; font-style: italic; padding: 20px 0; }
    </style>

    <div class="mm-var-wrap">
        <p class="description">Add multiple pricing options for this course. Each variation can have different pricing and billing terms.</p>

        <div id="mm-var-list">
            <?php if (empty($variations)): ?>
                <div class="mm-var-empty" data-placeholder>No variations yet. Click "Add variation" below.</div>
            <?php else: 
                foreach ($variations as $i => $v): 
                    mm_render_variation_row($i, $v);
                endforeach;
            endif; ?>
        </div>

        <button type="button" id="mm-var-add">+ Add variation</button>
    </div>

    <!-- Template for new variations -->
    <script type="text/template" id="mm-var-template">
        <?php mm_render_variation_row('__INDEX__', ['label' => '', 'desc' => '', 'price' => '', 'sku' => '', 'billing' => 'onetime']); ?>
    </script>

    <script>
    (function($){
        var list = $('#mm-var-list');
        var editorInstances = {};

        function reindex() {
            list.find('.mm-var-row').each(function(i) {
                $(this).find('[name]').each(function() {
                    var oldName = $(this).attr('name');
                    var newName = oldName.replace(/mm_variations\[\d+\]/, 'mm_variations[' + i + ']');
                    $(this).attr('name', newName);
                });
            });
        }

        $('#mm-var-add').on('click', function(){
            list.find('[data-placeholder]').remove();
            var newIndex = list.find('.mm-var-row').length;
            var tpl = $('#mm-var-template').html().replace(/__INDEX__/g, newIndex);
            list.append(tpl);
            reindex();
            
            // Initialize editor for new row
            var editorId = 'mm_var_desc_' + newIndex;
            setTimeout(function() {
                if (window.tinyMCE) {
                    window.tinyMCE.execCommand('mceAddEditor', false, editorId);
                }
            }, 100);
        });

        list.on('click', '.mm-var-remove', function(){
            if (!confirm('Remove this variation?')) return;
            
            var editorId = $(this).closest('.mm-var-row').find('[id^="mm_var_desc_"]').attr('id');
            if (editorId && window.tinyMCE && window.tinyMCE.get(editorId)) {
                window.tinyMCE.get(editorId).destroy(true);
            }
            
            $(this).closest('.mm-var-row').remove();
            reindex();
            
            if (!list.find('.mm-var-row').length) {
                list.append('<div class="mm-var-empty" data-placeholder>No variations yet. Click "Add variation" below.</div>');
            }
        });
    })(jQuery);
    </script>
    <?php
}

function mm_render_variation_row($index, $v)
{
    $label = esc_attr($v['label'] ?? '');
    $desc  = $v['desc'] ?? '';
    $price = esc_attr($v['price'] ?? '');
    $sku   = esc_attr($v['sku']   ?? '');
    $billing = $v['billing'] ?? 'onetime';
    $plan_id = $v['plan_id'] ?? '';
    $editor_id = 'mm_var_desc_' . $index;
    ?>
    <div class="mm-var-row">
        <!-- Top row: Label, Price, Billing, SKU, Remove -->
        <div class="mm-var-top">
            <div>
                <label>Label</label>
                <input type="text" name="mm_variations[<?= $index ?>][label]" value="<?= $label ?>" placeholder="e.g. Pro Plan" required>
            </div>
            <div>
                <label>Price (USD)</label>
                <input type="number" step="0.01" min="0" name="mm_variations[<?= $index ?>][price]" value="<?= $price ?>" placeholder="29.99" required>
            </div>
            <div>
                <label>Billing</label>
                <select name="mm_variations[<?= $index ?>][billing]">
                    <option value="onetime" <?= selected($billing, 'onetime', false) ?>>One-time</option>
                    <option value="weekly" <?= selected($billing, 'weekly', false) ?>>Weekly</option>
                    <option value="monthly" <?= selected($billing, 'monthly', false) ?>>Monthly</option>
                    <option value="yearly" <?= selected($billing, 'yearly', false) ?>>Yearly</option>
                </select>
            </div>
            <div>
                <label>SKU (optional)</label>
                <input type="text" name="mm_variations[<?= $index ?>][sku]" value="<?= $sku ?>" placeholder="PRO-01">
            </div>
            <div>
                <button type="button" class="mm-var-remove">Remove</button>
            </div>
        </div>

        <!-- Full-width: Description Editor -->
        <div class="mm-var-desc-box">
            <label>Description (Rich Text)</label>
            <?php
            wp_editor(
                $desc,
                $editor_id,
                [
                    'textarea_name' => 'mm_variations[' . $index . '][desc]',
                    'media_buttons' => false,
                    'textarea_rows' => 4,
                    'teeny' => true,
                ]
            );
            ?>
        </div>

        <input type="hidden" name="mm_variations[<?= $index ?>][plan_id]" value="<?= esc_attr($plan_id) ?>">
    </div>
    <?php
}

// Save handler
add_action('save_post_course', function ($post_id) {
    if (!isset($_POST['mm_course_variations_nonce']) ||
        !wp_verify_nonce($_POST['mm_course_variations_nonce'], 'mm_course_variations_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $raw = $_POST['mm_variations'] ?? [];
    $existing = mm_get_course_variations($post_id);
    $clean = [];
    $errors = [];

    if (is_array($raw)) {
        foreach ($raw as $row) {
            $label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
            $price = isset($row['price']) ? floatval($row['price']) : 0;
            if ($label === '' || $price <= 0) continue;

            $billing = in_array($row['billing'] ?? 'onetime', ['onetime', 'weekly', 'monthly', 'yearly'], true)
                ? $row['billing']
                : 'onetime';

            $sku = isset($row['sku']) ? sanitize_text_field($row['sku']) : '';

            // Find existing variation
            $prev = null;
            foreach ($existing as $e) {
                if (($e['label'] ?? '') === $label && ($e['billing'] ?? 'onetime') === $billing) {
                    $prev = $e;
                    break;
                }
            }

            $plan_id = '';
            if ($billing === 'onetime') {
                $plan_id = '';
            } else {
                $prev_price = $prev['price'] ?? '';
                $prev_plan  = $prev['plan_id'] ?? '';
                if ($prev_plan && $prev_price !== '' && floatval($prev_price) === floatval($price)) {
                    $plan_id = $prev_plan;
                } else {
                    if (function_exists('mm_pp_get_or_create_product')) {
                        $product_id = mm_pp_get_or_create_product($post_id);
                        if (is_wp_error($product_id)) {
                            $errors[] = "Could not create PayPal product for \"$label\": " . $product_id->get_error_message();
                        } else {
                            $interval_map = ['weekly' => 'WEEK', 'monthly' => 'MONTH', 'yearly' => 'YEAR'];
                            $interval = $interval_map[$billing] ?? 'MONTH';
                            $plan = mm_pp_create_plan($product_id, $label, $price, $interval);
                            if (is_wp_error($plan)) {
                                $errors[] = "Could not create PayPal plan for \"$label\": " . $plan->get_error_message();
                            } else {
                                $plan_id = $plan;
                            }
                        }
                    }
                }
            }

            $clean[] = [
                'label'   => $label,
                'price'   => number_format($price, 2, '.', ''),
                'sku'     => $sku,
                'desc'    => isset($row['desc']) ? wp_kses_post($row['desc']) : '',
                'billing' => $billing,
                'plan_id' => $plan_id,
            ];
        }
    }

    if ($clean) {
        update_post_meta($post_id, '_course_variations', wp_json_encode($clean));
    } else {
        delete_post_meta($post_id, '_course_variations');
    }

    if (function_exists('mm_pp_request')) {
        $still_used_plans = array_filter(array_map(function ($r) { return $r['plan_id'] ?? ''; }, $clean));
        foreach ($existing as $e) {
            $old_plan = $e['plan_id'] ?? '';
            if (!$old_plan) continue;
            if (in_array($old_plan, $still_used_plans, true)) continue;
            $resp = mm_pp_request('POST', '/v1/billing/plans/' . rawurlencode($old_plan) . '/deactivate');
        }
    }

    if ($errors) {
        set_transient('mm_var_errors_' . $post_id, $errors, 60);
    }
});

// Display errors
add_action('admin_notices', function () {
    global $post;
    if (!$post || $post->post_type !== 'course') return;
    $errors = get_transient('mm_var_errors_' . $post->ID);
    if (!$errors) return;
    delete_transient('mm_var_errors_' . $post->ID);
    echo '<div class="notice notice-error"><p><strong>PayPal variation issues:</strong></p><ul style="list-style:disc;padding-left:20px;">';
    foreach ($errors as $e) echo '<li>' . esc_html($e) . '</li>';
    echo '</ul></div>';
});

// Helper functions
function mm_get_course_variations($course_id)
{
    $raw = get_post_meta($course_id, '_course_variations', true);
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function mm_get_course_variation($course_id, $index)
{
    $all = mm_get_course_variations($course_id);
    $index = (int) $index;
    return isset($all[$index]) ? $all[$index] : null;
}
