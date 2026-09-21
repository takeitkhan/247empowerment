<?php
get_header_based_on_login();

if (have_posts()) {
    the_post();
}
?>

<main class="mm-spg-guide-page">
    <?php if (!is_user_logged_in()) : ?>
        <section class="mm-spg-guide-guest">
            <div class="mm-spg-guide-guest__inner">
                <p class="mm-spg-guide-kicker">24/7 Empowerment</p>
                <h1>Choose your guide, build your profile, and unlock your next path.</h1>
                <p class="mm-spg-guide-guest__copy">
                    Start with a free account so your interests, profile progress, wallet journey, and empowerment path can be saved properly.
                </p>
                <div class="mm-spg-guide-guest__actions">
                    <a class="mm-spg-guide-button mm-spg-guide-button--primary" href="<?php echo esc_url(home_url('/signup')); ?>">Sign Up</a>
                    <a class="mm-spg-guide-button mm-spg-guide-button--secondary" href="<?php echo esc_url(home_url('/signin')); ?>">Sign In</a>
                </div>
            </div>
        </section>
    <?php else : ?>
        <?php
        $user_id = get_current_user_id();
        $interest_options = [
            'communications-business-marketing' => 'Communications, Business & Marketing',
            'income-development'               => 'Income Development',
            'sales-careers'                    => 'Sales Careers',
            'sustainable-communities'          => 'Sustainable Communities',
            'personal-empowerment-teams'       => 'Personal Empowerment Teams',
            'nde-spirituality-empowerment'     => 'NDE, Spirituality & Empowerment',
            'narcissistic-abuse-healing'       => 'Narcissistic Abuse Healing',
            'men-women-empowerment'           => 'Men & Women Empowerment',
            'habits-addictions'               => 'Habits & Addictions',
            'diabetes-health-fitness'          => 'Diabetes, Health & Fitness',
        ];

        $selected_categories = get_user_meta($user_id, 'user_categories', true);
        if (!is_array($selected_categories)) {
            $selected_categories = [];
        }

        $selected_priorities = get_user_meta($user_id, 'user_categories_priority', true);
        if (!is_array($selected_priorities)) {
            $selected_priorities = [];
        }

        $errors = [];
        $saved = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mm_interest_selection_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field($_POST['mm_interest_selection_nonce']), 'mm_interest_selection')) {
                $errors[] = 'Security check failed. Please try again.';
            } else {
                $selected_categories = array_values(array_filter(array_map('sanitize_text_field', (array) ($_POST['user_categories'] ?? []))));
                $selected_priorities = [];

                $posted_priorities = (array) ($_POST['user_categories_priority'] ?? []);
                foreach ($posted_priorities as $slug => $priority) {
                    $slug = sanitize_text_field($slug);
                    $priority = sanitize_text_field($priority);
                    if (in_array($slug, $selected_categories, true) && in_array($priority, ['1', '2', '3'], true)) {
                        $selected_priorities[$slug] = (int) $priority;
                    }
                }

                if (count($selected_categories) < 1) {
                    $errors[] = 'Please select at least one interest path.';
                }
                if (count($selected_priorities) < count($selected_categories)) {
                    $errors[] = 'Please assign a priority to each selected interest.';
                }
                if (count(array_unique($selected_priorities)) !== count($selected_priorities)) {
                    $errors[] = 'Please use unique priorities for each selected interest.';
                }

                if (empty($errors)) {
                    update_user_meta($user_id, 'user_categories', $selected_categories);
                    update_user_meta($user_id, 'user_categories_priority', $selected_priorities);
                    update_user_meta($user_id, 'mm_spg_step', 'interest-selection');
                    update_user_meta($user_id, 'mm_spg_status', 'completed');
                    $saved = true;
                }
            }
        }

        $show_resources = $saved || !empty($selected_categories);

        function mm_guide_resources_for_category($slug)
        {
            $map = [
                'communications-business-marketing' => [
                    'Digital Business Card' => '/digital-business-card/',
                    'Video Library' => '/videos/',
                    'Social Management' => '/modify-links/',
                    'Marketing Tools' => '/tools/',
                    'AI Tools' => '/artificial-intelligence/',
                    'Collaboration' => '/collaboration/',
                    'Reputation Marketing' => '/reputation/',
                    'Marketplace' => '/joshuajoseph/store/',
                    'Wallet' => '/wallet/',
                    'Teams' => '/focus-points/',
                    'FAQs' => '/faqs/',
                    'Sales Agreement' => '/sales-agreement-form/',
                    'Participation Agreement' => '/participation-agreement-form/',
                ],
                'income-development' => [
                    'Digital Business Card' => '/digital-business-card/',
                    'Video Library' => '/videos/',
                    'Social Management' => '/modify-links/',
                    'Marketing Tools' => '/tools/',
                    'AI Tools' => '/artificial-intelligence/',
                    'Collaboration' => '/collaboration/',
                    'Reputation Marketing' => '/reputation/',
                    'Marketplace' => '/joshuajoseph/store/',
                    'Wallet' => '/wallet/',
                    'Teams' => '/focus-points/',
                    'FAQs' => '/faqs/',
                    'Sales Agreement' => '/sales-agreement-form/',
                    'Participation Agreement' => '/participation-agreement-form/',
                ],
                'sales-careers' => [
                    'Career Page' => '/careers/',
                    'Video Library' => '/videos/',
                    'Digital Business Card' => '/digital-business-card/',
                    'Marketplace' => '/joshuajoseph/store/',
                    'Wallet' => '/wallet/',
                    'Sales Agreement' => '/sales-agreement-form/',
                    'Participation Agreement' => '/participation-agreement-form/',
                ],
                'sustainable-communities' => [
                    'Teams & Housing' => '/housing/',
                    'Video Library' => '/videos/',
                    'FAQs' => '/faqs/',
                    'Wallet' => '/wallet/',
                ],
                'personal-empowerment-teams' => [
                    'Personal Empowerment Teams' => '/focus-points/',
                    'Video Library' => '/videos/',
                    'FAQs' => '/faqs/',
                    'Wallet' => '/wallet/',
                ],
                'nde-spirituality-empowerment' => [
                    'Personal Empowerment Teams' => '/focus-points/',
                    'Video Library' => '/videos/',
                    'FAQs' => '/faqs/',
                    'Wallet' => '/wallet/',
                ],
                'narcissistic-abuse-healing' => [
                    'Personal Empowerment Teams' => '/focus-points/',
                    'Video Library' => '/videos/',
                    'FAQs' => '/faqs/',
                    'Wallet' => '/wallet/',
                ],
                'men-women-empowerment' => [
                    'Personal Empowerment Teams' => '/focus-points/',
                    'Video Library' => '/videos/',
                    'FAQs' => '/faqs/',
                    'Wallet' => '/wallet/',
                ],
                'habits-addictions' => [
                    'Personal Empowerment Teams' => '/focus-points/',
                    'Video Library' => '/videos/',
                    'FAQs' => '/faqs/',
                    'Wallet' => '/wallet/',
                ],
                'diabetes-health-fitness' => [
                    'Personal Empowerment Teams' => '/focus-points/',
                    'Video Library' => '/videos/',
                    'FAQs' => '/faqs/',
                    'Wallet' => '/wallet/',
                ],
            ];

            return $map[$slug] ?? [];
        }
        ?>

        <section class="mm-spg-guide-user">
            <div class="mm-spg-guide-user__inner">
                <div class="mm-spg-guide-header">
                    <p class="mm-spg-guide-kicker">24/7 Empowerment</p>
                    <h1>Choose your interest paths and unlock your next resources.</h1>
                    <p class="mm-spg-guide-copy">Fill out your strongest interests and we’ll show you the exact pages that match your path.</p>
                </div>

                <?php if (!empty($errors)) : ?>
                    <div class="mm-spg-guide-error">
                        <ul>
                            <?php foreach ($errors as $error) : ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!$show_resources) : ?>
                    <form method="post" class="mm-spg-interest-form">
                        <?php wp_nonce_field('mm_interest_selection', 'mm_interest_selection_nonce'); ?>
                        <div class="mm-spg-interest-grid">
                            <?php foreach ($interest_options as $slug => $label) : ?>
                                <div class="mm-spg-interest-item">
                                    <label class="mm-spg-interest-checkbox">
                                        <input
                                            type="checkbox"
                                            name="user_categories[]"
                                            value="<?php echo esc_attr($slug); ?>"
                                            <?php checked(in_array($slug, $selected_categories, true)); ?>
                                        />
                                        <span><?php echo esc_html($label); ?></span>
                                    </label>

                                    <select name="user_categories_priority[<?php echo esc_attr($slug); ?>]" class="mm-spg-interest-priority">
                                        <option value="">Priority</option>
                                        <?php for ($i = 1; $i <= 3; $i++) : ?>
                                            <option value="<?php echo esc_attr($i); ?>" <?php selected(isset($selected_priorities[$slug]) ? $selected_priorities[$slug] : '', $i); ?>><?php echo esc_html($i . 'st'); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mm-spg-guide-actions">
                            <button type="submit" name="save_interest_selection" class="mm-spg-guide-button mm-spg-guide-button--primary">Save Interests & View Resources</button>
                        </div>
                    </form>
                <?php else : ?>
                    <div class="mm-spg-guide-success">
                        <h2>Great choice.</h2>
                        <p>Your interest path is saved. Explore the exact platform pages that match your selected interest areas.</p>
                    </div>

                    <div class="mm-spg-guide-resources">
                        <?php
                        $all_resources = [];
                        foreach ($selected_categories as $slug) {
                            $category_resources = mm_guide_resources_for_category($slug);
                            foreach ($category_resources as $title => $path) {
                                $all_resources[$title] = home_url($path);
                            }
                        }
                        ?>

                        <?php if (!empty($all_resources)) : ?>
                            <div class="mm-spg-resources-list">
                                <?php foreach ($all_resources as $title => $url) : ?>
                                    <a class="mm-spg-resource-card" href="<?php echo esc_url($url); ?>">
                                        <h3><?php echo esc_html($title); ?></h3>
                                        <p><?php echo esc_html('Open the page built for your interest path.'); ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mm-spg-guide-actions">
                            <a href="<?php echo esc_url(home_url('/wallet/')); ?>" class="mm-spg-guide-button mm-spg-guide-button--secondary">Go to Wallet</a>
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="mm-spg-guide-button mm-spg-guide-button--primary">Go to Dashboard</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php get_footer_based_on_login(); ?>
