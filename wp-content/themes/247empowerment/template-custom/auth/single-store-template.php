<?php

/**
 * Template Name: Single Store Course Template
 */
if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}
$store_user = get_query_var('store_user');
$course_slug = get_query_var('course_slug');
$is_shareable = get_query_var('shareable'); // '1' if shareable URL

$course = get_page_by_path($course_slug, OBJECT, 'course');

if (!$course) {
    if (!$is_shareable) {
        get_header_based_on_login();
    }
    echo '<p class="text-danger">Course not found.</p>';
    if (!$is_shareable) {
        get_header_based_on_login();
    }
    exit;
}

// Redirect non-logged-in users to login for non-shareable URLs
if (!is_user_logged_in() && !$is_shareable) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

setup_postdata($course);

$price = get_field('price', $course->ID);
$instructor = get_field('instructor', $course->ID);
$duration = get_field('duration', $course->ID);
$short_details = get_field('short_details', $course->ID);
$thumbnail_url = get_the_post_thumbnail_url($course->ID, 'large') ?: '/img/banner.jpg';
$custom_permalink = home_url("/u/{$store_user}/store/{$course_slug}/");

// Variations (custom field system, see more_functions/store-variations.php)
$variations = function_exists('mm_get_course_variations') ? mm_get_course_variations($course->ID) : [];

// Debug: Check what variations were loaded
error_log('📦 [single-store-template] Loaded variations for course ' . $course->ID . ': ' . count($variations) . ' variations');
if (!empty($variations)) {
    foreach ($variations as $idx => $var) {
        error_log('  [' . $idx . '] Label: ' . ($var['label'] ?? 'N/A') . ' | Billing: ' . ($var['billing'] ?? 'N/A') . ' | Plan ID: ' . ($var['plan_id'] ?? 'EMPTY'));
    }
}

// FALLBACK: If no variations saved, auto-create from ACF price field (one-time)
if (empty($variations) && $price) {
    $variations = [
        [
            'label' => get_the_title($course),
            'desc' => $short_details ?: '',
            'price' => (string)$price,
            'sku' => get_field('sku', $course->ID) ?: '',
            'billing' => 'onetime',
            'plan_id' => '',
        ]
    ];
}

$has_variations = !empty($variations);
// Default price used by the JS when no variation is pre-selected:
// - If variations exist: use the first variation's price
// - Else: fall back to the legacy `price` ACF field
$default_price = $has_variations ? $variations[0]['price'] : ($price ?: '');
$default_label = $has_variations ? $variations[0]['label'] : get_the_title($course);
$current_user = wp_get_current_user();

// Get current logged-in user ID (used as a fallback if no slug is provided)
$current_user_id = get_current_user_id();

// 1. Get the user slug from the query variable
$user_slug = get_query_var('user_profile');

// 2. Determine the target user
if ($user_slug) {
    // If a slug is present, try to get the user by their slug (login or nicename)
    $user = get_user_by('slug', $user_slug);
} else {
    // If no slug, fall back to the currently logged-in user
    $user = get_user_by('ID', $current_user_id);
}

// 3. Instantiate the UserProfileData class and get the profile array
if ($user) {
    // We pass the WP_User object to the class constructor, or the ID/slug depending on the class's constructor.
    // Given your original line: $profile = (new UserProfileData($user_slug))->getProfile();
    // We'll update it to pass the $user object for better data handling, assuming the class supports it.
    // If the class REQUIRES a slug, use $user_slug or $user->user_login.

    // Option A: If UserProfileData takes a WP_User object (Recommended)
    $profile_data_instance = new UserProfileData($user);

    // Option B: If UserProfileData only takes the slug (Sticking closer to your original code)
    // Use the slug if present, otherwise use the current user's login.
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);

    // Get the profile array
    $profile = $profile_data_instance->getProfile();
} else {
    // Set variables to null if no user could be determined
    $user = null;
    $profile = null;
}

// Only load header if NOT shareable
if (!$is_shareable) {
    get_header_based_on_login();
}

?>

<?php if ($is_shareable): ?>

    <?php
    $path = $_SERVER['REQUEST_URI'];
    $segments = explode('/', trim($path, '/'));

    $referrer_username = $segments[0] ?? null;

    $login_url = home_url('/signin');
    $register_url = home_url('/signup');

    if ($referrer_username) {
        $login_url    = add_query_arg('ref', $referrer_username, $login_url);
        $register_url = add_query_arg('ref', $referrer_username, $register_url);
    }

    include __DIR__ . '/store-parts/shareable-course.php';
    ?>

