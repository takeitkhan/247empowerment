<?php
/* Template Name: Guide Complete */
/**
 * 24/7 Empowerment — Guide Complete Page
 * Upload to: /wp-content/themes/247empowerment/template-custom/guide-complete.php
 */


if ( ! defined( 'ABSPATH' ) ) {
  $path = dirname( __FILE__ );
  while ( $path !== '/' && ! file_exists( $path . '/wp-load.php' ) ) {
    $path = dirname( $path );
  }
  require_once $path . '/wp-load.php';
}

// Block non-logged-in users from accessing guide page
if ( ! is_user_logged_in() ) {
  wp_redirect( home_url( '/signin/' ) );
  exit;
}

/* ─── Handle AJAX saves (POST to self) ─────────────────────── */
if ( isset( $_POST['guide_action'] ) ) {
    $nonce_ok = isset( $_POST['guide_nonce'] ) &&
                wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['guide_nonce'] ) ), 'guide_save_action' );
    if ( ! $nonce_ok ) {
        wp_send_json_error( [ 'message' => 'Security check failed.' ] );
    }
    $uid = get_current_user_id();
    if ( ! $uid ) {
        wp_send_json_error( [ 'message' => 'Please log in first.' ] );
    }

    $act = sanitize_text_field( $_POST['guide_action'] );

    /* ── interests ── */
    if ( $act === 'save_interests' ) {
        $cats  = isset( $_POST['user_categories'] )
               ? array_map( 'sanitize_text_field', (array) $_POST['user_categories'] )
               : [];
        $prios = [];
        foreach ( $_POST as $k => $val ) {
            if ( strpos( $k, 'user_categories_priority' ) === 0 ) {
                $prios[ sanitize_key($k) ] = sanitize_text_field($val);
            }
        }

      // Also store in modify-profile format: user_categories_priority[term_id] = numeric priority.
      $profile_prios = [];
      foreach ( $cats as $term_id ) {
        $idx_key = 'user_categories_priority_' . sanitize_key( (string) $term_id );
        $raw     = $prios[ $idx_key ] ?? '';
        if ( preg_match( '/(\d+)/', (string) $raw, $m ) ) {
          $profile_prios[ (string) $term_id ] = (string) intval( $m[1] );
        }
      }

        update_user_meta( $uid, 'guide_interests',  $cats );
        update_user_meta( $uid, 'guide_priorities', $prios );
      update_user_meta( $uid, 'user_categories_priority', $profile_prios );
      update_user_meta( $uid, 'user_categories', $cats );
      update_user_meta( $uid, 'mm_spg_interest_completed', 1 );
        // Award points via the gamification system (wallet reads this)
        if ( function_exists( 'mm_award_points_and_notify' ) ) {
            mm_award_points_and_notify( $uid, 'interest_completed' );
        }
        wp_send_json_success( [ 'message' => 'Saved.' ] );
    }
    /* ── biz card ── */
    if ( $act === 'save_bizcard' ) {
      $title    = sanitize_text_field( $_POST['title'] ?? '' );
      $about    = sanitize_textarea_field( $_POST['about'] ?? '' );
      $address  = sanitize_text_field( $_POST['address'] ?? '' );
      $keywords = sanitize_text_field( $_POST['keywords'] ?? '' );
      $hashtags = sanitize_text_field( $_POST['hashtags'] ?? '' );

      // Normalize hashtags for shared profile pages that expect comma-separated values.
      $hashtags_arr = array_filter( array_map( 'trim', preg_split( '/[\s,]+/', $hashtags ) ) );
      $hashtags_arr = array_map(
        static function( $tag ) {
          return strpos( $tag, '#' ) === 0 ? $tag : '#' . $tag;
        },
        $hashtags_arr
      );
      $hashtags_csv = implode( ', ', $hashtags_arr );

      // Original guide storage.
      update_user_meta( $uid, 'guide_title',    $title );
      update_user_meta( $uid, 'guide_about',    $about );
      update_user_meta( $uid, 'guide_address',  $address );
      update_user_meta( $uid, 'guide_keywords', $keywords );
      update_user_meta( $uid, 'guide_hashtags', $hashtags_csv );

      // Modify Profile compatibility.
      update_user_meta( $uid, 'about_me_short', $title ?: $about );
      update_user_meta( $uid, 'about_me',       $about );
      update_user_meta( $uid, 'place_display_name', $address );
      update_user_meta( $uid, 'designation', $title );
      update_user_meta( $uid, 'digital_card_about', $about );
      update_user_meta( $uid, 'user_keywords', $keywords );
      update_user_meta( $uid, 'user_hashtags', $hashtags_csv );
      update_user_meta( $uid, 'mm_spg_business_card_completed', 1 );
        // Award points via the gamification system (wallet reads this)
        if ( function_exists( 'mm_award_points_and_notify' ) ) {
            mm_award_points_and_notify( $uid, 'business_card_completed' );
        }
        wp_send_json_success( [ 'message' => 'Saved.' ] );
    }

    /* ── social links ── */
    // Uses the same $_POST['links'] array format as modify-links.php — no JSON involved.
    if ( $act === 'save_social' ) {
        $sanitized_links = [];
        foreach ( $_POST['links'] ?? [] as $link ) {
            if ( ! empty( $link['url'] ) ) {
                $sanitized_links[] = [
                    'platform' => sanitize_text_field( $link['platform'] ?? '' ),
                    'label'    => sanitize_text_field( $link['label']    ?? '' ),
                    'url'      => esc_url_raw( $link['url'] ),
                ];
            }
        }
        // Save to the same key modify-links.php reads/writes.
        update_user_meta( $uid, 'custom_social_links', $sanitized_links );
        update_user_meta( $uid, 'mm_spg_social_links_completed', 1 );
        // Award points via the gamification system (wallet reads this)
        if ( function_exists( 'mm_award_points_and_notify' ) ) {
            mm_award_points_and_notify( $uid, 'social_links_completed' );
        }
        wp_send_json_success( [ 'message' => 'Saved.' ] );
    }

    /* ── mark onboarding complete ── */
    if ( $act === 'complete_onboarding' ) {
        update_user_meta( $uid, 'mm_spg_onboarding_completed', 1 );
        if ( function_exists( 'mm_award_points_and_notify' ) ) {
            mm_award_points_and_notify( $uid, 'onboarding_completed' );
        }
        wp_send_json_success( [ 'message' => 'Done.' ] );
    }

    /* ── award points — writes directly to mm_spg_points + history in user meta ── */
    if ( $act === 'award_points' ) {
        $reason = sanitize_text_field( $_POST['reason'] ?? '' );
        $points = (int) ( $_POST['points'] ?? 0 );

        if ( $points <= 0 || $points > 500 ) {
            wp_send_json_error( [ 'message' => 'Invalid points value.' ] );
        }
        if ( empty( $reason ) ) {
            wp_send_json_error( [ 'message' => 'Reason required.' ] );
        }

        // Dedup: use a SEPARATE key prefix (mm_spg_pts_) so this never collides
        // with mm_spg_*_completed flags set by the save handlers BEFORE award runs.
        $dedup_key = 'mm_spg_pts_' . sanitize_key( $reason );
        $previous  = (int) get_user_meta( $uid, 'mm_spg_points', true );

        if ( get_user_meta( $uid, $dedup_key, true ) ) {
            wp_send_json_success( [
                'already_awarded' => true,
                'points_added'    => 0,
                'previous_total'  => $previous,
                'total_points'    => $previous,
            ] );
        }

        // Write new total to user meta
        $new_total = $previous + $points;
        update_user_meta( $uid, 'mm_spg_points', $new_total );

        // Append to points history (same structure the mm-spg plugin uses)
        $history = get_user_meta( $uid, 'mm_spg_points_history', true );
        if ( ! is_array( $history ) ) { $history = []; }
        $history[] = [
            'points'    => $points,
            'reason'    => $reason,
            'date'      => current_time( 'mysql' ),
            'timestamp' => time(),
        ];
        update_user_meta( $uid, 'mm_spg_points_history', $history );

        // Set dedup flag so this step can never be double-awarded
        update_user_meta( $uid, $dedup_key, 1 );

        wp_send_json_success( [
            'already_awarded' => false,
            'points_added'    => $points,
            'previous_total'  => $previous,
            'total_points'    => $new_total,
        ] );
    }

    /* ── get interests list ── */
    if ( $act === 'get_interests' ) {
        // Try multiple common taxonomy slugs used by empowerment plugins
        $possible = [ 'user_category', 'user-category', 'interest', 'interests',
                      'user_categories', 'category', 'empowerment_category' ];
        $items = [];
        foreach ( $possible as $tax ) {
            $terms = get_terms([ 'taxonomy' => $tax, 'hide_empty' => false ]);
            if ( ! is_wp_error($terms) && ! empty($terms) ) {
                $saved_cats  = (array) get_user_meta( $uid, 'guide_interests',  true );
                $saved_prios = (array) get_user_meta( $uid, 'guide_priorities', true );
            $profile_prios = get_user_meta( $uid, 'user_categories_priority', true );
            $profile_prios = is_array( $profile_prios ) ? $profile_prios : [];

            if ( empty( $saved_cats ) && ! empty( $profile_prios ) ) {
              $saved_cats = array_keys( $profile_prios );
            }

                foreach ( $terms as $term ) {
              $term_id_str = (string) $term->term_id;
              $guide_prio  = $saved_prios[ 'user_categories_priority_' . $term->term_id ] ?? '';
              $profile_prio = $profile_prios[ $term_id_str ] ?? '';
              $priority = $guide_prio;
              if ( $priority === '' && $profile_prio !== '' ) {
                $priority = (string) intval( $profile_prio );
              }

                    $items[] = [
                        'value'    => $term->term_id,
                        'label'    => $term->name,
                'checked'  => in_array( $term_id_str, array_map( 'strval', $saved_cats ), true ),
                'priority' => $priority,
                    ];
                }
                break;
            }
        }
        // Fallback: try existing plugin AJAX handler
        if ( empty($items) ) {
            wp_send_json_success( [ 'items' => [], 'use_plugin' => true ] );
        }
        wp_send_json_success( [ 'items' => $items ] );
    }

    wp_send_json_error( [ 'message' => 'Unknown action.' ] );
    exit;
}

/* ─── Page render ───────────────────────────────────────────── */
$nonce       = wp_create_nonce( 'guide_save_action' );
$self_url    = home_url( $_SERVER['REQUEST_URI'] );
$profile_url = 'https://personalempowermentteams.me/modify-profile/';
$skip_url    = 'https://personalempowermentteams.me/';
$uid         = get_current_user_id();

// ── Point 4: Guide disappears once all data is properly filled ──
// Never redirect on save POSTs — those are handled above and already exited.
if ( empty( $_POST['guide_action'] ) ) {
    $_done_cats  = get_user_meta( $uid, 'user_categories_priority', true );
    $_done_about = get_user_meta( $uid, 'guide_about', true )
                 ?: get_user_meta( $uid, 'about_me', true )
                 ?: get_user_meta( $uid, 'digital_card_about', true );
    $_done_title = get_user_meta( $uid, 'guide_title', true )
                 ?: get_user_meta( $uid, 'designation', true );
    if ( is_array( $_done_cats ) && count( $_done_cats ) > 0
         && ! empty( $_done_about )
         && ! empty( $_done_title ) ) {
        error_log('[GUIDE_COMPLETE] Redirecting to modify-profile for user: ' . $uid);
        wp_redirect( home_url( '/modify-profile/' ) );
        exit;
    }
}


