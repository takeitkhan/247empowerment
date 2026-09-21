<?php
class UserProfileData
{
    private static $instance = null;
    private $user;
    private $referred_users = null;

    public function __construct($user = null)
    {
        if ($user instanceof WP_User) {
            $this->user = $user;
        } elseif (is_numeric($user)) {
            // ✅ NEW: support user ID
            $this->user = get_user_by('id', (int) $user);
        } elseif (is_string($user)) {
            // username / slug
            $this->user = mm_resolve_public_user($user);
        } elseif (is_user_logged_in()) {
            $this->user = wp_get_current_user();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get the user profile data
    // This returns an array of user information including ID, username, email, display name,

    public function getProfile()
    {
        if (!$this->user) return null;

        // Fetch the array of category IDs
        $user_categories = get_user_meta($this->user->ID, 'user_categories', true);



        // Initialize the names array
        $user_category_names = [];

        // Assuming the user categories are stored in the default 'category' taxonomy
        $category_taxonomy = 'category';

        if (!empty($user_categories) && is_array($user_categories)) {
            foreach ($user_categories as $cat_id) {
                // Check if the ID is numeric to prevent errors
                if (is_numeric($cat_id)) {
                    // Pass both the ID and the taxonomy name to retrieve the term object
                    $term = get_term((int)$cat_id, $category_taxonomy);

                    if ($term && !is_wp_error($term)) {
                        // Add the category name to the array
                        $user_category_names[] = $term->name;
                    }
                }
            }
        }

        $social_links = get_user_meta($this->user->ID, 'custom_social_links', true);
        $social_links = is_array($social_links) ? $social_links : [];

        $user_categories_priority = get_user_meta(
            $this->user->ID,
            'user_categories_priority',
            true
        );
        $user_categories_priority = is_array($user_categories_priority)
            ? $user_categories_priority
            : [];

        $user_categories_with_priority = [];

        if (!empty($user_categories) && is_array($user_categories)) {
            foreach ($user_categories as $cat_id) {
                if (!is_numeric($cat_id)) continue;

                $term = get_term((int) $cat_id, 'category');
                if (!$term || is_wp_error($term)) continue;

                $user_categories_with_priority[] = [
                    'term_id'  => (int) $cat_id,
                    'name'     => $term->name,
                    'slug'     => $term->slug,
                    'priority' => $user_categories_priority[$cat_id] ?? null,
                ];
            }
        }

        $zoom_user_id = get_user_meta($this->user->ID, '_zoom_user_id', true);
        $zoom_user_name = get_user_meta($this->user->ID, '_zoom_user_name', true);
        $zoom_scopes = get_user_meta($this->user->ID, '_zoom_scopes', true);
        $zoom_connected_at = get_user_meta($this->user->ID, '_zoom_connected_at', true);
        $zoom_token_expires_at = get_user_meta($this->user->ID, '_zoom_token_expires_at', true);
        $zoom_access_token_encrypted = get_user_meta($this->user->ID, '_zoom_access_token', true);

        return [
            'id' => $this->user->ID,
            'username' => $this->user->user_login,
            'user_nicename' => $this->user->user_nicename,
            'email' => $this->user->user_email,
            'show_email' => get_user_meta($this->user->ID, 'show_email', true),
            'referrer' => $this->getReferrer(), // ✅ Added here
            'display_name' => $this->user->display_name,
            'first_name' => get_user_meta($this->user->ID, 'first_name', true),
            'last_name' => get_user_meta($this->user->ID, 'last_name', true),
            'dob' => get_user_meta($this->user->ID, 'dob', true),
            'show_dob' => get_user_meta($this->user->ID, 'show_dob', true),
            'phone' => get_user_meta($this->user->ID, 'phone', true),
            'show_phone' => get_user_meta($this->user->ID, 'show_phone', true),
            'designation' => get_user_meta($this->user->ID, 'designation', true),
            'about_me' => get_user_meta($this->user->ID, 'about_me', true),
            'about_me_short' => get_user_meta($this->user->ID, 'about_me_short', true),
            'digital_card_about' => get_user_meta($this->user->ID, 'digital_card_about', true),
            'location' => get_user_meta($this->user->ID, 'place_address', true),
            'show_full_address' => get_user_meta($this->user->ID, 'show_full_address', true),
            'latitude' => get_user_meta($this->user->ID, 'latitude', true),
            'longitude' => get_user_meta($this->user->ID, 'longitude', true),
            'place_display_name' => get_user_meta($this->user->ID, 'place_display_name', true),
            'user_categories' => $user_categories,
            'user_category_names' => $user_category_names,
            'user_categories_with_priority' => $user_categories_with_priority,
            'bio' => get_user_meta($this->user->ID, 'description', true),
            'profile_photo' => get_user_meta($this->user->ID, 'profile_photo', true),
            'cover_photo' => get_user_meta($this->user->ID, 'profile_cover_photo', true),
            'roles' => $this->user->roles,
            'profile_url' => $this->getProfileUrl(),
            'social_links' => $social_links, // ✅ NEW: array of platform, label, url
            'referred_users' => $this->getReferredUsers(), // ✅ Added referred users
            'referred_users_count' => count($this->getReferredUsers()), // Count
            'referred_users_html' => $this->render_referred_users($this->getReferredUsers(), $this->user->user_login, 3), // Rendered HTML            

            // NEW SECTION
            'is_sales_person' => (bool)get_user_meta($this->user->ID, 'is_sales_person', true),
            'sales_agreement' => $this->getSalesAgreementInfo(),
            'keywords' => $this->getUserKeywords(),
            'hashtags' => $this->getUserHashtags(),

            // ✅ Shareable public profile URL
            'shareable_link' => home_url('/u/' . ($this->user->user_nicename ?: $this->user->user_login) . '/'),
            'shareable_nice_link' => home_url('/u/' . ($this->user->user_nicename ?: $this->user->user_login) . '/'),
            'mm_spg_avatar' => get_user_meta($this->user->ID, 'mm_spg_avatar', true),
            'mm_spg_step' => get_user_meta($this->user->ID, 'mm_spg_step', true),
            'mm_spg_status' => get_user_meta($this->user->ID, 'mm_spg_status', true),

            // Social Tokens (for internal use, not public)            
            'esp_state_google' => get_user_meta($this->user->ID, 'esp_state_google', true),
            'esp_state_facebook' => get_user_meta($this->user->ID, 'esp_state_facebook', true),
            'esp_state_linkedin' => get_user_meta($this->user->ID, 'esp_state_linkedin', true),
            'esp_state_instagram' => get_user_meta($this->user->ID, 'esp_state_instagram', true),
            'paypal_email' => get_user_meta($this->user->ID, 'paypal_email', true),

            'zoom' => [
                'connected' => !empty($zoom_access_token_encrypted),
                'user_id' => $zoom_user_id,
                'display_name' => $zoom_user_name,
                'scopes' => $zoom_scopes,
                'connected_at' => $zoom_connected_at,
                'token_expires_at' => $zoom_token_expires_at,
            ],

        ];
    }

    // Get the profile URL for the user
    // This is the URL to their profile page based on their username
    public function getProfileUrl()
    {
        if (!$this->user) return '';
        return home_url('/u/' . ($this->user->user_nicename ?: $this->user->user_login));
    }

    // Get the referrer for the user
    // This is the user who referred them, stored in user meta
    public function getReferrer()
    {
        if (!$this->user) return null;
        return get_user_meta($this->user->ID, 'referrer', true);
    }



    public function getReferredUsers()
    {
        if (!$this->user) return [];

        $partners = get_user_meta($this->user->ID, 'referral_partners', true);

        if (!is_array($partners)) return [];

        $users = [];

        foreach ($partners as $id) {
            $u = get_user_by('id', $id);
            if ($u) $users[] = $u;
        }

        usort($users, function ($a, $b) {
            return strcasecmp($a->display_name, $b->display_name);
        });

        return $users;
    }

    // OLD NON MUTUAL PARTNERSHIP
    public static function getReferredUsersBy($referrer_user)
    {
        if (!$referrer_user instanceof WP_User) {
            $referrer_user = get_user_by('id', (int)$referrer_user);
        }

        if (!$referrer_user) {
            return [];
        }

        $referrer_id    = $referrer_user->ID;
        $referrer_login = $referrer_user->user_login;

        /*
    |--------------------------------------------------------------------------
    | ⭐ NEW: FETCH reverse referral list stored in user_meta
    |--------------------------------------------------------------------------
    */
        $reverse_list = get_user_meta($referrer_id, 'referral_partners', true);

        if (!is_array($reverse_list)) {
            $reverse_list = [];
        }

        /*
    |--------------------------------------------------------------------------
    | ⭐ Convert each ID into a WP_User object
    |--------------------------------------------------------------------------
    */
        $users = [];
        foreach ($reverse_list as $id) {
            $u = get_user_by('id', $id);
            if ($u) {
                $users[] = $u;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | ⭐ OPTIONAL FALLBACK:
    | Still include users who stored the "referrer" meta (old system)
    |--------------------------------------------------------------------------
    */
        $args = [
            'meta_query' => [
                [
                    'key'     => 'referrer',
                    'value'   => [$referrer_id, $referrer_login],
                    'compare' => 'IN'
                ]
            ],
            'orderby' => 'registered',
            'order'   => 'DESC',
            'number'  => 7,
        ];

        $legacy_users = get_users($args);

        // Merge old + reverse results
        foreach ($legacy_users as $lu) {
            if (!in_array($lu->ID, $reverse_list)) {
                $users[] = $lu;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | Now process users with your existing enrichment logic
    |--------------------------------------------------------------------------
    */

        $enriched_users = [];

        foreach ($users as $user) {
            $user_id = $user->ID;

            $user_categories = get_user_meta($user_id, 'user_categories', true) ?: [];

            $user_categories_priority = get_user_meta(
                $user_id,
                'user_categories_priority',
                true
            );
            $user_categories_priority = is_array($user_categories_priority)
                ? $user_categories_priority
                : [];

            $user_category_names = [];
            if (!empty($user_categories) && is_array($user_categories)) {
                foreach ($user_categories as $cat_id) {
                    if (is_numeric($cat_id)) {
                        $term = get_term((int)$cat_id, 'category');
                        if ($term && !is_wp_error($term)) {
                            $user_category_names[] = $term->name;
                        }
                    }
                }
            }

            $zoom_access_token  = get_user_meta($user_id, 'zoom_access_token', true);
            $zoom_refresh_token = get_user_meta($user_id, 'zoom_refresh_token', true);

            $user_category_names = [];
            $user_categories_with_priority = [];

            if (!empty($user_categories) && is_array($user_categories)) {
                foreach ($user_categories as $cat_id) {
                    if (!is_numeric($cat_id)) continue;

                    $term = get_term((int) $cat_id, 'category');
                    if (!$term || is_wp_error($term)) continue;

                    // legacy
                    $user_category_names[] = $term->name;

                    // NEW
                    $user_categories_with_priority[] = [
                        'term_id'  => (int) $cat_id,
                        'name'     => $term->name,
                        'slug'     => $term->slug,
                        'priority' => $user_categories_priority[$cat_id] ?? null,
                    ];
                }
            }


            $enriched_users[] = [
                'id' => $user_id,
                'username' => $user->user_login,
                'user_nicename' => $user->user_nicename,
                'email' => $user->user_email,
                'referrer' => get_user_meta($user_id, 'referrer', true),
                'display_name' => $user->display_name,
                'first_name' => get_user_meta($user_id, 'first_name', true),
                'last_name' => get_user_meta($user_id, 'last_name', true),
                'dob' => get_user_meta($user_id, 'dob', true),
                'phone' => get_user_meta($user_id, 'phone', true),
                'about_me' => get_user_meta($user_id, 'about_me', true),
                'about_me_short' => get_user_meta($user_id, 'about_me_short', true),
                'location' => get_user_meta($user_id, 'place_address', true),
                'latitude' => get_user_meta($user_id, 'latitude', true),
                'longitude' => get_user_meta($user_id, 'longitude', true),
                'place_display_name' => get_user_meta($user_id, 'place_display_name', true),
                // legacy (unchanged)
                'user_categories' => $user_categories,
                'user_category_names' => $user_category_names,

                // ✅ NEW (priority-aware)
                'user_categories_with_priority' => $user_categories_with_priority,                
                'bio' => get_user_meta($user_id, 'description', true),
                'profile_photo' => get_user_meta($user_id, 'profile_photo', true),
                'cover_photo' => get_user_meta($user_id, 'profile_cover_photo', true),
                'roles' => $user->roles,
                'social_links' => get_user_meta($user_id, 'social_links', true),
                'zoom_access_token' => $zoom_access_token,
                'zoom_refresh_token' => $zoom_refresh_token,
                'zoom_connected' => !empty($zoom_access_token),
            ];
        }

        return $enriched_users;
    }


    function render_referred_users($referredUsers, $user_login, $maxVisible = 3)
    {
        $referralCount = count($referredUsers);
        $count = 0;
        ob_start();

        echo '<div class="d-flex justify-content-lg-start align-items-start justify-content-center mt-1 overflow-hidden">';

        foreach ($referredUsers as $user) {
            if ($count >= $maxVisible) break;

            $profilePhoto = get_user_meta($user->ID, 'profile_photo', true);
            if (!$profilePhoto) {
                $profilePhoto = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->user_email))) . '?s=64&d=mm';
            }
            $public_slug = $user->user_nicename ?: $user->user_login;
            $userProfileUrl = site_url('/u/' . $public_slug);

            echo '<a href="' . esc_url($userProfileUrl) . '" class="d-inline-block">';
            echo '<img src="' . esc_url($profilePhoto) . '" alt="' . esc_attr($user->display_name) . '" class="profile-img ' . ($count === 0 ? '' : 'profile-img-stacked') . '" />';
            echo '</a>';

            $count++;
        }

        if ($referralCount > $maxVisible) {
            $lastUser = $referredUsers[$maxVisible];
            $lastPhoto = get_user_meta($lastUser->ID, 'profile_photo', true);
            if (!$lastPhoto) {
                $lastPhoto = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($lastUser->user_email))) . '?s=64&d=mm';
            }
            $owner = get_user_by('login', $user_login);
            $owner_slug = $owner ? ($owner->user_nicename ?: $owner->user_login) : $user_login;
            $referralsLink = site_url('/u/' . $owner_slug . '/referrals');

            echo '<div><div class="position-relative">';
            echo '<a href="' . esc_url($referralsLink) . '" class="d-inline-block position-relative" title="See all referral partners">';
            echo '<img src="' . esc_url($lastPhoto) . '" alt="See all referral partners" class="profile-img profile-img-stacked" />';
            echo '<span class="top-0 bottom-0 position-absolute d-flex align-items-center end-0 start-0">';
            echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="20" fill="var(--white-color)" viewBox="0 0 16 16">';
            echo '<path d="M3 9.5A1.5 1.5 0 1 0 3 6.5a1.5 1.5 0 0 0 0 3zm5 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm5 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>';
            echo '</svg></span></a></div></div>';
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Summary of getSalesAgreementInfo
     * @return array{effective_date: mixed, is_submitted: bool, printed_name: mixed, signature: mixed, signature_date: mixed|null}
     */
    public function getSalesAgreementInfo()
    {
        if (!$this->user) return null;

        $user_id = $this->user->ID;

        $effective_date = get_user_meta($user_id, 'agreement_effective_date', true);
        $signature_date = get_user_meta($user_id, 'agreement_signature_date', true);
        $printed_name   = get_user_meta($user_id, 'agreement_printed_name', true);
        $signature      = get_user_meta($user_id, 'agreement_signature', true);

        $is_submitted = !empty($effective_date) && !empty($signature_date);

        // 🔥 Automatically mark user as sales person
        if ($is_submitted) {
            update_user_meta($user_id, 'is_sales_person', 1);
        }

        return [
            'effective_date' => $effective_date,
            'signature_date' => $signature_date,
            'printed_name'   => $printed_name,
            'signature'      => $signature,
            'is_submitted'   => $is_submitted
        ];
    }


    private function getUserKeywords()
    {
        $raw = get_user_meta($this->user->ID, 'user_keywords', true);

        if (empty($raw)) {
            return [
                'raw' => '',
                'list' => []
            ];
        }

        $list = array_filter(array_map('trim', explode(',', $raw)));

        return [
            'raw' => $raw,
            'list' => $list
        ];
    }

    private function getUserHashtags()
    {
        $raw = get_user_meta($this->user->ID, 'user_hashtags', true);

        if (empty($raw)) {
            return [
                'raw' => '',
                'list' => []
            ];
        }

        $list = array_filter(array_map('trim', explode(',', $raw)));

        return [
            'raw' => $raw,
            'list' => $list
        ];
    }




    /**
     * Check if a post can be viewed by the current user based on privacy settings
     * 
     * @param int $post_id
     * @param int|null $current_user_id (defaults to wp_get_current_user()->ID)
     * @return bool
     */
    public static function canViewPost($post_id, $current_user_id = null)
    {
        if (!$current_user_id) {
            $current_user_id = get_current_user_id();
        }

        $post_author_id = get_post_field('post_author', $post_id);
        $privacy = get_post_meta($post_id, '_post_privacy', true) ?: 'only_me';

        // Post author can always view their own post
        if ($current_user_id === (int)$post_author_id) {
            return true;
        }

        // Public posts can be viewed by anyone
        if ($privacy === 'public') {
            return true;
        }

        // Private posts can only be viewed by the owner
        if ($privacy === 'only_me') {
            return false;
        }

        // Referral partners can view posts shared with referral partners
        if ($privacy === 'referral_partners') {
            if (!$current_user_id) {
                return false; // Only logged-in users can view partner posts
            }

            // Check if current user is a referral partner of post author
            $author_profile = new self($post_author_id);
            $referral_partners = $author_profile->getReferredUsers();

            foreach ($referral_partners as $partner) {
                if ($partner->ID === (int)$current_user_id) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    public function toJSON()
    {
        return wp_json_encode($this->getProfile());
    }
}