<?php else: ?>


    <div class="pb-5 container profile-page pt20">
        <?php
        // 🔍 DEBUG: Show missing plan IDs for admins
        if (current_user_can('manage_options') && $has_variations) {
            $missing_plans = [];
            foreach ($variations as $i => $v) {
                if (in_array($v['billing'] ?? 'onetime', ['weekly', 'monthly', 'yearly'], true) && empty($v['plan_id'])) {
                    $missing_plans[] = $v;
                }
            }
            
            // Check PayPal credentials
            $paypal_creds = function_exists('get_paypal_api_credentials') ? get_paypal_api_credentials() : null;
            $paypal_configured = $paypal_creds && !empty($paypal_creds['client_id']) && !empty($paypal_creds['secret']);
            
            // Check for errors from the last save
            $errors = get_transient('mm_var_errors_' . $course->ID);
            
            if ($missing_plans || !$paypal_configured || $errors) {
                ?>
                <div class="d-flex align-items-start justify-content-between alert alert-danger" style="margin-bottom: 20px; border-left: 4px solid #dc3232;">
                    <div style="flex-grow: 1;">
                        <strong>🔍 Admin Debug: PayPal Configuration Issues</strong>
                        <div style="font-size: 0.9rem; margin-top: 5px;">
                            <?php if (!$paypal_configured): ?>
                                <div style="margin-bottom: 8px;">
                                    ❌ <strong>PayPal Not Configured</strong>
                                    <div style="margin-left: 16px; color: #555; font-size: 0.85rem;">
                                        Client ID or Secret is missing. Go to <strong>Settings → PayPal Settings</strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($errors && is_array($errors)): ?>
                                <div style="margin-bottom: 8px;">
                                    ❌ <strong>PayPal API Errors (Last Save):</strong>
                                    <div style="margin-left: 16px; background: #fff3cd; padding: 8px; border-radius: 3px; margin-top: 4px;">
                                        <?php foreach ($errors as $error): ?>
                                            <div style="font-size: 0.85rem; color: #333; margin: 4px 0;">
                                                • <?= esc_html($error) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($missing_plans): ?>
                                <div>
                                    ❌ <strong>Missing Plan IDs on <?= count($missing_plans) ?> variation(s):</strong>
                                    <div style="margin-left: 16px;">
                                        <?php foreach ($missing_plans as $v): ?>
                                            <div style="font-size: 0.85rem; margin: 4px 0; color: #555;">
                                                📌 <strong><?= esc_html($v['label']) ?></strong> 
                                                (<?= esc_html($v['billing']) ?> @ $<?= esc_html($v['price']) ?>)
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div style="margin-top: 8px; color: #333; font-size: 0.85rem;">
                                    <?php if ($errors): ?>
                                        <strong>→ Check the PayPal error above. Then:</strong>
                                    <?php else: ?>
                                        <strong>→ Fix:</strong>
                                    <?php endif; ?>
                                    Save this course post again to retry creating PayPal plans.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: relative; float: none; margin-left: 10px; cursor: pointer;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php
            }
        }
        ?>
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">

                <?php get_template_part('template-custom/auth/feed-parts/profile-card', null, ['profile' => $profile]); ?>
                <?php
                /**
                 * @var mixed
                 
                $current_user = wp_get_current_user();
                $username = get_query_var('store_user') ?: $current_user->user_nicename;

                $terms = get_terms([
                    'taxonomy' => 'course_category',
                    'hide_empty' => false,
                    'orderby' => 'name',
                    'order' => 'ASC',
                ]);
                    
                <div class="bg-white custom-card navbar-link">
                    <ul class="d-flex flex-column gap-2 nav">
                        <!-- Browse All -->
                        <li class="d-flex align-items-center gap-2 nav-item">
                            <img src="<?= get_template_directory_uri(); ?>/assets/img/nd/empower.png" alt="empower" class="icon-img">
                            <a href="<?php echo esc_url(home_url("/$username/store")); ?>" class="p-0 text">Browse All</a>
                        </li>

                        <!-- Dynamic Category List -->
                        <?php foreach ($terms as $term): ?>
                            <li class="d-flex align-items-center gap-2 nav-item">
                                <?php
                                $icon_id = get_term_meta($term->term_id, 'term_icon', true) ??  get_template_directory_uri() . '/assets/img/marketplace/default.png';
                                if ($icon_id) {
                                    echo '<img class="icon-img" src="' . esc_url(wp_get_attachment_url($icon_id)) . '" alt="' . esc_attr($term->name) . '">';
                                }
                                ?>
                                <a href="<?php echo esc_url(home_url("/$username/store?category={$term->slug}")); ?>" class="p-0 text">
                                    <?php echo esc_html($term->name); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                */
                ?>
                <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
            </div>
            <div class="bg-white mb-0 col-lg-9 custom-card">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="post-search">
                            <div class="gap-3 post-row">
                                <div class="d-flex align-items-center justify-content-between u-title">
                                    <h5 class="portal-title">
                                        <?= esc_html(get_the_title($course)); ?>
                                    </h5>
                                    <button class="d-flex align-items-center gap-2 w-auto text-primary">
                                        <img class="copy-img" src="<?= get_template_directory_uri(); ?>/assets/img/nd/copy-link.png" alt=""> Copy link
                                    </button>
                                </div>
                                <button class="d-flex align-items-center gap-2 w-auto text-primary fs18 fw-medium">
                                    <a href="<?= esc_url(home_url("/u/{$store_user}/store")); ?>">
                                        <img class="object-fit-contain w14" src="<?= get_template_directory_uri(); ?>/assets/img/nd/back-emp.png" alt=""> Go back
                                    </a>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <div>
                                <div class="d-flex justify-content-between my-1 pb-2">
                                    <?php if ($instructor): ?>
                                        <div class="d-flex align-items-center">
                                            <i class="me-2 text-primary-color bi bi-person-fill"></i>
                                            <span><strong>Instructor:</strong> <?= esc_html($instructor); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($duration): ?>
                                        <div class="d-flex align-items-center">
                                            <i class="me-2 text-success bi bi-clock-fill"></i>
                                            <span><strong>Duration:</strong> <?= esc_html($duration); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="pb-4">
                                    <div class="ximg271">
                                        <img class="w-100 h-300 object-fit-cover" src="<?= esc_url($thumbnail_url); ?>" alt="<?= esc_attr(get_the_title($course)); ?>">
                                    </div>
                                </div>
                                <div>
                                    <?php if ($short_details): ?>
                                        <div class="bg-custom-gray mb-4 custom-card">
                                            <p class="mb-3 lead"><?= esc_html($short_details); ?></p>

                                            <?php
                                            $shareable_link = home_url("/u/{$store_user}/store/{$course_slug}/?shareable=1");
                                            ?>

                                            <div class="text-end">
                                                <button id="copyLinkBtn" class="btn-outline-primary btn btn-sm">
                                                    <i class="bi bi-link-45deg"></i> Copy Sharable Link
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?= apply_filters('the_content', $course->post_content); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="upcoming-events">
                            <div class="d-flex u-title">
                                <h5 class="portal-title">Benefits</h5>
                            </div>
                            <div class="d-flex flex-column gap-3 py-4 border-underline">
                                <?php
                                $benefits = [];
                                
                                // Debug: Check what's in the database
                                error_log('🔍 ===== BENEFITS DEBUG START =====');
                                error_log('🔍 Course ID: ' . $course->ID);
                                error_log('🔍 Course Slug: ' . $course->post_name);
                                
                                // Get ALL meta keys for this course
                                $all_meta = get_post_meta($course->ID);
                                error_log('📊 All meta keys: ' . wp_json_encode(array_keys($all_meta)));
                                
                                // Try different meta keys
                                $meta1 = get_post_meta($course->ID, 'course_benefits', true);
                                error_log('📊 course_benefits: ' . wp_json_encode($meta1));
                                
                                $meta2 = get_post_meta($course->ID, '_course_benefits', true);
                                error_log('📊 _course_benefits: ' . wp_json_encode($meta2));
                                
                                // Get from either key
                                $meta_benefits = $meta1 ?: $meta2;
                                
                                if ($meta_benefits && is_array($meta_benefits)) {
                                    $benefits = array_filter($meta_benefits);
                                    error_log('✅ Benefits found: ' . wp_json_encode($benefits));
                                } else {
                                    error_log('⚠️ No benefits found - using defaults');
                                }
                                
                                error_log('🔍 ===== BENEFITS DEBUG END =====');
                                
                                // If still no benefits, use defaults
                                if (empty($benefits)) {
                                    $benefits = [
                                        'Full-day guided experience',
                                        'Personalized empowerment coaching',
                                        'Deep clarity and purpose',
                                        'Reconnection with inner self',
                                        'Transformative legacy activation',
                                    ];
                                }
                                
                                // Display benefits
                                foreach ($benefits as $benefit) {
                                    ?>
                                    <div class="d-flex gap-2">
                                        <div>
                                            <img src="<?= get_template_directory_uri(); ?>/assets/img/nd/right-sign.png" alt="">
                                        </div>
                                        <span><?= esc_html($benefit); ?></span>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                            $buyer_id = get_current_user_id();
                            $purchased_courses = get_user_meta($buyer_id, 'purchased_courses', true);

                            // Ensure it's an array (in case it's unserialized)
                            if (!is_array($purchased_courses)) {
                                $purchased_courses = maybe_unserialize($purchased_courses);
                            }

                            $already_purchased = is_array($purchased_courses) && in_array($course->ID, $purchased_courses);

                            // Per-variation ownership map (index => 'onetime'|'active_sub')
                            $owned_variations = [];
                            if ($buyer_id && $has_variations) {
                                // one-time purchases from log
                                $purchase_log = get_user_meta($buyer_id, 'course_purchase_log', true);
                                if (is_array($purchase_log)) {
                                    foreach ($purchase_log as $entry) {
                                        if ((int)($entry['course_id'] ?? 0) !== (int)$course->ID) continue;
                                        if (($entry['type'] ?? '') === 'subscription') continue; // handled below
                                        if (($entry['type'] ?? '') === 'renewal') continue;
                                        if (($entry['type'] ?? '') === 'subscription_status') continue;
                                        $idx = $entry['variation_index'] ?? -1;
                                        if ($idx >= 0) $owned_variations[(int)$idx] = 'onetime';
                                    }
                                }
                                // active subscriptions
                                $active_subs = get_user_meta($buyer_id, 'active_subscriptions', true);
                                if (is_array($active_subs)) {
                                    foreach ($active_subs as $s) {
                                        if ((int)($s['course_id'] ?? 0) !== (int)$course->ID) continue;
                                        if (($s['status'] ?? '') !== 'ACTIVE' && ($s['status'] ?? '') !== 'APPROVAL_PENDING') continue;
                                        $idx = $s['variation_index'] ?? -1;
                                        if ($idx >= 0) $owned_variations[(int)$idx] = 'active_sub';
                                    }
                                }
                            }
                            ?>
                            <div>
                                <?php if ($has_variations): ?>
                                    <div class="my-4">
                                        <h6 class="mb-2 fw-semibold">Choose a variation:</h6>
                                        <div class="d-flex flex-column gap-2 mb-3" id="mm-variation-list">
                                            <?php
                                            // pick first non-owned variation to be initially selected
                                            $initial_selected_idx = -1;
                                            foreach ($variations as $ii => $vv) {
                                                if (!isset($owned_variations[$ii])) { $initial_selected_idx = $ii; break; }
                                            }
                                            ?>
                                            <?php foreach ($variations as $i => $v): ?>
                                                <?php
                                                $billing = $v['billing'] ?? 'onetime';
                                                $billing_suffix = $billing === 'monthly' ? '/mo' : ($billing === 'yearly' ? '/yr' : '');
                                                $owned_type = $owned_variations[$i] ?? '';
                                                $is_owned = $owned_type !== '';
                                                $is_selected = ($i === $initial_selected_idx);
                                                ?>
                                                <label class="d-flex align-items-start gap-2 p-3 border rounded mm-variation-option <?= $is_owned ? 'mm-variation-owned' : '' ?>"
                                                       style="cursor:<?= $is_owned ? 'not-allowed' : 'pointer' ?>;<?= $is_selected ? 'border-color:#0d6efd;background:#f0f6ff;' : '' ?><?= $is_owned ? 'opacity:0.7;' : '' ?>">
                                                    <input type="radio"
                                                           name="mm_variation"
                                                           value="<?= esc_attr($i) ?>"
                                                           data-price="<?= esc_attr($v['price']) ?>"
                                                           data-label="<?= esc_attr($v['label']) ?>"
                                                           data-billing="<?= esc_attr($billing) ?>"
                                                           data-plan-id="<?= esc_attr($v['plan_id'] ?? '') ?>"
                                                           data-suffix="<?= esc_attr($billing_suffix) ?>"
                                                           data-owned="<?= esc_attr($owned_type) ?>"
                                                           <?= $is_selected ? 'checked' : '' ?>
                                                           <?= $is_owned ? 'disabled' : '' ?>
                                                           class="mt-1">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center justify-content-between">
                                                            <strong><?= esc_html($v['label']) ?></strong>
                                                            <span class="text-primary fs20 fw-bold">$<?= esc_html($v['price']) ?><?= $billing_suffix ? '<small class="ms-1">' . esc_html($billing_suffix) . '</small>' : '' ?></span>
                                                        </div>
                                                        <?php if ($billing !== 'onetime'): ?>
                                                            <span class="bg-info mt-1 text-dark badge"><?= $billing === 'monthly' ? 'Monthly subscription' : 'Yearly subscription' ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($is_owned): ?>
                                                            <span class="bg-success ms-1 mt-1 badge">
                                                                <?= $owned_type === 'active_sub' ? '✓ Active subscription' : '✓ Already purchased' ?>
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($v['desc'])): ?>
                                                            <div class="mt-1 text-muted" style="font-size: 0.875rem;">
                                                                <?php 
                                                                    $desc = $v['desc'];
                                                                    // Remove literal 'rn' string artifacts
                                                                    $desc = str_replace('rn', '', $desc);
                                                                    // Remove escape sequences
                                                                    $desc = str_replace(['\\r\\n', '\\r', '\\n'], '', $desc);
                                                                    // Remove all actual newlines/carriage returns
                                                                    $desc = preg_replace('/[\r\n\t]+/', '', $desc);
                                                                    // Clean up extra spaces around HTML tags
                                                                    $desc = preg_replace('/>\s+</', '><', $desc);
                                                                    echo wp_kses_post($desc);
                                                                ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="d-flex align-items-center gap-3 mb-3">
                                            <span class="fs20">Total:</span>
                                            <span class="fs28 fw-bold" id="mm-selected-price">$<?= esc_html($default_price) ?></span>
                                        </p>
                                        <?php
                                        $all_owned = $has_variations && count($owned_variations) === count($variations);
                                        ?>
                                        <?php if ($all_owned): ?>
                                            <div class="alert alert-success fw-semibold">✅ You own every variation of this course.</div>
                                        <?php else: ?>
                                            <div id="paypal-button-container"></div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif ($price): ?>
                                    <div class="my-4">
                                        <p class="d-flex align-items-center gap-3">
                                            <span class="fs24">Price:</span>
                                            <span class="fs32">$<?= esc_html($price); ?></span>
                                        </p>
                                        <?php if ($already_purchased): ?>
                                            <div class="alert alert-success fw-semibold">✅ You already purchased this course.</div>
                                        <?php else: ?>
                                            <div id="paypal-button-container"></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