// Pre-fill saved data
$sv = [
  // Keep guide-specific keys first, then fall back to modify-profile keys.
  'title'    => (string) ( get_user_meta( $uid, 'guide_title', true ) ?: get_user_meta( $uid, 'designation', true ) ?: get_user_meta( $uid, 'about_me_short', true ) ),
  'about'    => (string) ( get_user_meta( $uid, 'guide_about', true ) ?: get_user_meta( $uid, 'digital_card_about', true ) ?: get_user_meta( $uid, 'about_me', true ) ?: get_user_meta( $uid, 'about_me_short', true ) ),
  'address'  => (string) ( get_user_meta( $uid, 'guide_address', true ) ?: get_user_meta( $uid, 'place_display_name', true ) ),
    'keywords' => (string) ( get_user_meta( $uid, 'guide_keywords', true ) ?: get_user_meta( $uid, 'user_keywords', true ) ),
    'hashtags' => (string) ( get_user_meta( $uid, 'guide_hashtags', true ) ?: get_user_meta( $uid, 'user_hashtags', true ) ),
];

// Check if user has completed interests/priorities (step 1)
$user_interests = get_user_meta($uid, 'user_categories_priority', true);
$has_interests = is_array($user_interests) && count($user_interests) > 0;

// custom_social_links is the canonical key (modify-links.php writes here)
$sv_social = get_user_meta( $uid, 'custom_social_links', true );
if ( ! is_array( $sv_social ) || empty( $sv_social ) ) {
    $sv_social = get_user_meta( $uid, 'guide_social_links', true );
}
if ( ! is_array( $sv_social ) || empty( $sv_social ) ) {
    $sv_social = get_user_meta( $uid, 'social_links', true );
}
if ( ! is_array( $sv_social ) || empty( $sv_social ) ) {
    $sv_social = get_user_meta( $uid, 'user_social_links', true );
}
if ( ! is_array( $sv_social ) ) {
    $sv_social = [];
}

// Guide images
$BG  = 'https://personalempowermentteams.me/wp-content/uploads/2026/03/unnamed.jpg';
$BG_PERSON = 'https://personalempowermentteams.me/wp-content/uploads/2026/03/BG2.jpg';
$SEATED_SMALL = 'https://personalempowermentteams.me/wp-content/uploads/2026/02/Joseph.png';
$guides = [
    'joseph' => [
        'name'  => 'Joseph',
        'real'  => 'https://personalempowermentteams.me/wp-content/uploads/2026/02/Joseph.png',
    'stand' => 'https://personalempowermentteams.me/wp-content/uploads/2026/03/Man.png',
        'intro' => "Welcome! I’m Joseph, your Empowerment Guide, here to help you make the most of the 24/7 Empowerment Platform explore your profile, tools, goals, and opportunities anytime. As part of our Referral Appreciation Program, members may receive a one-time credit of up to $600 when someone they refer enrolls in an eligible paid program and remains beyond the refund period. This program is not MLM and does not apply to donations or fundraising.",
    ],
    'zoro' => [
        'name'  => 'Zoro',
        'real'  => 'https://personalempowermentteams.me/wp-content/uploads/2026/02/Zoro.png',
    'stand' => 'https://personalempowermentteams.me/wp-content/uploads/2026/03/Dog.png',
        'intro' => "Welcome! I’m Zoro, your Empowerment Guide, here to help you make the most of the 24/7 Empowerment Platform explore your profile, tools, goals, and opportunities anytime. As part of our Referral Appreciation Program, members may receive a one-time credit of up to $600 when someone they refer enrolls in an eligible paid program and remains beyond the refund period. This program is not MLM and does not apply to donations or fundraising.",
    ],
    'bella' => [
        'name'  => 'Bella',
        'real'  => 'https://personalempowermentteams.me/wp-content/uploads/2026/02/Bella2.png',
    'stand' => 'https://personalempowermentteams.me/wp-content/uploads/2026/03/Bella.png',
        'intro' => "Welcome! I’m Bella, your Empowerment Guide, here to help you make the most of the 24/7 Empowerment Platform explore your profile, tools, goals, and opportunities anytime. As part of our Referral Appreciation Program, members may receive a one-time credit of up to $600 when someone they refer enrolls in an eligible paid program and remains beyond the refund period. This program is not MLM and does not apply to donations or fundraising.",
    ],
];

// Also pass the plugin's ajaxurl/nonce for interests fallback
$plugin_nonce = wp_create_nonce('guide_form_nonce');
$ajax_url     = admin_url('admin-ajax.php');

// ── Plugin API nonce (mm_spg_api_nonce stored in user meta) ──────────────────
// The mm-spg plugin verifies nonces against mm_spg_api_nonce in user meta,
// NOT against wp_create_nonce(). Retrieve the stored one or generate a fresh one.
$_api_nonce_stored = get_user_meta( $uid, 'mm_spg_api_nonce', true );
$_api_nonce_time   = (int) get_user_meta( $uid, 'mm_spg_api_nonce_time', true );
if ( empty( $_api_nonce_stored ) || ( time() - $_api_nonce_time ) > 86400 ) {
    $_api_nonce_stored = wp_hash( $uid . time() . wp_rand(), 'nonce' );
    update_user_meta( $uid, 'mm_spg_api_nonce', $_api_nonce_stored );
    update_user_meta( $uid, 'mm_spg_api_nonce_time', time() );
}
$api_nonce = $_api_nonce_stored;
$api_base  = rtrim( home_url(), '/' );

// ── Current points + dedup flags ─────────────────────────────────────────────
// NOTE: PTS_FLAGS reads from mm_spg_pts_* (award tracking), NOT mm_spg_*_completed
// (step-done flags). This prevents the dedup check from firing too early because
// save_interests/save_bizcard/save_social set mm_spg_*_completed BEFORE award_points runs.
$current_pts = (int) get_user_meta( $uid, 'mm_spg_points', true );
$pts_flags   = [
    'interest_completed'      => (bool) get_user_meta( $uid, 'mm_spg_pts_interest_completed',      true ),
    'business_card_completed' => (bool) get_user_meta( $uid, 'mm_spg_pts_business_card_completed', true ),
    'social_links_completed'  => (bool) get_user_meta( $uid, 'mm_spg_pts_social_links_completed',  true ),
    'onboarding_completed'    => (bool) get_user_meta( $uid, 'mm_spg_pts_onboarding_completed',    true ),
];

// Platform options — identical list to modify-links.php
$platform_options = [
    'youtube' => 'YouTube', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn',
    'x' => 'X (Twitter)', 'instagram' => 'Instagram', 'google_business' => 'Google Business',
    'yelp' => 'Yelp', 'meetup' => 'Meetup', 'website' => 'Website', 'tiktok' => 'TikTok',
    'twitch' => 'Twitch', 'pinterest' => 'Pinterest', 'snapchat' => 'Snapchat',
    'whatsapp' => 'WhatsApp', 'zoom' => 'Zoom', 'discord' => 'Discord',
    'github' => 'GitHub', 'google' => 'Google', 'custom' => 'Custom Link',
    'other' => 'Other', 'email' => 'Email', 'phone' => 'Phone',
    'telegram' => 'Telegram', 'signal' => 'Signal', 'viber' => 'Viber',
    'sheet' => 'Sheet', 'slack' => 'Slack', 'reddit' => 'Reddit',
    'messenger' => 'Messenger', 'meet' => 'Meet', 'bluesky' => 'Bluesky',
    'skype' => 'Skype', 'calendar' => 'Calendar', 'default' => 'Default Link',
];
$platform_opts_js = [];
foreach ( $platform_options as $val => $lbl ) {
    $platform_opts_js[] = [ 'value' => $val, 'label' => $lbl ];
}

get_header(); // use theme header so WP styles/scripts load properly
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Guide – 24/7 Empowerment</title>
<style>
/* ════════════════════════════════════
   BASE
════════════════════════════════════ */
html, body { margin:0; padding:0; width:100%; overflow-x:hidden;
  font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }

/* Full-screen wrapper so theme header doesn't interfere */
#guide-root {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: #0a0500;
  overflow: hidden;
}

/* ════════════════════════════════════
   SHARED BG
════════════════════════════════════ */
.g-screen {
  display: none;
  position: absolute;
  inset: 0;
  background:
    linear-gradient(rgba(10,5,0,.35),rgba(10,5,0,.55)),
    url('<?php echo esc_url($BG); ?>') center/cover no-repeat;
}
.g-screen.active { display: flex; }
#s-intro, #s-wizard {
  background:
    linear-gradient(rgba(10,5,0,.35),rgba(10,5,0,.55)),
    url('<?php echo esc_url($BG_PERSON); ?>') center/cover no-repeat;
}

/* ════════════════════════════════════
   SCREEN 1 — SELECT GUIDE
════════════════════════════════════ */
#s-select {
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.sel-card {
  position: relative;
  z-index: 5;
  background: rgba(255,238,180,.95);
  border-radius: 20px;
  padding: 28px 24px 24px;
  max-width: 780px;
  width: 100%;
  box-shadow: 0 20px 60px rgba(0,0,0,.55);
}
.sel-card h2 {
  text-align: center;
  font-size: clamp(1.15rem,2.8vw,1.5rem);
  color: #3a2000;
  margin: 0 0 22px;
  font-weight: 700;
  line-height: 1.4;
}
.guides-grid {
  display: grid;
  grid-template-columns: repeat(3,1fr);
  gap: 14px;
}
.g-btn {
  background: none; border: 2.5px solid transparent;
  border-radius: 14px; cursor: pointer;
  padding: 8px; transition: all .2s;
  display: flex; flex-direction: column;
  align-items: center; gap: 8px;
}
.g-btn:hover {
  border-color: #c8a96e;
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(200,169,110,.4);
}
.g-btn img {
  width: 100%; aspect-ratio: 3/4;
  object-fit: cover; border-radius: 10px;
}
.g-btn span { font-size:1.05rem; font-weight:700; color:#3a2000; }

/* ════════════════════════════════════
   SCREEN 2 — INTRO
════════════════════════════════════ */
#s-intro { align-items: flex-end; justify-content: flex-end; }
.intro-img {
  position: absolute; right: 0; bottom: 0;
  height: 95vh; max-height: 820px;
  z-index: 3;
  object-fit: contain;
  filter: drop-shadow(0 10px 30px rgba(0,0,0,.5));
}
.scene-seated {
  position: absolute;
  left: 8vw;
  bottom: 0;
  height: min(56vh, 430px);
  z-index: 2;
  object-fit: contain;
  filter: drop-shadow(0 8px 22px rgba(0,0,0,.35));
  pointer-events: none;
}
.intro-bubble {
  position: absolute; top: 60px;
  right: clamp(220px, 25vw, 420px);
  max-width: 300px;
  z-index: 8;
  background: rgba(15,8,0,.9);
  color: #fff; border-radius: 14px;
  padding: 16px 18px;
  font-size: .88rem; line-height: 1.6;
  box-shadow: 0 8px 24px rgba(0,0,0,.5);
}
.intro-bubble::after {
  content:''; position:absolute;
  right:-10px; top:22px;
  border:10px solid transparent;
  border-left-color:rgba(15,8,0,.9);
  border-right:0;
}
.intro-next {
  position: absolute; bottom: 36px; right: 36px;
  z-index: 8;
  background: #c0392b; color:#fff; border:none;
  border-radius: 8px; padding: 11px 28px;
  font-size:.92rem; font-weight:700;
  cursor:pointer; font-family:inherit;
  transition: background .2s, transform .15s;
}
.intro-next:hover { background:#a93226; transform:translateY(-2px); }

/* ════════════════════════════════════
   SCREEN 3 — WIZARD
════════════════════════════════════ */
#s-wizard { align-items: center; justify-content: flex-end; }

/* Step speech bubble — floats above the card, beside the guide */
#wiz-bubble {
  position: absolute;
  top: 28px;
  right: clamp(80px, 10vw, 160px);
  max-width: clamp(200px, 24vw, 300px);
  z-index: 12;
  background: rgba(15,8,0,.88);
  color: #fff;
  border-radius: 14px;
  padding: 11px 15px;
  font-size: .82rem;
  line-height: 1.6;
  font-weight: 600;
  box-shadow: 0 6px 22px rgba(0,0,0,.55);
  pointer-events: none;
  opacity: 0;
  transform: translateY(-8px) scale(.95);
  transition: opacity .35s ease, transform .35s ease;
}
#wiz-bubble.vis {
  opacity: 1;
  transform: translateY(0) scale(1);
}
/* tail pointing down toward the guide character */
#wiz-bubble::after {
  content: '';
  position: absolute;
  bottom: -10px;
  right: 36px;
  border: 10px solid transparent;
  border-top-color: rgba(15,8,0,.88);
  border-bottom: 0;
}
/* gold accent bar on the left */
#wiz-bubble::before {
  content: '';
  position: absolute;
  left: 0; top: 12px; bottom: 12px;
  width: 3px;
  background: #c8a96e;
  border-radius: 3px;
}