endif;

wp_reset_postdata();

if (!$is_shareable) :
?>
    <?php
    $paypal = get_paypal_api_credentials();
    $client_id = esc_js($paypal['client_id']);
    $is_live   = (get_option('paypal_environment') === 'live');
    // Include both `buttons` (one-time) and `subscription` intents so the
    // same SDK can render either flow depending on selected variation.
    $paypal_sdk_url = $is_live
        ? "https://www.paypal.com/sdk/js?client-id={$client_id}&currency=USD&intent=capture&vault=true&components=buttons"
        : "https://www.paypal.com/sdk/js?client-id={$client_id}&currency=USD&intent=capture&vault=true&components=buttons&debug=true";
    ?>
    <!-- ✅ Load PayPal SDK -->
    <script src="<?= $paypal_sdk_url; ?>"></script>

    <!-- ✅ PayPal Button Script -->
    <script>
        // Data shared with PayPal handlers
        var MM_COURSE = {
            id: "<?= esc_js($course->ID); ?>",
            title: "<?= esc_js(get_the_title($course)); ?>",
            hasVariations: <?= $has_variations ? 'true' : 'false' ?>,
            defaultPrice: "<?= esc_js($default_price ?: '10.00'); ?>",
            defaultLabel: "<?= esc_js($default_label); ?>",
            ajaxUrl: "<?= admin_url('admin-ajax.php'); ?>",
            returnUrl: "<?= esc_url($custom_permalink); ?>"
        };

        // 🔍 DEBUG: Log all variations on page load
        (function() {
            if (!MM_COURSE.hasVariations) {
                console.log('✅ No variations - using single price');
                return;
            }
            console.log('📦 === COURSE VARIATIONS DEBUG ===');
            var radios = document.querySelectorAll('input[name="mm_variation"]');
            console.log('Total variations:', radios.length);
            radios.forEach(function(radio, i) {
                console.log('  [' + i + ']', {
                    label: radio.getAttribute('data-label'),
                    price: radio.getAttribute('data-price'),
                    billing: radio.getAttribute('data-billing'),
                    planId: radio.getAttribute('data-plan-id') || '❌ MISSING',
                    checked: radio.checked ? '✓' : ''
                });
            });
            console.log('📦 === END DEBUG ===');
        })();

        function mmGetSelectedVariation() {
            if (!MM_COURSE.hasVariations) {
                return {
                    index: -1, price: MM_COURSE.defaultPrice, label: MM_COURSE.defaultLabel,
                    billing: 'onetime', planId: ''
                };
            }
            var el = document.querySelector('input[name="mm_variation"]:checked');
            if (!el) {
                return {
                    index: 0, price: MM_COURSE.defaultPrice, label: MM_COURSE.defaultLabel,
                    billing: 'onetime', planId: ''
                };
            }
            
            var result = {
                index:   parseInt(el.value, 10),
                price:   el.getAttribute('data-price'),
                label:   el.getAttribute('data-label'),
                billing: el.getAttribute('data-billing') || 'onetime',
                planId:  el.getAttribute('data-plan-id') || ''
            };
            
            // AGGRESSIVE DEBUG
            console.log('🔥 mmGetSelectedVariation() - Raw data attributes:');
            console.log('  data-price:', el.getAttribute('data-price'));
            console.log('  data-label:', el.getAttribute('data-label'));
            console.log('  data-billing:', el.getAttribute('data-billing'));
            console.log('  data-plan-id:', el.getAttribute('data-plan-id'));
            console.log('  Result object:', result);
            
            return result;
        }

        // Live-update the visible total + highlight selected row + re-render PayPal button
        document.addEventListener('DOMContentLoaded', function() {
            var radios = document.querySelectorAll('input[name="mm_variation"]');
            var priceEl = document.getElementById('mm-selected-price');
            radios.forEach(function(r){
                r.addEventListener('change', function(){
                    if (priceEl) {
                        var suffix = r.getAttribute('data-suffix') || '';
                        priceEl.innerHTML = '$' + r.getAttribute('data-price') +
                            (suffix ? ' <small class="ms-1">' + suffix + '</small>' : '');
                    }
                    document.querySelectorAll('.mm-variation-option').forEach(function(l){
                        l.style.borderColor = '';
                        l.style.background = '';
                    });
                    var label = r.closest('.mm-variation-option');
                    if (label) {
                        label.style.borderColor = '#0d6efd';
                        label.style.background  = '#f0f6ff';
                    }
                    // Re-render PayPal buttons for the new variation
                    mmRenderPayPal();
                });
            });
        });

        function mmRenderPayPal() {
            if (typeof paypal === 'undefined') return;
            var container = document.getElementById('paypal-button-container');
            if (!container) return;
            container.innerHTML = ''; // clear previous button

            var v = mmGetSelectedVariation();
            
            // Debug log
            console.log('🔍 mmRenderPayPal() - Selected variation:', v);

            if (v.billing === 'weekly' || v.billing === 'monthly' || v.billing === 'yearly') {
                if (!v.planId) {
                    console.warn('⚠️ Missing plan ID for', v.billing, 'billing');
                    var debugHtml = '<div class="alert alert-warning">' +
                        '<strong>⚠️ Subscription Not Configured</strong>' +
                        '<p style="margin:8px 0 0 0; font-size:0.9rem;">This ' + v.billing + ' plan is missing a PayPal plan ID.</p>' +
                        '<p style="margin:8px 0 0 0; font-size:0.85rem;"><small>Debug: Label="' + v.label + '" | Price=$' + v.price + ' | BillingType=' + v.billing + '</small></p>' +
                        '<p style="margin:8px 0 0 0; font-size:0.85rem;">Please contact the admin to configure PayPal and create billing plans.</p>' +
                        '</div>';
                    container.innerHTML = debugHtml;
                    return;
                }
                paypal.Buttons({
                    style: { layout: 'vertical', label: 'subscribe' },
                    createSubscription: function(data, actions) {
                        return actions.subscription.create({
                            plan_id: v.planId,
                            custom_id: MM_COURSE.id + ':' + v.index
                        });
                    },
                    onApprove: async function(data) {
                        try {
                            const response = await fetch(MM_COURSE.ajaxUrl, {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: new URLSearchParams({
                                    action: "handle_course_subscription",
                                    course_id: MM_COURSE.id,
                                    variation_index: v.index,
                                    subscriptionID: data.subscriptionID
                                })
                            });
                            const result = await response.json();
                            if (result && result.success) {
                                alert('Subscription activated!');
                                window.location.href = MM_COURSE.returnUrl;
                            } else {
                                alert('Subscription verification failed: ' + (result && result.data ? result.data.message : 'unknown'));
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Error activating subscription. Please contact support.');
                        }
                    },
                    onError: function(err) {
                        console.error('PayPal subscription error:', err);
                    }
                }).render('#paypal-button-container');
            } else {
                // One-time payment
                paypal.Buttons({
                    style: { layout: 'vertical' },
                    createOrder: function(data, actions) {
                        return actions.order.create({
                            purchase_units: [{
                                amount: { value: v.price },
                                description: MM_COURSE.title + (v.label && v.label !== MM_COURSE.defaultLabel ? ' — ' + v.label : '')
                            }]
                        });
                    },
                    onApprove: async function(data, actions) {
                        try {
                            const details = await actions.order.capture();
                            const response = await fetch(MM_COURSE.ajaxUrl, {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: new URLSearchParams({
                                    action: "handle_course_purchase",
                                    course_id: MM_COURSE.id,
                                    amount: v.price,
                                    variation_index: v.index,
                                    variation_label: v.label,
                                    orderID: data.orderID,
                                    referrer: sessionStorage.getItem("referrer") || ""
                                })
                            });
                            await response.json();
                            window.location.href = MM_COURSE.returnUrl;
                        } catch (err) {
                            console.error("Error during payment:", err);
                            alert("An error occurred while processing your purchase. Please try again.");
                        }
                    },
                    onError: function(err) {
                        console.error('PayPal order error:', err);
                    }
                }).render('#paypal-button-container');
            }
        }

        function initializePayPalButtons() {
            if (typeof paypal === 'undefined') {
                console.error("PayPal SDK failed to load.");
                return;
            }
            mmRenderPayPal();
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Referral logic
            const urlParams = new URLSearchParams(window.location.search);
            const isShareable = urlParams.get('shareable');

            if (isShareable === '1') {
                const segments = window.location.pathname.split('/');
                const referrer = segments[1]; // e.g., "joseph"

                if (referrer) {
                    sessionStorage.setItem("referred_by", referrer);
                    alert(`You were referred by ${referrer}. Your referral link is now saved!`);

                    const cleanUrl = window.location.origin + window.location.pathname;
                    window.history.replaceState({}, document.title, cleanUrl);
                }
            }

            // Wait until PayPal is fully available
            if (typeof paypal === 'undefined') {
                const interval = setInterval(() => {
                    if (typeof paypal !== 'undefined') {
                        clearInterval(interval);
                        initializePayPalButtons();
                    }
                }, 100);
            } else {
                initializePayPalButtons();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            var button = document.getElementById('copyLinkBtn');
            var link = <?= json_encode($shareable_link); ?>;

            button.addEventListener('click', function() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(link)
                        .then(() => alert("Sharable link copied!"))
                        .catch(() => fallbackCopy(link));
                } else {
                    fallbackCopy(link);
                }
            });

            function fallbackCopy(text) {
                var textarea = document.createElement("textarea");
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    alert("Sharable link copied!");
                } catch (err) {
                    alert("Failed to copy the link.");
                }
                document.body.removeChild(textarea);
            }
        });
    </script>
<?php endif; ?>

<?php get_footer_based_on_login(); ?>