.wiz-guide-img {
  position: absolute; right: 0; bottom: 0;
  height: 100vh; max-height: 860px;
  z-index: 3;
  object-fit: contain; pointer-events:none;
  filter: drop-shadow(0 8px 24px rgba(0,0,0,.4));
}

/* The card */
.wiz-card {
  position: relative; z-index:10;
  margin-right: clamp(320px, 34vw, 520px);
  width: clamp(250px, 28vw, 330px);
  background: rgba(255,238,165,.97);
  border-radius: 16px;
  padding: 18px 16px 14px;
  box-shadow: 0 14px 44px rgba(0,0,0,.45);
  max-height: 82vh;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: #c8a96e transparent;
}

/* step dots */
.s-dots { display:flex; justify-content:center; gap:7px; margin-bottom:12px; }
.s-dot  { width:7px; height:7px; border-radius:50%; background:rgba(90,62,27,.22); transition:background .25s; }
.s-dot.on { background:#5a3e1b; }

/* page visibility */
.wiz-pg { display:none; }
.wiz-pg.on { display:block; }

/* heading */
.wiz-h {
  font-size:1rem; font-weight:800; color:#5a3e1b;
  text-align:center; margin-bottom:8px; line-height:1.3;
}

/* ── Interests ── */
.int-scroll {
  max-height: 210px; overflow-y:auto;
  padding-right:2px;
  scrollbar-width:thin; scrollbar-color:#c8a96e transparent;
}
.int-item {
  display:flex; align-items:center; gap:8px;
  padding:6px 9px; margin-bottom:5px;
  background:rgba(255,255,255,.5);
  border-radius:8px; border:1px solid rgba(200,169,110,.4);
}
.int-item input[type="checkbox"] {
  width:14px; height:14px; cursor:pointer;
  accent-color:#c8a96e; flex-shrink:0;
}
.int-item label { flex:1; font-size:.86rem; font-weight:700; color:#3a2a0a; cursor:pointer; }
.int-item select {
  padding:3px 5px; border-radius:5px;
  border:1px solid #c8a96e; font-size:.7rem;
  background:#fff; color:#444; min-width:68px;
  transition:opacity .2s;
}
.int-item select:disabled {
  opacity:.38; cursor:not-allowed;
  background:#f0e8d8; color:#aaa; border-color:#ddd;
}
.int-note { font-size:.82rem; color:#5a3e1b; font-style:italic; margin-bottom:7px; font-weight:600; }

/* ── Form fields ── */
.fld { margin-bottom:10px; }
.fld-lbl {
  display:block; font-size:.82rem; font-weight:900;
  color:#3a2a0a; text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px;
}
.fld-hint {
  display:block; font-size:.75rem; color:#3a2814;
  background:rgba(200,169,110,.28);
  border-left:3px solid #c8a96e;
  border-radius:0 5px 5px 0;
  padding:5px 9px; margin-bottom:5px; line-height:1.5; font-weight:600;
}
.fld input, .fld textarea {
  width:100%; padding:6px 8px;
  border:1.5px solid #d4b87a; border-radius:7px;
  font-size:.88rem; background:rgba(255,255,255,.92);
  color:#1a0800; font-family:inherit;
  transition:border-color .2s, box-shadow .2s;
  box-sizing:border-box;
}
.fld input:focus, .fld textarea:focus {
  outline:none; border-color:#8a5c2a;
  box-shadow:0 0 0 2px rgba(200,169,110,.3);
}
.fld input.ferr, .fld textarea.ferr {
  border-color:#d94f4f !important;
  box-shadow:0 0 0 2px rgba(217,79,79,.2) !important;
}
.fld textarea { resize:vertical; }
.fld-char { font-size:.6rem; color:#999; text-align:right; margin-top:2px; }
.fld-err  { font-size:.63rem; color:#d94f4f; display:none; margin-top:2px; font-weight:600; }

/* ── Tag-cloud chip input ── */
.tag-cloud-wrap {
  display:flex; flex-wrap:wrap; align-items:center; gap:5px;
  border:1px solid #5a3e1b; border-radius:7px;
  padding:6px 8px; background:#fff8ef; min-height:38px;
  cursor:text;
}
.tag-cloud-wrap:focus-within { border-color:#a07840; box-shadow:0 0 0 2px rgba(160,120,64,.2); }
.tag-chips { display:contents; }
.tag-chip {
  display:inline-flex; align-items:center; gap:4px;
  background:linear-gradient(135deg,#5a3e1b,#a07840);
  color:#fff; border-radius:20px; padding:3px 10px 3px 11px;
  font-size:.72rem; font-weight:700; white-space:nowrap;
}
.tag-chip .tc-rm {
  cursor:pointer; font-size:.8rem; line-height:1;
  opacity:.8; padding:0 2px;
}
.tag-chip .tc-rm:hover { opacity:1; }
.tag-input {
  border:none; outline:none; background:transparent;
  font-size:.78rem; min-width:100px; flex:1; color:#1a0e05;
}

/* ── Welcome overlay ── */
#welcome-overlay {
  display:none; position:fixed; inset:0;
  background:rgba(18,8,2,.93);
  z-index:2147483640;
  flex-direction:column; align-items:center; justify-content:center;
  gap:14px; overflow-y:auto;
  padding:24px 20px; box-sizing:border-box;
  animation:wov-in .5s ease;
}
#welcome-overlay.show { display:flex; }
@keyframes wov-in { from{opacity:0;transform:scale(.96)} to{opacity:1;transform:scale(1)} }
.wov-img { width:110px; height:auto; border-radius:50%; border:3px solid #a07840; flex-shrink:0; }
.wov-title {
  font-size:clamp(1.3rem,4vw,2rem); font-weight:900;
  color:#f0c060; text-align:center;
  text-shadow:0 2px 12px rgba(0,0,0,.6); margin:0;
}
.wov-text {
  font-size:.88rem; color:#f5deb3; text-align:center;
  max-width:480px; line-height:1.65; margin:0;
}
.wov-tasks-title {
  font-size:.78rem; font-weight:800; color:#f0c060;
  text-align:center; margin:6px 0 2px;
}
.wov-tasks-list {
  list-style:none; padding:0; margin:0;
  display:grid; grid-template-columns:1fr 1fr; gap:5px 12px;
  width:100%; max-width:480px;
}
.wov-tasks-list li {
  font-size:.74rem; color:#ffe7a0; font-weight:700;
  padding:4px 10px; background:rgba(200,169,110,.12);
  border:1px solid rgba(200,169,110,.25); border-radius:8px;
}
#wov-proceed-btn {
  background:linear-gradient(135deg,#a07840,#f0c060);
  color:#1a0800; border:none; border-radius:12px;
  padding:12px 36px; font-size:1rem; font-weight:900;
  cursor:pointer; font-family:inherit; margin-top:6px;
  transition:opacity .2s, transform .15s;
}
#wov-proceed-btn:hover { opacity:.88; transform:translateY(-1px); }
#wov-proceed-btn:disabled { opacity:.6; cursor:not-allowed; transform:none; }

/* ── Social links ── */
.soc-cols {
  display:grid; grid-template-columns:1.2fr 1fr 1.6fr 26px;
  gap:4px; margin-bottom:3px;
}
.soc-cols span { font-size:.58rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#8a6a30; }
.soc-row {
  display:grid; grid-template-columns:1.2fr 1fr 1.6fr 26px;
  gap:4px; margin-bottom:5px; align-items:center;
}
.soc-row input, .soc-row select {
  padding:4px 4px; border:1.5px solid #d4b87a;
  border-radius:6px; font-size:.68rem;
  background:rgba(255,255,255,.92); color:#1a0800;
  width:100%; box-sizing:border-box; font-family:inherit;
  transition:border-color .2s;
}
.soc-row input:focus, .soc-row select:focus { outline:none; border-color:#8a5c2a; box-shadow:0 0 0 2px rgba(200,169,110,.3); }
.soc-row input.serr, .soc-row select.serr { border-color:#d94f4f !important; }
.soc-rm {
  background:#d94f4f; color:#fff; border:none;
  border-radius:5px; width:24px; height:24px;
  font-size:.75rem; cursor:pointer; display:flex;
  align-items:center; justify-content:center;
}
.soc-rm:hover { background:#b83333; }
.soc-add {
  display:inline-flex; align-items:center; gap:4px;
  background:linear-gradient(135deg,#7a5a2b,#c8a96e);
  color:#fff; border:none; border-radius:7px;
  padding:6px 12px; cursor:pointer;
  font-size:.74rem; font-weight:700; margin-top:3px;
  font-family:inherit; transition:opacity .2s;
}
.soc-add:hover { opacity:.85; }

/* ── Congrats ── */
.congrats { text-align:center; padding:8px 2px; }
.cong-title {
  font-size: 1.45rem;
  font-weight: 900;
  color: #4a2f0f;
  margin-bottom: 10px;
  letter-spacing: .01em;
  text-shadow: 0 1px 0 rgba(255,255,255,.4);
}
.cong-text  {
  font-size: .88rem;
  color: #2f2414;
  line-height: 1.72;
  font-weight: 700;
}
.cong-tasks-title {
  font-size: .76rem; font-weight: 800; color: #4a2f0f;
  margin: 10px 0 6px; text-align: left;
}
.cong-tasks-list {
  list-style: none; padding: 0; margin: 0;
  columns: 2; column-gap: 8px; text-align: left;
}
.cong-tasks-list li {
  font-size: .7rem; color: #3a2010; font-weight: 700;
  padding: 3px 8px; margin-bottom: 4px; break-inside: avoid;
  background: rgba(200,169,110,.14);
  border-radius: 6px; border: 1px solid rgba(160,120,64,.22);
}

/* Full-page celebration overlay for each Next step */
#step-celebrate {
  position: fixed;
  inset: 0;
  z-index: 20000;
  pointer-events: none;
  opacity: 0;
  overflow: hidden;
}
#step-celebrate.show { opacity: 1; }
#step-celebrate .burst {
  position: absolute;
  top: 50%;
  width: 58vmax;
  height: 58vmax;
  border-radius: 50%;
  opacity: 0;
}
#step-celebrate .burst.left {
  left: -14vmax;
  background: radial-gradient(circle at center,
    rgba(255,212,96,.45) 0%,
    rgba(255,123,84,.33) 25%,
    rgba(255,255,255,0) 68%);
  transform: translate(-12vmax,-50%) scale(.55);
}
#step-celebrate .burst.right {
  right: -14vmax;
  background: radial-gradient(circle at center,
    rgba(115,221,255,.4) 0%,
    rgba(255,91,146,.3) 24%,
    rgba(255,255,255,0) 68%);
  transform: translate(12vmax,-50%) scale(.55);
}
#step-celebrate .spark {
  position: absolute;
  inset: -12% 0;
  opacity: 0;
  background-image:
    radial-gradient(circle, rgba(255,183,3,.95) 0 3px, transparent 4px),
    radial-gradient(circle, rgba(239,71,111,.9) 0 3px, transparent 4px),
    radial-gradient(circle, rgba(6,214,160,.9) 0 3px, transparent 4px),
    radial-gradient(circle, rgba(17,138,178,.9) 0 3px, transparent 4px),
    radial-gradient(circle, rgba(255,255,255,.9) 0 2px, transparent 3px);
  background-size: 180px 180px, 210px 210px, 170px 170px, 230px 230px, 150px 150px;
  background-position: 0% 8%, 20% 0%, 50% 12%, 78% 3%, 95% 10%;
}
#step-celebrate.show .burst.left { animation: fxBurstLeft 2.3s ease-out both; }
#step-celebrate.show .burst.right { animation: fxBurstRight 2.3s ease-out both; }
#step-celebrate.show .spark { animation: fxSpark 2.3s ease-out both; }

@keyframes fxBurstLeft {
  0% { opacity: 0; transform: translate(-14vmax,-50%) scale(.45); }
  28% { opacity: .95; }
  100% { opacity: 0; transform: translate(5vmax,-50%) scale(1.25); }
}
@keyframes fxBurstRight {
  0% { opacity: 0; transform: translate(14vmax,-50%) scale(.45); }
  28% { opacity: .95; }
  100% { opacity: 0; transform: translate(-5vmax,-50%) scale(1.25); }
}
@keyframes fxSpark {
  0% { opacity: 0; transform: translateY(-14px); }
  20% { opacity: 1; }
  100% {
    opacity: 0;
    transform: translateY(54px);
    background-position: 6% 86%, 24% 92%, 52% 88%, 80% 94%, 96% 90%;
  }
}

/* ── Nav ── */
.wiz-nav {
  display:flex; flex-direction:column;
  align-items:center; margin-top:10px; gap:6px;
}
/* Step 1 skip is hidden by default; shown by JS on step 2+ */
.wiz-skip { font-size:.7rem; color:#8a6a30; text-decoration:none; font-weight:600; display:none; }
.wiz-skip:hover { color:#5a3e1b; text-decoration:underline; }
.wiz-nxt {
  background:linear-gradient(135deg,#5a3e1b,#a07840);
  color:#fff; border:none; border-radius:8px;
  padding:8px 16px; font-size:.8rem; font-weight:700;
  cursor:pointer; font-family:inherit; transition:opacity .2s, transform .15s;
}
.wiz-nxt:hover { opacity:.88; transform:translateY(-1px); }
.wiz-nxt:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.wiz-nxt.blocked { opacity:.5; cursor:not-allowed; transform:none; }

/* ── Toast ── */
#guide-toast {
  position:fixed; bottom:22px; left:50%;
  transform:translateX(-50%) translateY(80px);
  background:#1a0e05; color:#fff;
  padding:9px 18px; border-radius:8px;
  font-size:.82rem; font-weight:600;
  z-index:99999; transition:transform .3s;
  white-space:nowrap; box-shadow:0 4px 18px rgba(0,0,0,.4);
  pointer-events:none;
}
#guide-toast.show  { transform:translateX(-50%) translateY(0); }
#guide-toast.ok    { background:#2e7d32; }
#guide-toast.fail  { background:#c62828; }

/* ════════════════════════════════════
   RESPONSIVE
════════════════════════════════════ */
@media (max-width:640px) {
  #wiz-bubble {
    top: 8px;
    right: 8px;
    left: auto;
    max-width: clamp(160px, 55vw, 240px);
    font-size: .74rem;
    padding: 8px 11px;
  }
  #wiz-bubble::after { display: none; }

  /* Use simple background on all screens for phone */
  #s-intro, #s-wizard {
    background:
      linear-gradient(rgba(10,5,0,.35),rgba(10,5,0,.55)),
      url('<?php echo esc_url($BG); ?>') center/cover no-repeat;
  }

  .guides-grid { grid-template-columns:1fr; max-width:280px; margin:0 auto; gap:18px; }
  .g-btn img   { aspect-ratio:3/4; height:220px; object-fit:cover; }
  .intro-img   { height:58vh; right:-8px; opacity:.95; }
  .scene-seated{ left:2vw; height:36vh; }
  .scene-seated-select{ height:28vh; left:1vw; opacity:.8; }
  .intro-bubble{ right:10px; left:10px; top:14px; max-width:none; }
  .intro-bubble::after { display:none; }
  .intro-next  { bottom:20px; right:16px; }

  /* Card slides up from bottom on mobile */
  .wiz-card {
    position: fixed;
    top: 12px;
    bottom: auto;
    left: 8px;
    right: 8px;
    margin: 0;
    width: auto;
    border-radius: 18px;
    max-height: 58vh;
    z-index: 200;
    padding: 16px 14px 12px;
  }
  #s-wizard .wiz-card { top: 12px; }
  .wiz-guide-img {
    height: 46vh;
    right: -14px;
    bottom: -22px;
    opacity: .68;
  }

  .wiz-h { font-size:1.05rem; }
  .int-scroll { max-height: 190px; }
  .fld input, .fld textarea { font-size:.95rem; }
}
@media (min-width:641px) and (max-width:960px) {
  .wiz-card { margin-right:clamp(220px,30vw,360px); width:clamp(220px,30vw,290px); }
  .wiz-guide-img { height:84vh; }
  .scene-seated{ left:4vw; height:44vh; }
  .scene-seated-select{ height:34vh; }
}

/* ════════════════════════════════════
   POINTS POPUP
════════════════════════════════════ */
#pts-popup {
  position:fixed; bottom:192px; right:28px; z-index:2147483647;
  text-align:center; min-width:130px;
  background:linear-gradient(135deg,#5a3e1b,#c8a96e);
  border-radius:20px; padding:14px 24px 13px;
  box-shadow:0 10px 36px rgba(0,0,0,.6);
  pointer-events:none; opacity:0; transform:scale(.35);
  visibility:visible !important;
}
#pts-popup.pts-show { animation:ptsIn .55s cubic-bezier(.34,1.56,.64,1) forwards !important; }
#pts-popup.pts-hide { animation:ptsOut .4s ease-in forwards !important; }
#pts-prev {
  display:block; font-size:.68rem; font-weight:700;
  color:rgba(255,255,255,.65); margin-bottom:3px; letter-spacing:.02em;
}
#pts-added {
  display:block; font-size:1.65rem; font-weight:900; color:#fff;
  line-height:1.1; text-shadow:0 2px 10px rgba(0,0,0,.3);
}
#pts-label {
  display:block; font-size:.58rem; font-weight:800;
  color:rgba(255,255,255,.72); text-transform:uppercase;
  letter-spacing:.1em; margin:4px 0 3px;
}
#pts-total {
  display:block; font-size:.8rem; font-weight:700;
  color:rgba(255,255,255,.92);
}
@keyframes ptsIn {
  0%  { opacity:0; transform:scale(.3)  translateY(22px); }
  62% { transform:scale(1.18) translateY(-5px); opacity:1; }
  100%{ transform:scale(1)    translateY(0);    opacity:1; }
}
@keyframes ptsOut {
  0%  { opacity:1; transform:scale(1); }
  100%{ opacity:0; transform:scale(.42) translateY(12px); }
}
/* ── Persistent points badge ── */
#pts-badge {
  position:fixed; bottom:130px; right:28px; z-index:2147483646;
  background:linear-gradient(135deg,#3a2510,#7a5a30);
  color:#f5deb3; border-radius:20px; padding:6px 16px;
  font-size:.78rem; font-weight:800; box-shadow:0 4px 16px rgba(0,0,0,.4);
  pointer-events:none; letter-spacing:.03em;
}
</style>
</head>

<body>
<div id="guide-root">
  <button id="voice-mute-btn" style="position:fixed;top:18px;left:18px;z-index:99999;background:#fffbe6;border:2px solid #c8a96e;border-radius:8px;padding:7px 18px;font-weight:700;font-size:1rem;box-shadow:0 2px 8px rgba(0,0,0,.08);cursor:pointer;">🔇 Mute Voice</button>

<!-- ══ SCREEN 1 — SELECT ══ -->
<div class="g-screen active" id="s-select">
  <div class="sel-card">
    <h2>Welcome, please select a Guide to Empower Your Journey.</h2>
    <div class="guides-grid">
      <?php foreach ($guides as $key => $g): ?>
      <button class="g-btn" onclick="pickGuide('<?php echo esc_js($key); ?>')">
        <img src="<?php echo esc_url($g['real']); ?>"
             alt="<?php echo esc_attr($g['name']); ?>"
             loading="lazy"
             onerror="this.style.background='#d4b87a';this.src='';">
        <span><?php echo esc_html($g['name']); ?></span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ══ SCREEN 2 — INTRO ══ -->
<div class="g-screen" id="s-intro">
  <img id="intro-img" src="" alt="Guide" class="intro-img">
  <div class="intro-bubble" id="intro-txt"></div>
  <button class="intro-next" onclick="startWiz()">Next →</button>
</div>

<!-- ══ SCREEN 3 — WIZARD ══ -->
<div class="g-screen" id="s-wizard">
  <img id="wiz-img" src="" alt="Guide" class="wiz-guide-img">
  <div id="wiz-bubble"></div>

  <div class="wiz-card">
    <div class="s-dots">
      <div class="s-dot on" id="d1"></div>
      <div class="s-dot" id="d2"></div>
      <div class="s-dot" id="d3"></div>
      <div class="s-dot" id="d4"></div>
    </div>

    <!-- Page 1: Interests -->
    <div class="wiz-pg on" id="wp1">
      <div class="wiz-h">Interests &amp; Priorities</div>
      <p class="int-note">✅ Check an interest to unlock its priority dropdown. &nbsp;🔢 Each priority (1st, 2nd…) can only be used once. &nbsp;⚠ Every checked interest <strong>must</strong> have a priority before continuing.</p>
      <div class="int-scroll" id="int-wrap">
        <div style="text-align:center;padding:12px;font-size:.78rem;color:#8a6a30;">Loading interests…</div>
      </div>
    </div>

    <!-- Page 2: SEO Card -->
    <div class="wiz-pg" id="wp2">
      <div class="wiz-h">SEO Optimized Biz Card</div>

      <div class="fld">
        <label class="fld-lbl">Title</label>
        <span class="fld-hint">Keyword-rich e.g. "Life Coach &amp; Business Mentor"</span>
        <input type="text" id="f-title" placeholder="e.g. Life Coach &amp; Business Mentor"
               value="<?php echo esc_attr($sv['title']); ?>">
        <div class="fld-err" id="e-title">⚠ Please enter your professional title.</div>
      </div>
      <div class="fld">
        <label class="fld-lbl">About Me <small style="font-size:.6rem;font-weight:400;text-transform:none;">(Max 150)</small></label>
        <span class="fld-hint">Short bio with keywords — boosts your SEO profile.</span>
        <textarea id="f-about" maxlength="150" rows="3"
                  placeholder="e.g. Certified coach helping entrepreneurs build wealth..."><?php echo esc_textarea($sv['about']); ?></textarea>
        <div class="fld-char"><span id="ab-ct"><?php echo strlen($sv['about']); ?></span>/150</div>
        <div class="fld-err" id="e-about">⚠ Please enter a short bio.</div>
      </div>
      <div class="fld">
        <label class="fld-lbl">Address</label>
        <span class="fld-hint">City/State improves local SEO e.g. "Atlanta, GA"</span>
        <input type="text" id="f-addr" placeholder="e.g. Atlanta, GA"
               value="<?php echo esc_attr($sv['address']); ?>">
        <div class="fld-err" id="e-addr">⚠ Please enter your city or address.</div>
      </div>
      <div class="fld">
        <label class="fld-lbl">Keywords</label>
        <span class="fld-hint">Type a keyword and press comma or Enter to add it as a tag.</span>
        <div class="tag-cloud-wrap" id="kw-cloud">
          <div class="tag-chips" id="kw-chips"></div>
          <input type="text" class="tag-input" id="kw-input" placeholder="keyword, press comma…" autocomplete="off">
        </div>
        <input type="hidden" id="f-kw" value="<?php echo esc_attr($sv['keywords']); ?>">
        <div class="fld-err" id="e-kw">⚠ Please enter at least one keyword.</div>
      </div>
      <div class="fld">
        <label class="fld-lbl">Hashtags</label>
        <span class="fld-hint">Type a hashtag and press comma or Enter — # is added automatically.</span>
        <div class="tag-cloud-wrap" id="ht-cloud">
          <div class="tag-chips" id="ht-chips"></div>
          <input type="text" class="tag-input" id="ht-input" placeholder="#coaching, press comma…" autocomplete="off">
        </div>
        <input type="hidden" id="f-ht" value="<?php echo esc_attr($sv['hashtags']); ?>">
        <div class="fld-err" id="e-ht">⚠ Enter hashtags starting with # e.g. #coaching</div>
      </div>
    </div>

    <!-- Page 3: Social -->
    <div class="wiz-pg" id="wp3">
      <div class="wiz-h">Add Your Social Links</div>
      <p class="int-note">🔗 Platform + full URL e.g. Twitter → https://twitter.com/you</p>
      <div class="soc-cols"><span>Platform</span><span>Custom Name</span><span>URL</span><span></span></div>
      <div id="soc-wrap"></div>
      <button type="button" class="soc-add" onclick="addSocRow()">+ Add Link</button>
    </div>

    <!-- Page 4: Congrats -->
    <div class="wiz-pg" id="wp4">
      <div class="congrats">
        <div class="cong-title">🎉 Congratulations!</div>
        <div class="cong-text">You've completed the first steps in building your 24/7 Empowerment SEO/CRM Profile. Continue exploring to experience Inner Empowerment combined with real External Empowerment — featuring 100+ sales and business tools, jobs, wallets, passive income opportunities, and affordable housing.</div>
        <p class="cong-tasks-title">At your convenience, complete these sections to activate all tools &amp; benefits:</p>
        <ul class="cong-tasks-list">
          <li>📇 Digital Business Card</li>
          <li>📱 Social Management</li>
          <li>💳 Wallet &amp; Payments</li>
          <li>📧 Set up Payouts Email</li>
          <li>💸 Withdrawal Request</li>
          <li>🤝 Meeting &amp; Integrations</li>
          <li>🔒 Password &amp; Security</li>
          <li>🔔 Notifications</li>
          <li>📹 Connect Zoom Account</li>
          <li>📅 My Zoom Meetings</li>
          <li>📆 Book Meeting</li>
          <li>🔗 Connect Social Media</li>
        </ul>
      </div>
    </div>

    <div class="wiz-nav">
      <a href="<?php echo esc_url($skip_url); ?>" class="wiz-skip">Skip</a>
      <button type="button" class="wiz-nxt" id="wiz-btn" onclick="wizNext()">Next Page</button>
    </div>
  </div><!-- /wiz-card -->
</div><!-- /s-wizard -->

</div><!-- /guide-root -->
<div id="step-celebrate" aria-hidden="true">
  <div class="left burst"></div>
  <div class="right burst"></div>
  <div class="spark"></div>
</div>
<div id="guide-toast"></div>

<!-- Welcome overlay — shown after completing all steps -->
<div id="welcome-overlay">
  <img class="wov-img" id="wov-guide-img" src="" alt="Guide">
  <div class="wov-title">🎉 Congratulations!</div>
  <div class="wov-text">You've completed the first steps in building your 24/7 Empowerment SEO/CRM Profile. Continue exploring to experience Inner Empowerment combined with real External Empowerment — featuring 100+ sales and business tools, jobs, wallets, passive income opportunities, and affordable housing.</div>
  <p class="wov-tasks-title">At your convenience, complete these sections to activate all tools &amp; benefits:</p>
  <ul class="wov-tasks-list">
    <li>📇 Digital Business Card</li>
    <li>📱 Social Management</li>
    <li>💳 Wallet &amp; Payments</li>
    <li>📧 Set up Payouts Email</li>
    <li>💸 Withdrawal Request</li>
    <li>🤝 Meeting &amp; Integrations</li>
    <li>🔒 Password &amp; Security</li>
    <li>🔔 Notifications</li>
    <li>📹 Connect Zoom Account</li>
    <li>📅 My Zoom Meetings</li>
    <li>📆 Book Meeting</li>
    <li>🔗 Connect Social Media</li>
  </ul>
  <button id="wov-proceed-btn" onclick="finishOnboarding()">Go to Profile →</button>
</div>

<div id="pts-popup" aria-live="polite" aria-atomic="true">
  <span id="pts-prev">Before: 0 pts</span>
  <span id="pts-added">+10 pts</span>
  <span id="pts-label">🏆 Points Earned!</span>
  <span id="pts-total">New Total: 0 pts</span>
</div>
<!-- Always-visible points badge bottom-right -->
<div id="pts-badge" title="Your total points">🏆 <span id="pts-badge-val"><?php echo (int)$current_pts; ?></span> pts</div>

<script>
var SELF    = '<?php echo esc_js(home_url($_SERVER['REQUEST_URI'])); ?>';
var NONCE   = '<?php echo esc_js($nonce); ?>';
var PNONCE  = '<?php echo esc_js($plugin_nonce); ?>';
var AJAXURL = '<?php echo esc_js($ajax_url); ?>';
var PROF    = '<?php echo esc_js($profile_url); ?>';
var SEATED_DEFAULT = '<?php echo esc_js($SEATED_SMALL); ?>';
var PREFILL_SOCIAL = <?php echo wp_json_encode( $sv_social ); ?>;
var GUIDES    = <?php echo wp_json_encode(array_map(fn($g)=>['name'=>$g['name'],'img'=>$g['real'],'stand'=>$g['stand'],'intro'=>$g['intro']], $guides)); ?>;
var API_NONCE = '<?php echo esc_js($api_nonce); ?>';
var SITE_URL  = '<?php echo esc_js($api_base); ?>';
var PLATFORM_OPTIONS = <?php echo wp_json_encode( $platform_opts_js ); ?>;
var INIT_POINTS = <?php echo (int) $current_pts; ?>;
var PTS_FLAGS   = <?php echo wp_json_encode( $pts_flags ); ?>;

var selKey = null, step = 1, STEPS = 4;

/* ── screens ── */
function showS(id){
    document.querySelectorAll('.g-screen').forEach(function(s){ s.classList.remove('active'); });
    document.getElementById(id).classList.add('active');
}

// --- VOICE MUTE SYSTEM ---
window.voiceMuted = false;

function setSeated(src){
  var seats = [];
  seats.forEach(function(img){
    if(!img) return;
    img.onerror = function(){
      this.onerror = null;
      this.src = SEATED_DEFAULT;
    };
    img.src = src || SEATED_DEFAULT;
  });
}
function getVoicesReady(cb){
  if(!window.speechSynthesis || !window.speechSynthesis.getVoices){ cb([]); return; }
  var voices = window.speechSynthesis.getVoices() || [];
  if(voices.length){ cb(voices); return; }

  var done = false;
  var finish = function(v){
    if(done) return;
    done = true;
    cb(v || []);
  };

  window.speechSynthesis.onvoiceschanged = function(){
    finish(window.speechSynthesis.getVoices() || []);
  };

  setTimeout(function(){
    finish(window.speechSynthesis.getVoices() || []);
  }, 500);
}

function pickVoiceForGuide(key, voices){
  voices = voices || [];
  if(!voices.length) return null;

  if(key === 'bella'){
    // Prefer known female voices across Chrome/Edge/Safari environments.
    var preferredFemale = [
      'Google UK English Female',
      'Microsoft Zira',
      'Microsoft Aria',
      'Samantha',
      'Victoria',
      'Karen',
      'Moira',
      'Ava',
      'Natasha',
      'Susan'
    ];

    for(var i=0;i<preferredFemale.length;i++){
      var exact = voices.find(function(v){
        return (v.name || '').toLowerCase().indexOf(preferredFemale[i].toLowerCase()) !== -1;
      });
      if(exact) return exact;
    }

    var female = voices.find(function(v){
      var n = (v.name || '').toLowerCase();
      return /female|woman|girl|zira|samantha|victoria|karen|moira|ava|aria|natasha|susan/.test(n);
    });
    if(female) return female;
  }

  // Joseph/Zoro should prefer male voices.
  var preferredMale = [
    'Google UK English Male',
    'Microsoft David',
    'Microsoft Guy',
    'Daniel',
    'Alex',
    'Fred',
    'Tom',
    'John'
  ];

  for(var j=0;j<preferredMale.length;j++){
    var maleExact = voices.find(function(v){
      return (v.name || '').toLowerCase().indexOf(preferredMale[j].toLowerCase()) !== -1;
    });
    if(maleExact) return maleExact;
  }

  var male = voices.find(function(v){
    var n = (v.name || '').toLowerCase();
    return /male|man|boy|david|guy|daniel|alex|fred|tom|john/.test(n);
  });
  if(male) return male;

  return null;
}
function isPhoneView(){
  // Phone-only mute: keep desktop/tablet behavior unchanged.
  return window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
}

/* ════════════════════════════════════
   GAMIFICATION — POINTS API
   POST /wp-json/api/v1/spg/leaderboard/award-points
════════════════════════════════════ */

// Running total synced from server; seeded from PHP on load
var _runningPts = INIT_POINTS;

// On load: fetch real gamification total (what wallet reads) and sync badge
(function(){
    fetch(SITE_URL + '/wp-json/api/v1/spg/user/earned-points?nonce=' + encodeURIComponent(API_NONCE))
        .then(function(r){ return r.json(); })
        .then(function(resp){
            if(resp && resp.success && resp.data){
                var earned = resp.data.user_earned_points;
                var total = (earned && typeof earned.total !== 'undefined')
                            ? earned.total : resp.data.total_points;
                if(total){
                    _runningPts = total;
                    var badge = document.getElementById('pts-badge-val');
                    if(badge) badge.textContent = total;
                }
            }
        })
        .catch(function(){});
})();

// Dedup map — reason → meta flag name (matches mm_spg_* user meta)
var REASON_FLAG = {
    'interest_completed':      'interest_completed',
    'business_card_completed': 'business_card_completed',
    'social_links_completed':  'social_links_completed',
    'onboarding_completed':    'onboarding_completed'
};

function awardPoints(reason, points, cb){
    cb = cb || function(){};

    // Dedup: if this step was already awarded (server flag = true), skip
    if(PTS_FLAGS[reason]){ cb(true); return; }

    var prev = _runningPts;

    // The PHP save handler already called mm_award_points_and_notify() server-side.
    // Now fetch the real gamification total (what the wallet reads) from the plugin endpoint.
    fetch(SITE_URL + '/wp-json/api/v1/spg/user/earned-points?nonce=' + encodeURIComponent(API_NONCE))
        .then(function(r){ return r.json(); })
        .then(function(resp){
            var newTotal = prev + points; // optimistic fallback
            if(resp && resp.success && resp.data){
                var earned = resp.data.user_earned_points;
                if(earned && typeof earned.total !== 'undefined'){
                    newTotal = earned.total;
                } else if(typeof resp.data.total_points !== 'undefined'){
                    newTotal = resp.data.total_points;
                }
            }
            var added = newTotal - prev;
            if(added <= 0) added = points;
            _runningPts = newTotal;
            PTS_FLAGS[reason] = true;
            showPtsPopup(added, prev, newTotal);
            sendBpNotification(reason, added, newTotal);
            cb(true);
        })
        .catch(function(){
            // Fallback: show optimistic total
            _runningPts = prev + points;
            PTS_FLAGS[reason] = true;
            showPtsPopup(points, prev, _runningPts);
            cb(true);
        });
}

/* sendBpNotification — create a BuddyPress notification for earned points */
function sendBpNotification(reason, added, total){
    if(!AJAXURL || !PNONCE) return;
    var fd = new FormData();
    fd.append('action', 'guide_add_notification');
    fd.append('nonce',  PNONCE);
    fd.append('reason', reason);
    fd.append('added',  added);
    fd.append('total',  total);
    fetch(AJAXURL, { method:'POST', body: fd }).catch(function(){});
}

function showPtsPopup(added, prevTotal, newTotal){
    var pop = document.getElementById('pts-popup');
    if(!pop) return;
    var prevEl = document.getElementById('pts-prev');
    if(prevEl) prevEl.textContent = 'Before: ' + prevTotal + ' pts';
    document.getElementById('pts-added').textContent = '+' + added + ' pts';
    document.getElementById('pts-label').textContent = '\ud83c\udfc6 Points Earned!';
    document.getElementById('pts-total').textContent = 'New Total: ' + newTotal + ' pts';
    pop.classList.remove('pts-show','pts-hide');
    void pop.offsetWidth;                    // force reflow to restart animation
    pop.classList.add('pts-show');
    if(window._ptsTimer) clearTimeout(window._ptsTimer);
    window._ptsTimer = setTimeout(function(){
        pop.classList.remove('pts-show');
        pop.classList.add('pts-hide');
    }, 4000);
    // Update persistent badge
    var badge = document.getElementById('pts-badge-val');
    if(badge) badge.textContent = newTotal;
}

function pickGuide(k){
    selKey = k;
    var g = GUIDES[k];
    setSeated(SEATED_DEFAULT);
    document.getElementById('intro-img').src = g.stand;
    var bubble = document.getElementById('intro-txt');
    bubble.style.display = 'block';
    bubble.textContent = g.intro;
    if(window.speechSynthesis && !window.voiceMuted){
        try { window.speechSynthesis.cancel(); } catch(e){}
        // Speak synchronously inside the user-gesture context — Chrome blocks async speak()
        try {
          var u = new SpeechSynthesisUtterance(g.intro);
          u.lang = 'en-US';
          u.rate = 0.95;
          u.pitch = (k === 'bella') ? 1.35 : 0.78;
          var voices = window.speechSynthesis.getVoices();
          if(voices && voices.length){
            var v = pickVoiceForGuide(k, voices);
            if(v){ u.voice = v; if(v.lang) u.lang = v.lang; }
          }
          window.speechSynthesis.speak(u);
          // If voices weren't loaded yet, retry once they arrive
          if(!voices || !voices.length){
            window.speechSynthesis.onvoiceschanged = function(){
              window.speechSynthesis.onvoiceschanged = null;
              if(window.voiceMuted) return;
              try {
                var u2 = new SpeechSynthesisUtterance(g.intro);
                u2.lang = 'en-US'; u2.rate = 0.95;
                u2.pitch = (k === 'bella') ? 1.35 : 0.78;
                var vs2 = window.speechSynthesis.getVoices();
                var v2  = pickVoiceForGuide(k, vs2);
                if(v2){ u2.voice = v2; if(v2.lang) u2.lang = v2.lang; }
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(u2);
              } catch(e){}
            };
          }
        } catch(e){}
    }
    showS('s-intro');
}
function startWiz(){
  if(window.speechSynthesis){
      try { window.speechSynthesis.cancel(); } catch(e){}
  }
  document.getElementById('wiz-img').src = GUIDES[selKey].stand;
  showS('s-wizard');
  loadInts();
  // Show step 1 bubble + speak
  showWizBubble(STEP_HINTS[1] || '');
  speakStep(1);
}

/* ── step speech hints ── */
var STEP_HINTS = [
  '', // index 0 unused
  "Pick the topics that interest you most and set a priority for each one. Both a selection and a priority are required to continue.",
  "Fill in your professional title, a short bio, your location, keywords and hashtags. This builds your SEO profile so people can find you.",
  "Add your social media and website links. Choose the platform from the dropdown, give it a custom name if you like, then paste the full URL.",
  "Congratulations! You finished setting up your basic profile. Now let's review your main interest in visiting 24/7 Empowerment — check the top right!"
];

function showWizBubble(text){
  var b = document.getElementById('wiz-bubble');
  if(!b) return;
  b.textContent = text;
  b.classList.remove('vis');
  void b.offsetWidth; // reflow to restart transition
  b.classList.add('vis');
}

function speakStep(n){
  if(!window.speechSynthesis || window.voiceMuted) return;
  var text = STEP_HINTS[n];
  if(!text || !selKey) return;
  // Cancel any in-flight speech immediately
  try { window.speechSynthesis.cancel(); } catch(e){}
  // Debounce: if called rapidly, only fire once things settle
  if(window._speakTimer){ clearTimeout(window._speakTimer); }
  window._speakTimer = setTimeout(function(){
    if(window.voiceMuted) return; // re-check after delay
    try {
      var u = new SpeechSynthesisUtterance(text);
      u.lang  = 'en-US';
      u.rate  = 0.95;
      u.pitch = (selKey === 'bella') ? 1.35 : 0.78;
      var voices = window.speechSynthesis.getVoices();
      if(voices && voices.length){
        var v = pickVoiceForGuide(selKey, voices);
        if(v){ u.voice = v; if(v.lang) u.lang = v.lang; }
      }
      window.speechSynthesis.speak(u);
    } catch(e){}
  }, 120);
}

/* ── dots ── */
function setDots(n){
    for(var i=1;i<=STEPS;i++){
        var d=document.getElementById('d'+i);
        if(d) d.className='s-dot'+(i===n?' on':'');
    }
}
function goStep(n){
    document.querySelectorAll('.wiz-pg').forEach(function(p){ p.classList.remove('on'); });
    var page = document.getElementById('wp'+n);
    page.classList.add('on');

    step=n; setDots(n);
    document.getElementById('wiz-btn').textContent=(n===STEPS)?'Go to Profile':'Next Page';

    // Point 2: hide Skip on step 1 — interests & priorities are mandatory
    var skipLink = document.querySelector('.wiz-skip');
    if(skipLink) skipLink.style.display = (n === 1) ? 'none' : '';

    // Show cloud hint and speak for this step (overlay handles speech on last step)
    showWizBubble(STEP_HINTS[n] || '');
    if(n !== STEPS) speakStep(n);

    updateNextButtonState();
    // On last step, show the full-screen congratulations overlay immediately
    if(n === STEPS){ showWelcomeOverlay(); }
}

function playStepCelebration(done){
  var fx = document.getElementById('step-celebrate');
  if(!fx){
    if(typeof done === 'function') done();
    return;
  }
  fx.classList.remove('show');
  void fx.offsetWidth;
  fx.classList.add('show');
  setTimeout(function(){
    fx.classList.remove('show');
    if(typeof done === 'function') done();
  }, 2300);
}

/* ── Welcome overlay ── */
function showWelcomeOverlay(){
  // Kill any pending speakStep timer — prevents double audio on last step
  if(window._speakTimer){ clearTimeout(window._speakTimer); window._speakTimer = null; }
  var ov = document.getElementById('welcome-overlay');
  if(!ov){ return; }
  // Set guide image
  var img = document.getElementById('wov-guide-img');
  if(img && selKey && GUIDES[selKey]) img.src = GUIDES[selKey].stand;
  ov.classList.add('show');
  // Speak congratulations — user clicks "Go to Profile" button to proceed (no auto-redirect)
  if(window.speechSynthesis && !window.voiceMuted){
    try {
      window.speechSynthesis.cancel();
      var u = new SpeechSynthesisUtterance(
        "Congratulations! You've completed the first steps in building your 24 7 Empowerment profile. Continue exploring all the tools and benefits available to you!"
      );
      u.lang = 'en-US'; u.rate = 0.95;
      u.pitch = (selKey === 'bella') ? 1.35 : 0.78;
      var voices = window.speechSynthesis.getVoices();
      if(voices && voices.length){
        var v = pickVoiceForGuide(selKey, voices);
        if(v){ u.voice = v; if(v.lang) u.lang = v.lang; }
      }
      window.speechSynthesis.speak(u);
    } catch(e){}
  }
}

function finishOnboarding(){
  var btn = document.getElementById('wov-proceed-btn');
  if(btn){ btn.disabled=true; btn.textContent='Loading\u2026'; }
  if(window.speechSynthesis){ try{ window.speechSynthesis.cancel(); }catch(e){} }
  post('complete_onboarding', {}, function(){
    awardPoints('onboarding_completed', 10, function(){
      window.location.href = PROF + '?guide_welcome=1&guide=' + encodeURIComponent(selKey || 'joseph');
    });
  });
}

/* ── Tag-cloud chip logic ── */
function initTagCloud(cloudId, chipsId, inputId, hiddenId, isHashtag){
  var cloud  = document.getElementById(cloudId);
  var chips  = document.getElementById(chipsId);
  var input  = document.getElementById(inputId);
  var hidden = document.getElementById(hiddenId);
  if(!cloud || !chips || !input || !hidden) return;

  // Click cloud area → focus input
  cloud.addEventListener('click', function(){ input.focus(); });

  // Seed chips from existing hidden value
  var existing = hidden.value.trim();
  if(existing){
    var parts = isHashtag
      ? existing.split(/[\s,]+/)
      : existing.split(',');
    parts.forEach(function(t){
      t = t.trim();
      if(t) addChip(t);
    });
    hidden.value = collectValues();
  }

  function normalise(raw){
    var t = raw.trim();
    if(!t) return '';
    if(isHashtag && t.charAt(0) !== '#') t = '#' + t;
    return t;
  }

  function collectValues(){
    var all = [];
    chips.querySelectorAll('.tag-chip').forEach(function(c){
      all.push(c.dataset.value);
    });
    return isHashtag ? all.join(' ') : all.join(', ');
  }

  function addChip(raw){
    var val = normalise(raw);
    if(!val) return;
    // Avoid duplicates
    var existing = chips.querySelectorAll('.tag-chip');
    for(var i=0;i<existing.length;i++){
      if(existing[i].dataset.value === val) return;
    }
    var chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.dataset.value = val;
    chip.textContent = val + ' ';
    var rm = document.createElement('span');
    rm.className = 'tc-rm';
    rm.textContent = '×';
    rm.setAttribute('aria-label','Remove ' + val);
    rm.addEventListener('click', function(e){
      e.stopPropagation();
      chip.remove();
      hidden.value = collectValues();
      triggerUpdateStep2();
    });
    chip.appendChild(rm);
    chips.appendChild(chip);
    hidden.value = collectValues();
    triggerUpdateStep2();
  }

  function tryCommit(){
    var raw = input.value;
    // Split on comma
    var parts = raw.split(',');
    parts.forEach(function(p){ addChip(p); });
    input.value = '';
  }

  input.addEventListener('keydown', function(e){
    if(e.key === ',' || e.key === 'Enter'){
      e.preventDefault();
      tryCommit();
    } else if(e.key === 'Backspace' && input.value === ''){
      var last = chips.querySelector('.tag-chip:last-child');
      if(last){ last.remove(); hidden.value = collectValues(); triggerUpdateStep2(); }
    }
  });

  // Also commit when user types a comma anywhere mid-text
  input.addEventListener('input', function(){
    if(input.value.indexOf(',') !== -1) tryCommit();
  });

  // Commit on blur
  input.addEventListener('blur', function(){
    if(input.value.trim()) tryCommit();
  });
}

function triggerUpdateStep2(){
  // Re-evaluate Next button state while on step 2
  if(typeof updateNextButtonState === 'function') updateNextButtonState();
}

  function isStep1Ready(){
    var chk = document.querySelectorAll('#int-wrap input[name="user_categories[]"]:checked');
    if(!chk.length) return false;
    var allHavePrio = true;
    chk.forEach(function(cb){
      var row = cb.closest('.int-item');
      var sel = row && row.querySelector('select');
      if(!sel || !sel.value || sel.value === '' || sel.value === 'Priority') allHavePrio = false;
    });
    return allHavePrio;
  }

  function isStep2Ready(){
    var ids = ['f-title','f-about','f-addr','f-kw','f-ht'];
    for(var i=0;i<ids.length;i++){
      if(!vv(ids[i])) return false;
    }
    var hv = normTags(vv('f-ht'));
    return !!hv && hv.indexOf('#') !== -1;
  }

  function updateNextButtonState(){
    var btn = document.getElementById('wiz-btn');
    if(!btn) return;

    var ready = true;
    if(step===1) ready = isStep1Ready();
    else if(step===2) ready = isStep2Ready();

    if(ready){
      btn.classList.remove('blocked');
      btn.dataset.blocked = '0';
    } else {
      btn.classList.add('blocked');
      btn.dataset.blocked = '1';
    }
  }

/* ── toast ── */
function toast(m,type){
    var t=document.getElementById('guide-toast');
    t.textContent=m; t.className='show '+(type||'');
    setTimeout(function(){ t.className=''; },3200);
}

/* ── AJAX: post to self ── */
function post(action, extra, cb){
    var fd=new FormData();
    fd.append('guide_action',action);
    fd.append('guide_nonce',NONCE);
    if(extra){ Object.keys(extra).forEach(function(k){
        var v=extra[k];
        if(Array.isArray(v)){ v.forEach(function(i){ fd.append(k+'[]',i); }); }
        else { fd.append(k,v); }
    }); }
    fetch(SELF,{method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .then(function(r){
            if(r.success) cb(true,r.data);
            else { toast('⚠ '+(r.data&&r.data.message?r.data.message:'Error. Try again.'),'fail'); cb(false); }
        })
        .catch(function(){ toast('⚠ Network error. Try again.','fail'); cb(false); });
}

/* ── AJAX: plugin fallback for interests ── */
function postPlugin(action, extra, cb){
    var fd=new FormData();
    fd.append('action',action);
    fd.append('nonce',PNONCE);
    if(extra){ Object.keys(extra).forEach(function(k){ fd.append(k,extra[k]); }); }
    fetch(AJAXURL,{method:'POST',body:fd})
        .then(function(r){ return r.json(); })
        .then(function(r){ if(r.success) cb(true,r.data); else cb(false,r.data); })
        .catch(function(){ cb(false); });
}

/* ── Step 1: load interests ── */
var PRIO_OPTS = ['Priority','1st','2nd','3rd','4th','5th','6th','7th','8th','9th','10th'];

/* Returns priority values already chosen by OTHER checked interests */
function getUsedPriorities(exceptSelect){
    var used={};
    document.querySelectorAll('#int-wrap .int-item').forEach(function(row){
        var cb=row.querySelector('input[type="checkbox"]');
        var sel=row.querySelector('select');
        if(cb && cb.checked && sel && sel !== exceptSelect && sel.value && sel.value !== 'Priority'){
            used[sel.value]=true;
        }
    });
    return used;
}

/* Rebuild disabled <option> states across all priority selects */
function syncPriorityDropdowns(){
    document.querySelectorAll('#int-wrap .int-item').forEach(function(row){
        var sel=row.querySelector('select');
        if(!sel) return;
        var usedByOthers=getUsedPriorities(sel);
        Array.prototype.forEach.call(sel.options,function(opt){
            if(opt.value==='Priority'||opt.value==='') return;
            opt.disabled=!!usedByOthers[opt.value];
        });
    });
    if(typeof updateNextButtonState==='function') updateNextButtonState();
}

/* Wire one interest row: disable select until checkbox is checked */
function wireIntRow(row){
    var cb=row.querySelector('input[type="checkbox"]');
    var sel=row.querySelector('select');
    if(!cb||!sel) return;

    sel.disabled=!cb.checked;
    if(!cb.checked) sel.value='Priority';

    cb.addEventListener('change',function(){
        sel.disabled=!cb.checked;
        if(!cb.checked){ sel.value='Priority'; }
        syncPriorityDropdowns();
    });
    sel.addEventListener('change',function(){
        syncPriorityDropdowns();
    });
}

function renderInts(items){
    if(!items||!items.length){
        document.getElementById('int-wrap').innerHTML=
            '<div style="text-align:center;padding:10px;font-size:.76rem;color:#d94f4f;">No interests found. Please contact admin.</div>';
        return;
    }
    var html='';
    items.forEach(function(item){
        var opts=PRIO_OPTS.map(function(o,i){
            var sel=(item.priority&&(item.priority===o||parseInt(item.priority)===i))?' selected':'';
            return '<option value="'+o+'"'+sel+'>'+o+'</option>';
        }).join('');
        html+='<div class="int-item">'
            +'<input type="checkbox" id="cat_'+item.value+'" name="user_categories[]" value="'+item.value+'"'+(item.checked?' checked':'')+'>'
            +'<label for="cat_'+item.value+'">'+item.label+'</label>'
            +'<select name="uprio_'+item.value+'">'+opts+'</select>'
            +'</div>';
    });
    document.getElementById('int-wrap').innerHTML=html;
    // Wire each row, then sync priority exclusivity
    document.querySelectorAll('#int-wrap .int-item').forEach(wireIntRow);
    syncPriorityDropdowns();
}

function loadInts(){
    var wrap=document.getElementById('int-wrap');
    wrap.innerHTML='<div style="text-align:center;padding:12px;font-size:.78rem;color:#8a6a30;">Loading interests…</div>';

    // First try self (custom handler)
    post('get_interests',{},function(ok,data){
        if(ok && data && data.items && data.items.length){
            renderInts(data.items); return;
        }
        // Fallback: use the existing plugin AJAX
        postPlugin('get_guide_interests_html',{},function(ok2,data2){
            if(ok2 && data2 && data2.html){
                // Parse the plugin's HTML and rebuild
                var parser=new DOMParser();
                var doc=parser.parseFromString(data2.html,'text/html');
                var listItems=doc.querySelectorAll('.list-item');
                if(!listItems.length){
                    // Try raw html render
                    wrap.innerHTML=data2.html;
                    return;
                }
                var items=[];
                listItems.forEach(function(li){
                    var cb=li.querySelector('input[type="checkbox"]');
                    var lbl=li.querySelector('label');
                    var sel=li.querySelector('select');
                    if(cb&&lbl){
                        items.push({
                            value:cb.value,
                            label:lbl.textContent.trim(),
                            checked:cb.checked,
                            priority:sel?sel.value:''
                        });
                    }
                });
                renderInts(items);
            } else {
                wrap.innerHTML='<div style="text-align:center;padding:10px;font-size:.76rem;color:#d94f4f;">Could not load interests. Please refresh.</div>';
            }
        });
    });
}

function valStep1(){
    var chk=document.querySelectorAll('#int-wrap input[name="user_categories[]"]:checked');
    if(!chk || !chk.length){ toast('⚠ Please select at least one interest.','fail'); return false; }
    var missingPrio = false;
    chk.forEach(function(cb){
        var row = cb.closest('.int-item');
        var sel = row && row.querySelector('select');
        if(!sel || !sel.value || sel.value === '' || sel.value === 'Priority'){
            missingPrio = true;
            if(sel) sel.style.outline = '2px solid #d94f4f';
        } else {
            if(sel) sel.style.outline = '';
        }
    });
    if(missingPrio){ toast('⚠ Please set a priority for every selected interest.','fail'); return false; }
    return true;
}
function saveInts(cb){
    var cats=[], prios={};
    document.querySelectorAll('#int-wrap input[name="user_categories[]"]:checked')
        .forEach(function(c){ cats.push(c.value); });
    document.querySelectorAll('#int-wrap select')
        .forEach(function(s){ prios[s.name.replace('uprio_','user_categories_priority_')]=s.value; });
    var data={user_categories:cats};
    Object.assign(data,prios);

    // Try saving to self first
    post('save_interests',data,function(ok){
        if(ok){ toast('✓ Interests saved!','ok'); cb(true); return; }
        // Fallback: use existing plugin save
        var fd2=new FormData();
        fd2.append('action','save_guide_form_data');
        fd2.append('nonce',PNONCE);
        cats.forEach(function(v){ fd2.append('user_categories[]',v); });
        Object.keys(prios).forEach(function(k){ fd2.append(k,prios[k]); });
        fetch(AJAXURL,{method:'POST',body:fd2})
            .then(function(r){ return r.json(); })
            .then(function(r){ if(r.success){ toast('✓ Interests saved!','ok'); cb(true); } else { toast('Error saving interests.','fail'); cb(false); } })
            .catch(function(){ toast('Network error.','fail'); cb(false); });
    });
}

/* ── Step 2: validate & save biz card ── */
function setE(fi,ei,show,msg){
    var el=document.getElementById(fi), er=document.getElementById(ei);
    if(!el||!er) return;
    if(show){ el.classList.add('ferr'); er.style.display='block'; if(msg) er.textContent=msg; }
    else    { el.classList.remove('ferr'); er.style.display='none'; }
}
function vv(id){ var e=document.getElementById(id); return e?e.value.trim():''; }
function normTags(v){
    if(!v) return '';
    return v
      .split(/[\s,]+/)
      .map(function(t){ return t.trim(); })
      .filter(Boolean)
      .map(function(t){ return t.charAt(0)==='#' ? t : '#'+t; })
      .join(' ');
}

function valStep2(){
    var ok=true;
    [['f-title','e-title'],['f-about','e-about'],['f-addr','e-addr'],
     ['f-kw','e-kw'],['f-ht','e-ht']].forEach(function(p){
        if(!vv(p[0])){ setE(p[0],p[1],true); ok=false; }
        else           setE(p[0],p[1],false);
    });
    var htEl=document.getElementById('f-ht');
    if(htEl){
      htEl.value = normTags(htEl.value);
    }
    var hv=vv('f-ht');
    if(hv&&!hv.includes('#')){ setE('f-ht','e-ht',true,'⚠ Hashtags must start with # e.g. #coaching'); ok=false; }
    if(!ok){ var f=document.querySelector('.ferr'); if(f) f.scrollIntoView({behavior:'smooth',block:'center'}); }
    return ok;
}
function saveBiz(cb){
  var htEl=document.getElementById('f-ht');
  if(htEl){ htEl.value = normTags(htEl.value); }
    var d={title:document.getElementById('f-title').value,
           about:document.getElementById('f-about').value,
           address:document.getElementById('f-addr').value,
           keywords:document.getElementById('f-kw').value,
           hashtags:document.getElementById('f-ht').value};
    post('save_bizcard',d,function(ok){
        if(ok){ toast('✓ Profile saved!','ok'); cb(true); return; }
        // Plugin fallback
        var fd2=new FormData();
        fd2.append('action','save_guide_form_data');
        fd2.append('nonce',PNONCE);
        Object.keys(d).forEach(function(k){ fd2.append(k,d[k]); });
        fetch(AJAXURL,{method:'POST',body:fd2})
            .then(function(r){ return r.json(); })
            .then(function(r){ if(r.success){ toast('✓ Profile saved!','ok'); cb(true); } else { toast('Error saving profile.','fail'); cb(false); } });
    });
}

/* ── Step 3: social links ── */
function isValidUrl(str){
  try {
    new URL(str);
    return true;
  } catch(e){
    return false;
  }
}

/* HTML-escape helper for building innerHTML safely */
function socEsc(s){
    return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function addSocRow(platform, label, url){
    var c = document.getElementById('soc-wrap');
    var row = document.createElement('div');
    row.className = 'soc-row';
    // Match stored value by slug, or by label (for data saved before slug format was used)
    var pLow = (platform||'').toLowerCase();
    var optHtml = '<option value="">-- Platform --</option>'
        + (PLATFORM_OPTIONS||[]).map(function(opt){
            var isMatch = opt.value === pLow
                || opt.label.toLowerCase() === pLow
                || opt.value === pLow.replace(/\s+/g,'_');
            return '<option value="'+socEsc(opt.value)+'"'+(isMatch?' selected':'')+'>'+socEsc(opt.label)+'</option>';
          }).join('');
    row.innerHTML = '<select class="sp">'+optHtml+'</select>'
                  + '<input type="text" placeholder="Custom name" class="sl" value="'+socEsc(label||'')+'">'
                  + '<input type="text" placeholder="https://" class="su" value="'+socEsc(url||'')+'">'
                  + '<button type="button" class="soc-rm" onclick="this.parentElement.remove()">\u2715</button>';
    c.appendChild(row);
}
function saveSoc(cb){
    var rows=document.querySelectorAll('#soc-wrap .soc-row'), links=[], hasE=false;
    rows.forEach(function(row){
        var spEl=row.querySelector('.sp'), slEl=row.querySelector('.sl'), suEl=row.querySelector('.su');
        var p=spEl ? spEl.value.trim() : '';
        var l=slEl ? slEl.value.trim() : '';
        var u=suEl ? suEl.value.trim() : '';
        row.querySelectorAll('input,select').forEach(function(i){ i.classList.remove('serr'); });
        if(u){
            if(!isValidUrl(u)){
                if(suEl) suEl.classList.add('serr');
                hasE=true;
            } else {
                links.push({platform:p, label:l, url:u});
            }
        } else if(p){
            if(suEl) suEl.classList.add('serr');
            hasE=true;
        }
    });
    if(hasE){ toast('⚠ All links need valid URLs (e.g. https://twitter.com/you).','fail'); cb(false); return; }

    // Send in the same links[n][field] array format that modify-links.php uses —
    // avoids JSON parsing + sanitize_text_field URL corruption.
    var fd = new FormData();
    fd.append('guide_action', 'save_social');
    fd.append('guide_nonce',  NONCE);
    links.forEach(function(link, i){
        fd.append('links[' + i + '][platform]', link.platform);
        fd.append('links[' + i + '][label]',    link.label);
        fd.append('links[' + i + '][url]',      link.url);
    });
    fetch(SELF, {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(r){
            if(r && r.success){
                toast('✓ Social links saved!','ok');
                cb(true);
            } else {
                toast('⚠ ' + (r && r.data && r.data.message ? r.data.message : 'Error saving. Try again.'),'fail');
                cb(false);
            }
        })
        .catch(function(){ toast('⚠ Network error. Try again.','fail'); cb(false); });
}

/* ── Nav ── */
function wizNext(){
    var btn=document.getElementById('wiz-btn');
  if(btn && btn.dataset.blocked === '1'){
    if(step===1) valStep1();
    else if(step===2) valStep2();
    return;
  }

    if(step===1){
        if(!valStep1()) return;
        btn.disabled=true;
    saveInts(function(ok){
      btn.disabled=false;
      if(ok){
        awardPoints('interest_completed', 10);
        playStepCelebration(function(){ goStep(2); });
      }
    });
    } else if(step===2){
        if(!valStep2()) return;
        btn.disabled=true;
    saveBiz(function(ok){
      btn.disabled=false;
      if(ok){
        awardPoints('business_card_completed', 10);
        playStepCelebration(function(){ goStep(3); });
      }
    });
    } else if(step===3){
        btn.disabled=true;
    saveSoc(function(ok){
      btn.disabled=false;
      if(ok){
        awardPoints('social_links_completed', 10);
        playStepCelebration(function(){ goStep(4); });
      }
    });
    } else if(step===4){
    // Overlay shown on goStep(4); overlay button calls finishOnboarding()
    // This fallback handles any direct wizNext() call on step 4
    finishOnboarding();
    }
}

/* ── char counter ── */
document.addEventListener('DOMContentLoaded',function(){
  setSeated(SEATED_DEFAULT);

  // Mute button — stable toggle: cancel current speech, re-speak on unmute
  var muteBtn = document.getElementById('voice-mute-btn');
  if(muteBtn){
    muteBtn.addEventListener('click', function(){
      window.voiceMuted = !window.voiceMuted;
      muteBtn.textContent = window.voiceMuted ? '🔈 Unmute Voice' : '🔇 Mute Voice';
      if(window.speechSynthesis){
        try { window.speechSynthesis.cancel(); } catch(e){}
      }
      // Debounce: clear any pending speak timers
      if(window._speakTimer){ clearTimeout(window._speakTimer); window._speakTimer = null; }
      // Re-speak current screen content when unmuting
      if(!window.voiceMuted){
        var introEl = document.getElementById('s-intro');
        var wizEl   = document.getElementById('s-wizard');
        if(introEl && introEl.classList.contains('active') && selKey && GUIDES[selKey]){
          // Re-speak guide intro
          try {
            var ru = new SpeechSynthesisUtterance(GUIDES[selKey].intro);
            ru.lang = 'en-US'; ru.rate = 0.95;
            ru.pitch = (selKey === 'bella') ? 1.35 : 0.78;
            var rVoices = window.speechSynthesis.getVoices();
            if(rVoices && rVoices.length){
              var rv = pickVoiceForGuide(selKey, rVoices);
              if(rv){ ru.voice = rv; if(rv.lang) ru.lang = rv.lang; }
            }
            window.speechSynthesis.speak(ru);
          } catch(e){}
        } else if(wizEl && wizEl.classList.contains('active')){
          // Re-speak current wizard step hint
          speakStep(step);
        }
      }
    });
  }

  // Initialise tag-cloud chip inputs
  initTagCloud('kw-cloud','kw-chips','kw-input','f-kw', false);
  initTagCloud('ht-cloud','ht-chips','ht-input','f-ht', true);

  // Enforce step 1 completion if user has not filled interests
  var interestsRequired = <?php echo $has_interests ? 'false' : 'true'; ?>;
  if(interestsRequired){
    // Will be enforced in wizNext — no need to block pickGuide
    // Just ensure they cannot skip past step 1
  }

    var ab=document.getElementById('f-about');
    if(ab) ab.addEventListener('input',function(){
        document.getElementById('ab-ct').textContent=this.value.length;
    });

    if(Array.isArray(PREFILL_SOCIAL) && PREFILL_SOCIAL.length){
      PREFILL_SOCIAL.forEach(function(link){
        if(link && link.url){
          addSocRow(link.platform||'', link.label||'', link.url);
        }
      });
    }

    updateNextButtonState();

    // Show current points total once on page load (includes signup + first-login bonus)
    if(INIT_POINTS > 0){
      setTimeout(function(){
        var pop  = document.getElementById('pts-popup');
        var prev = document.getElementById('pts-prev');
        var add  = document.getElementById('pts-added');
        var lab  = document.getElementById('pts-label');
        var tot  = document.getElementById('pts-total');
        if(!pop) return;
        if(prev) prev.style.display = 'none';
        if(add)  add.textContent  = '\ud83c\udfc6 ' + INIT_POINTS + ' pts';
        if(lab)  lab.textContent  = 'Your Current Points';
        if(tot)  tot.textContent  = '+10 pts on each step!';
        pop.classList.remove('pts-show','pts-hide');
        void pop.offsetWidth;
        pop.classList.add('pts-show');
        if(window._ptsTimer) clearTimeout(window._ptsTimer);
        window._ptsTimer = setTimeout(function(){
          pop.classList.remove('pts-show');
          pop.classList.add('pts-hide');
          // Restore defaults for future award popups
          if(prev) prev.style.display = '';
          if(add)  add.textContent  = '+0 pts';
          if(lab)  lab.textContent  = '\ud83c\udfc6 Points Earned!';
          if(tot)  tot.textContent  = 'New Total: 0 pts';
        }, 4000);
      }, 1400);
    }

    document.addEventListener('change', function(e){
      if(step===1 && e.target && e.target.closest('#int-wrap')){
        updateNextButtonState();
      }
      if(step===2 && e.target && /^(f-title|f-about|f-addr|f-kw|f-ht)$/.test(e.target.id || '')){
        updateNextButtonState();
        // Clear stale validation error when user changes a field
        var errId = (e.target.id || '').replace('f-', 'e-');
        if(vv(e.target.id)) setE(e.target.id, errId, false);
      }
    });

    document.addEventListener('input', function(e){
      if(step===2 && e.target && /^(f-title|f-about|f-addr|f-kw|f-ht)$/.test(e.target.id || '')){
        updateNextButtonState();
        // Clear stale validation error as user types
        var errId = (e.target.id || '').replace('f-', 'e-');
        if(vv(e.target.id)) setE(e.target.id, errId, false);
      }
    });
});
</script>
<?php wp_footer(); ?>
</body>
</html>