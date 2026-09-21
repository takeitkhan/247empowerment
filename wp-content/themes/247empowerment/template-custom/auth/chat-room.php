<?php
/* Template Name: Chat Room */
/**
 * 24/7 Empowerment — Chat Room Page
 * Upload to: /wp-content/themes/247empowerment/template-custom/chat-room.php
 */

if ( ! defined( 'ABSPATH' ) ) {
  $path = dirname( __FILE__ );
  while ( $path !== '/' && ! file_exists( $path . '/wp-load.php' ) ) {
    $path = dirname( $path );
  }
  require_once $path . '/wp-load.php';
}

if ( ! is_user_logged_in() ) {
  wp_redirect( home_url( '/signin/' ) );
  exit;
}

add_filter( 'show_admin_bar', '__return_false' );

$uid       = get_current_user_id();
$user      = wp_get_current_user();
$user_name = $user->display_name ?: $user->user_login ?: 'Member';
$user_slug = $user->user_nicename ?: $user->user_login;
$user_init = strtoupper( substr( $user_name, 0, 1 ) );

$BG            = 'https://personalempowermentteams.me/wp-content/uploads/2026/03/unnamed.jpg';
$profile_url   = home_url( '/modify-profile/' );
$guide_url     = home_url( '/guide/' );
$ai_chat_url   = home_url( '/chat-with-ai/' );
$chat_room_url = get_permalink();

if ( class_exists( 'UserProfileData' ) ) {
    $profile_data = UserProfileData::getInstance();
    if ( $profile_data ) {
        $profile_url = $profile_data->getProfileUrl();
    }
}

// mm-portal-api REST (browser: WP cookie + X-WP-Nonce)
$portal_api_root  = esc_url_raw( untrailingslashit( rest_url( 'mm/v1' ) ) );
$portal_api_nonce = wp_create_nonce( 'wp_rest' );

$mm_ai_chat_active = (bool) get_option( 'mm_ai_chat_enabled' );
$ai_rest_url       = $mm_ai_chat_active ? esc_url_raw( untrailingslashit( rest_url( 'mm-ai-chat/v1' ) ) ) : '';
$ai_rest_nonce     = $mm_ai_chat_active ? wp_create_nonce( 'wp_rest' ) : '';
$ai_dir            = WP_PLUGIN_DIR . '/mm-ai-chat';

if ( $mm_ai_chat_active && is_dir( $ai_dir ) ) {
    if ( ! defined( 'MM_AI_CHAT_PLUGIN_URL' ) ) {
        define( 'MM_AI_CHAT_PLUGIN_URL', plugins_url( '/', $ai_dir . '/mm-ai-chat.php' ) );
    }
    if ( ! defined( 'MM_AI_CHAT_VERSION' ) ) {
        define( 'MM_AI_CHAT_VERSION', '1.0.1' );
    }
    if ( ! class_exists( 'MM_AI_Chat_Enqueue' ) ) {
        require_once $ai_dir . '/public/class-enqueue.php';
    }
    if ( class_exists( 'MM_AI_Chat_Enqueue' ) ) {
        MM_AI_Chat_Enqueue::enqueue_styles();
        MM_AI_Chat_Enqueue::enqueue_scripts();
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat Room – <?php bloginfo( 'name' ); ?></title>
<?php wp_head(); ?>
<style>
/* ══════════════════════════════════════
   RESET & BASE
══════════════════════════════════════ */
#room-root, #room-root *, #room-root *::before, #room-root *::after {
  box-sizing: border-box;
}
html, body { width: 100%; height: 100%; overflow: hidden; margin: 0; padding: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

/* Theme portal palette */
:root {
  --mm-primary: #05489C;
  --mm-primary-light: #1A5DB1;
  --mm-portal-bg: #F8FAFC;
  --mm-white: #ffffff;
  --mm-black: #2E2E2E;
  --mm-gray: #66676b;
  --mm-neutral: #BEC9E5;
  --mm-neutral-bg: rgba(190, 201, 229, 0.1);
  --mm-text-muted: #A0ABC7;
  --mm-danger: #de3f4f;
  --mm-success: #01A701;
}
#room-root {
  position: fixed;
  inset: 0;
  z-index: 9999;
  overflow: hidden;
  background: var(--mm-portal-bg);
  font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

#room-bg {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(rgba(248, 250, 252, .92), rgba(248, 250, 252, .96)),
    url('<?php echo esc_url($BG); ?>') center/cover no-repeat;
  z-index: 0;
  opacity: .35;
  pointer-events: none;
}

#mm-action-buttons-group { display: none !important; }

#room-main {
  position: relative;
  z-index: 5;
  display: flex;
  height: 100vh;
  overflow: hidden;
  padding: 12px;
  gap: 12px;
}

/* ── LEFT SIDEBAR ── */
#sidebar {
  width: 280px;
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
  overflow-y: auto;
  scrollbar-width: thin;
}
.cr-sidebar-block {
  background: var(--mm-white);
  border: 1px solid var(--mm-neutral);
  border-radius: 8px;
  padding: 14px 16px;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.cr-sidebar-name {
  font-size: 1rem;
  font-weight: 800;
  color: var(--mm-black);
  margin: 0 0 8px;
  line-height: 1.3;
}
.cr-profile-link {
  display: inline-block;
  background: var(--mm-primary);
  color: #fff !important;
  border-radius: 6px;
  padding: 6px 14px;
  font-size: .78rem;
  font-weight: 700;
  text-decoration: none;
  transition: background .2s;
}
.cr-profile-link:hover { background: var(--mm-primary-light); color: #fff; }
.cr-section-title {
  font-size: .95rem;
  font-weight: 800;
  color: var(--mm-black);
  margin: 0 0 10px;
}
.cr-search-input {
  width: 100%;
  border: 1px solid var(--mm-neutral);
  border-radius: 6px;
  padding: 9px 12px;
  font-size: .84rem;
  font-family: inherit;
  color: var(--mm-black);
  background: var(--mm-neutral-bg);
  outline: none;
  transition: border-color .2s, box-shadow .2s;
}
.cr-search-input:focus {
  border-color: var(--mm-primary);
  box-shadow: 0 0 0 2px rgba(5,72,156,.15);
  background: var(--mm-white);
}
.cr-search-input::placeholder { color: var(--mm-text-muted); }
.cr-menu-list { display: flex; flex-direction: column; gap: 4px; }
.cr-menu-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: .86rem;
  font-weight: 500;
  color: var(--mm-black);
  transition: background .18s, color .18s;
}
.cr-menu-item:hover {
  background: var(--mm-neutral-bg);
  color: var(--mm-primary);
}
.cr-menu-item.active {
  background: var(--mm-primary);
  color: var(--mm-white);
}
.cr-unread-pill {
  margin-left: auto;
  background: var(--mm-danger);
  color: #fff;
  border-radius: 10px;
  padding: 1px 7px;
  font-size: .65rem;
  font-weight: 800;
  line-height: 1.4;
}
#sidebar .menu-edit-profile-menu {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
#sidebar .menu-edit-profile-menu .nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 4px;
  transition: background .18s;
}
#sidebar .menu-edit-profile-menu .nav-item:hover { background: var(--mm-neutral-bg); }
#sidebar .menu-edit-profile-menu .nav-item.active-menu { background: var(--mm-neutral-bg); }
#sidebar .menu-edit-profile-menu .nav-item a {
  color: var(--mm-black);
  text-decoration: none;
  font-size: .86rem;
  font-weight: 500;
  padding: 0;
}
#sidebar .menu-edit-profile-menu .nav-item a:hover { color: var(--mm-primary); }
#sidebar .menu-edit-profile-menu .icon-img {
  width: 22px;
  height: 22px;
  object-fit: contain;
  flex-shrink: 0;
}

/* ── MAIN CONTENT ── */
#content-area {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  background: var(--mm-white);
  border: 1px solid var(--mm-neutral);
  border-radius: 8px;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}

/* Floating action bubbles */
.cr-float-group {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9990;
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: flex-end;
}
.cr-float-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--mm-primary);
  color: #fff;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(5,72,156,.3);
  transition: transform .2s, box-shadow .2s;
  position: relative;
}
.cr-float-btn:hover { transform: scale(1.06); color: #fff; }
.cr-float-btn.cr-float-guide { background: #01A701; box-shadow: 0 4px 12px rgba(1,167,1,.3); }
.cr-float-btn.cr-float-ai {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
.cr-float-btn.cr-float-active { box-shadow: 0 4px 16px rgba(5,72,156,.45); }
.cr-float-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 10px;
  background: var(--mm-danger);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* ── PANEL header ── */
.panel-header {
  padding: 14px 20px 10px;
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}
.panel-title {
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--mm-black);
}
.panel-count {
  background: var(--mm-primary);
  color: var(--mm-white);
  border-radius: 50px;
  padding: 3px 10px;
  font-size: .68rem;
  font-weight: 700;
}

/* ── MEMBER GRID (scoped — avoids theme CSS clashes) ── */
#room-root #tab-portal,
#room-root #tab-referral,
#room-root #tab-recent {
  flex: 1;
  min-height: 0;
  overflow: hidden;
  flex-direction: column;
}
#room-root #users-grid {
  flex: 1;
  overflow-y: auto;
  padding: 14px 20px 10px;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 18px;
  align-content: start;
  align-items: stretch;
  scrollbar-width: thin;
  scrollbar-color: rgba(200,169,110,.4) transparent;
}
#room-root #users-load-more-wrap {
  flex-shrink: 0;
  padding: 8px 20px 16px;
  text-align: center;
}
#room-root .mm-load-more {
  border: 1px solid var(--mm-primary);
  background: var(--mm-white);
  color: var(--mm-primary);
  font-weight: 800;
  font-size: .82rem;
  padding: 10px 22px;
  border-radius: 22px;
  cursor: pointer;
  font-family: inherit;
  transition: all .2s;
}
#room-root .mm-load-more:hover {
  background: var(--mm-primary);
  color: var(--mm-white);
  transform: translateY(-1px);
}
#room-root .mm-load-more:disabled { opacity: .6; cursor: wait; }

#room-root .mm-member-card {
  background: var(--mm-white);
  border-radius: 8px;
  padding: 0;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  height: 228px;
  cursor: pointer;
  border: 1px solid var(--mm-neutral);
  transition: border-color .2s, transform .2s, box-shadow .2s;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
  overflow: hidden;
  text-align: center;
}
#room-root .mm-member-card:hover {
  border-color: var(--mm-primary);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(5,72,156,.12);
}
#room-root .mm-member-card__avatar-wrap {
  flex-shrink: 0;
  padding: 14px 12px 8px;
  display: flex;
  justify-content: center;
}
#room-root .mm-member-avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: 2px solid var(--mm-neutral);
  object-fit: cover;
  background: #d4b87a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  font-weight: 900;
  color: #fff;
  flex-shrink: 0;
  line-height: 1;
}
#room-root .mm-member-card__body {
  flex: 1;
  min-height: 0;
  padding: 0 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  gap: 3px;
  overflow: hidden;
}
#room-root h3.mm-member-card__name {
  margin: 0 !important;
  padding: 0 !important;
  width: 100%;
  font-size: .86rem !important;
  font-weight: 800 !important;
  color: var(--mm-black) !important;
  line-height: 1.2 !important;
  overflow: hidden;
  display: -webkit-box !important;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  word-break: break-word;
}
#room-root .mm-member-card__user {
  margin: 0;
  padding: 0;
  width: 100%;
  font-size: .66rem;
  font-weight: 600;
  color: var(--mm-text-muted);
  line-height: 1.2;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
#room-root .mm-member-card__tagline {
  margin: 4px 0 0;
  padding: 4px 10px;
  width: 100%;
  max-width: 100%;
  font-size: .62rem;
  font-weight: 700;
  color: var(--mm-primary);
  line-height: 1.25;
  background: var(--mm-neutral-bg);
  border: 1px solid var(--mm-neutral);
  border-radius: 999px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  box-sizing: border-box;
}
#room-root .mm-member-card__footer {
  flex-shrink: 0;
  padding: 10px 12px 12px;
  border-top: 1px solid var(--mm-neutral);
  background: var(--mm-neutral-bg);
}
#room-root .mm-member-card__cta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  width: 100%;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: .74rem;
  font-weight: 700;
  color: #fff;
  background: var(--mm-primary);
  box-shadow: none;
  line-height: 1;
}
#room-root .mm-member-card:hover .mm-member-card__cta {
  background: var(--mm-primary-light);
}

/* ══════════════════════════════════════
   RECENT CHATS PANEL
══════════════════════════════════════ */
#recent-panel {
  flex: 1;
  overflow-y: auto;
  padding: 6px 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  scrollbar-width: thin;
  scrollbar-color: var(--mm-neutral) transparent;
}
.recent-item {
  background: var(--mm-white);
  border-radius: 8px;
  padding: 12px 14px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  border: 1px solid var(--mm-neutral);
  transition: all .2s;
  box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.recent-item:hover {
  border-color: var(--mm-primary);
  transform: translateX(3px);
  background: var(--mm-white);
}
.ri-avatar-wrap { position: relative; flex-shrink: 0; }
.ri-avatar {
  width: 46px; height: 46px; border-radius: 50%;
  object-fit: cover; border: 2px solid var(--mm-neutral);
}
.ri-avatar-init {
  width: 46px; height: 46px; border-radius: 50%;
  background: var(--mm-primary);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; font-weight: 900; color: #fff;
}
.ri-dot { position: absolute; bottom: 1px; right: 1px;
  width: 11px; height: 11px; border-radius: 50%;
  background: var(--mm-success); border: 2px solid var(--mm-white); }
.ri-dot.offline { background: #aaa; }
.ri-info { flex: 1; min-width: 0; }
.ri-name { font-size: .85rem; font-weight: 800; color: var(--mm-black); }
.ri-preview { font-size: .73rem; color: var(--mm-gray); white-space: nowrap;
  overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
.ri-meta { text-align: right; flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 4px; }
.ri-time { font-size: .62rem; color: var(--mm-text-muted); font-weight: 600; }
.ri-unread {
  background: var(--mm-danger); color: #fff; border-radius: 10px;
  padding: 1px 7px; font-size: .6rem; font-weight: 800;
}

/* ══════════════════════════════════════
   REFERRAL PANEL
══════════════════════════════════════ */
#referral-panel {
  flex: 1;
  overflow-y: auto;
  padding: 6px 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  scrollbar-width: thin;
  scrollbar-color: var(--mm-neutral) transparent;
}
.referral-banner {
  background: var(--mm-primary);
  border-radius: 8px;
  padding: 18px 20px;
  color: var(--mm-white);
  border: none;
}
.referral-banner h3 { font-size: 1rem; font-weight: 900; margin-bottom: 6px; }
.referral-banner p { font-size: .78rem; line-height: 1.6; opacity: .88; }
.ref-code-box {
  background: var(--mm-white);
  border-radius: 8px;
  padding: 10px 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 10px;
  border: none;
}
.ref-code { font-size: .9rem; font-weight: 900; color: var(--mm-primary); letter-spacing: .08em; }
.ref-copy {
  background: var(--mm-primary-light);
  color: #fff; border: none; border-radius: 6px;
  padding: 5px 12px; font-size: .7rem; font-weight: 700;
  cursor: pointer; font-family: inherit;
}
.ref-copy:hover { opacity: .85; }

.ref-list-title {
  font-size: .75rem; font-weight: 800; color: var(--mm-black);
  text-transform: uppercase; letter-spacing: .08em;
  padding: 4px 0 0;
}
.ref-item {
  background: var(--mm-white);
  border-radius: 8px;
  padding: 11px 14px;
  display: flex;
  align-items: center;
  gap: 11px;
  box-shadow: 0 1px 4px rgba(0,0,0,.05);
  border: 1px solid var(--mm-neutral);
  transition: all .2s;
}
.ref-item:hover { border-color: var(--mm-primary); }
.ref-status {
  padding: 3px 9px;
  border-radius: 10px;
  font-size: .62rem;
  font-weight: 800;
  margin-left: auto;
  white-space: nowrap;
}
.ref-status.active { background: rgba(5,72,156,.1); color: var(--mm-primary); border: 1px solid var(--mm-primary); }
.ref-status.pending { background: var(--mm-neutral-bg); color: var(--mm-gray); border: 1px solid var(--mm-neutral); }
.ref-earnings { font-size: .7rem; font-weight: 700; color: var(--mm-gray); }

/* ══════════════════════════════════════
   CHAT MODAL OVERLAY
══════════════════════════════════════ */
#chat-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: rgba(46,46,46,.45);
  backdrop-filter: blur(4px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  animation: fadeIn .25s ease;
}
#chat-modal-overlay.open { display: flex; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

/* Modal box */
#chat-modal {
  background: var(--mm-white);
  border-radius: 8px;
  width: 100%;
  max-width: 640px;
  height: 82vh;
  max-height: 700px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 16px 48px rgba(0,0,0,.18);
  border: 1px solid var(--mm-neutral);
  overflow: hidden;
  animation: modalIn .3s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes modalIn {
  from { opacity: 0; transform: scale(.9) translateY(20px); }
  to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* Modal header */
.modal-header {
  background: var(--mm-primary);
  padding: 14px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
  border-bottom: none;
}
.mh-avatar-wrap { position: relative; flex-shrink: 0; }
.mh-avatar {
  width: 42px; height: 42px; border-radius: 50%;
  object-fit: cover; border: 2px solid rgba(255,255,255,.5);
}
.mh-avatar-init {
  width: 42px; height: 42px; border-radius: 50%;
  background: var(--mm-primary-light);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; font-weight: 900; color: #fff;
  border: 2px solid rgba(255,255,255,.5);
}
.mh-online { position: absolute; bottom: 1px; right: 1px;
  width: 11px; height: 11px; border-radius: 50%;
  background: var(--mm-success); border: 2px solid var(--mm-primary); }
.mh-info { flex: 1; }
.mh-name { font-size: .95rem; font-weight: 900; color: #fff; }
.mh-status { font-size: .68rem; color: rgba(255,255,255,.8); margin-top: 1px; }
.mh-actions { display: flex; gap: 6px; }
.mh-btn {
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.3);
  color: #fff;
  border-radius: 7px;
  width: 32px; height: 32px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: .9rem;
  transition: background .2s;
}
.mh-btn:hover { background: rgba(255,255,255,.22); }
.mh-close {
  background: rgba(192,57,43,.8); border: none;
  color: #fff; border-radius: 7px;
  width: 32px; height: 32px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; font-size: 1rem; font-weight: 700;
  transition: background .2s;
}
.mh-close:hover { background: rgba(169,50,38,1); }

/* Modal messages */
#modal-messages {
  flex: 1;
  overflow-y: auto;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: var(--mm-portal-bg);
  scrollbar-width: thin;
  scrollbar-color: var(--mm-neutral) transparent;
}
.mm-date-sep {
  text-align: center;
  font-size: .62rem;
  color: var(--mm-text-muted);
  font-weight: 700;
  padding: 4px 0;
  position: relative;
}
.mm-date-sep::before {
  content: '';
  position: absolute;
  top: 50%; left: 0; right: 0;
  height: 1px;
  background: var(--mm-neutral);
  z-index: 0;
}
.mm-date-sep span {
  position: relative; z-index: 1;
  background: var(--mm-portal-bg);
  padding: 0 10px;
}

.mm { display: flex; gap: 8px; align-items: flex-end; }
.mm.me { flex-direction: row-reverse; }
.mm-avatar {
  width: 28px; height: 28px; border-radius: 50%;
  object-fit: cover; flex-shrink: 0;
  border: 1.5px solid var(--mm-neutral);
}
.mm-avatar-init {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--mm-primary);
  display: flex; align-items: center; justify-content: center;
  font-size: .65rem; font-weight: 800; color: #fff;
  flex-shrink: 0;
}
.mm-body { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.mm.me .mm-body { align-items: flex-end; }
.mm-bubble {
  display: inline-block;
  max-width: 75%;
  padding: 9px 13px;
  border-radius: 12px 12px 12px 4px;
  font-size: .84rem;
  line-height: 1.55;
  background: var(--mm-white);
  color: var(--mm-black);
  font-weight: 500;
  border: 1px solid var(--mm-neutral);
  box-shadow: 0 1px 3px rgba(0,0,0,.05);
  word-break: break-word;
  white-space: pre-wrap;
  animation: msgPop .22s ease both;
}
@keyframes msgPop {
  from { opacity: 0; transform: translateY(8px) scale(.96); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}
.mm.me .mm-bubble {
  background: var(--mm-primary);
  color: #fff;
  border-color: var(--mm-primary);
  border-radius: 12px 12px 4px 12px;
}
.mm-time {
  font-size: .56rem; color: var(--mm-text-muted);
  margin-top: 3px; font-weight: 600;
}
.mm.me .mm-time { text-align: right; }

/* Typing dots */
.mm-typing .mm-bubble {
  background: rgba(255,255,255,.88);
  padding: 10px 14px;
}
.tdots { display: flex; gap: 4px; align-items: center; }
.tdots span {
  width: 5px; height: 5px; border-radius: 50%;
  background: var(--mm-primary); display: inline-block;
  animation: tdot 1.2s ease-in-out infinite;
}
.tdots span:nth-child(2) { animation-delay: .2s; }
.tdots span:nth-child(3) { animation-delay: .4s; }
@keyframes tdot {
  0%,60%,100% { transform: translateY(0); opacity: .5; }
  30% { transform: translateY(-5px); opacity: 1; }
}

/* Modal input — matches theme .input / .input-message */
#chat-modal .modal-input-area {
  flex-shrink: 0;
  padding: 12px 14px;
  border-top: 1px solid var(--mm-neutral);
  background: var(--mm-white);
  display: flex !important;
  flex-direction: row !important;
  gap: 10px;
  align-items: flex-end;
  position: relative;
}
#chat-modal .modal-input-area > * {
  float: none !important;
}
#chat-modal .modal-compose {
  flex: 1;
  min-width: 0;
  position: relative;
  display: block;
  background: var(--mm-neutral-bg);
  border: 1px solid var(--mm-neutral);
  border-radius: 4px;
  transition: border-color .2s, box-shadow .2s, background .2s;
}
#chat-modal .modal-compose:focus-within {
  background: var(--mm-white);
  border-color: var(--mm-neutral);
  box-shadow: 0 0 0 2px var(--mm-primary);
}
#chat-modal .modal-emoji {
  position: absolute !important;
  left: 4px;
  bottom: 4px;
  z-index: 2;
  width: 34px !important;
  height: 34px !important;
  min-width: 34px !important;
  margin: 0 !important;
  border: none !important;
  border-radius: 4px;
  background: transparent !important;
  cursor: pointer;
  display: flex !important;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  line-height: 1;
  padding: 0 !important;
  transition: background .15s;
}
#chat-modal .modal-emoji:hover { background: var(--mm-neutral-bg) !important; }
#chat-modal #modal-input {
  display: block !important;
  width: 100% !important;
  box-sizing: border-box !important;
  border: none !important;
  border-radius: 4px !important;
  padding: 10px 12px 10px 44px !important;
  font-size: .84rem;
  font-family: inherit;
  background: transparent !important;
  color: var(--mm-black) !important;
  resize: none;
  outline: none !important;
  box-shadow: none !important;
  min-height: 42px;
  max-height: 110px;
  line-height: 1.45;
  position: relative;
  z-index: 1;
}
#chat-modal #modal-input::placeholder {
  color: var(--mm-text-muted) !important;
}
#chat-modal .modal-send {
  background: var(--mm-primary);
  border: none;
  border-radius: 6px;
  width: 40px;
  height: 40px;
  min-width: 40px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 1rem;
  transition: background .2s, transform .15s;
  flex-shrink: 0;
  padding: 0;
}
#chat-modal .modal-send:hover { background: var(--mm-primary-light); transform: scale(1.04); }
#chat-modal .modal-send:disabled { opacity: .45; cursor: not-allowed; transform: none; }

/* Emoji picker */
#chat-modal .emoji-picker {
  position: absolute;
  bottom: calc(100% + 6px);
  left: 14px;
  background: var(--mm-white);
  border: 1px solid var(--mm-neutral);
  border-radius: 8px;
  padding: 10px;
  display: none;
  flex-wrap: wrap;
  gap: 4px;
  width: 220px;
  box-shadow: 0 8px 24px rgba(0,0,0,.12);
  z-index: 10;
}
#chat-modal .emoji-picker.open { display: flex; }
#chat-modal .ep-btn {
  width: 32px; height: 32px; border: none;
  background: none; cursor: pointer; font-size: 1.1rem;
  border-radius: 4px; display: flex; align-items: center; justify-content: center;
  transition: background .15s;
}
#chat-modal .ep-btn:hover { background: var(--mm-neutral-bg); }

/* ══════════════════════════════════════
   TOAST
══════════════════════════════════════ */
#room-toast {
  position: fixed; bottom: 22px; left: 50%;
  transform: translateX(-50%) translateY(80px);
  background: #1a0e05; color: #fff;
  padding: 9px 20px; border-radius: 9px;
  font-size: .82rem; font-weight: 700;
  z-index: 99999; transition: transform .3s;
  white-space: nowrap; box-shadow: 0 4px 18px rgba(0,0,0,.4);
  pointer-events: none;
}
#room-toast.show { transform: translateX(-50%) translateY(0); }
#room-toast.ok   { background: var(--mm-success); }
#room-toast.fail { background: var(--mm-danger); }

/* ══════════════════════════════════════
   EMPTY STATES
══════════════════════════════════════ */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 40px 20px;
  color: var(--mm-text-muted);
}
.empty-state .es-icon { font-size: 2.5rem; opacity: .6; }
.empty-state .es-text { font-size: .85rem; font-weight: 600; text-align: center; line-height: 1.6; }

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 900px) {
  #room-main { flex-direction: column; height: auto; min-height: 100vh; overflow: auto; }
  #sidebar { width: 100%; flex-direction: row; flex-wrap: wrap; overflow: visible; }
  .cr-sidebar-block { flex: 1 1 220px; }
  #content-area { min-height: 60vh; }
}
@media (max-width: 680px) {
  #room-root #users-grid { grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 12px; padding: 10px 12px; }
  #room-root .mm-member-card { height: 218px; }
  #chat-modal { height: 94vh; max-height: none; }
  .cr-float-group { bottom: 14px; right: 14px; }
  .cr-float-btn { width: 48px; height: 48px; font-size: 18px; }
}
</style>
</head>
<body>
<div id="room-root">
  <div id="room-bg"></div>

  <div id="room-main">

    <!-- LEFT SIDEBAR -->
    <div id="sidebar">

      <div class="cr-sidebar-block">
        <h5 class="cr-sidebar-name"><?php echo esc_html( $user_name ); ?></h5>
        <a href="<?php echo esc_url( $profile_url ); ?>" class="cr-profile-link">Back to Profile</a>
      </div>

      <div class="cr-sidebar-block">
        <h5 class="cr-section-title">Search Members</h5>
        <input type="text" id="search-input" class="cr-search-input" placeholder="Search members…" oninput="filterUsers(this.value)">
      </div>

      <div class="cr-sidebar-block">
        <h5 class="cr-section-title">Chat Menu</h5>
        <div class="cr-menu-list">
          <div class="cr-menu-item active" id="sb-portal" onclick="switchTab('portal', this)">Members</div>
          <div class="cr-menu-item" id="sb-referral" onclick="switchTab('referral', this)">Referral Partners</div>
          <div class="cr-menu-item" id="sb-recent" onclick="switchTab('recent', this)">
            Chat History
            <span class="cr-unread-pill" id="unread-badge" style="display:none">0</span>
          </div>
        </div>
      </div>

      <div class="cr-sidebar-block">
        <h5 class="cr-section-title">Main Menu</h5>
        <?php
        $cr_fix_main_menu_urls = function ( $items ) use ( $user_slug ) {
            foreach ( $items as $item ) {
                $item->url = str_replace( '$username', $user_slug, $item->url );
                $item->url = str_replace( '/username/', '/' . $user_slug . '/', $item->url );
            }
            return $items;
        };
        add_filter( 'wp_nav_menu_objects', $cr_fix_main_menu_urls, 10, 1 );
        wp_nav_menu( [
            'theme_location' => 'portalmenu',
            'container'      => false,
            'menu_class'     => 'nav d-flex flex-column gap-2 menu-edit-profile-menu',
            'walker'         => new Profile_Menu_Walker(),
            'fallback_cb'    => false,
        ] );
        remove_filter( 'wp_nav_menu_objects', $cr_fix_main_menu_urls, 10 );
        ?>
      </div>

    </div><!-- /sidebar -->

    <!-- MAIN CONTENT -->
    <div id="content-area">

      <!-- MEMBERS TAB -->
      <div id="tab-portal">
        <div class="panel-header">
          <div class="panel-title">Members</div>
          <div class="panel-count" id="user-count">0 members</div>
        </div>
        <div id="users-grid"></div>
        <div id="users-load-more-wrap"></div>
      </div>

      <!-- REFERRAL TAB -->
      <div id="tab-referral" style="display:none;">
        <div class="panel-header">
          <div class="panel-title">Referral Partners</div>
          <div class="panel-count">Your Network</div>
        </div>
        <div id="referral-panel">
          <div class="referral-banner">
            <h3>🎉 Referral Appreciation Program</h3>
            <p>Earn up to <strong>$600</strong> when someone you refer enrolls in an eligible paid program and remains beyond the refund period. Share your unique code below!</p>
            <div class="ref-code-box">
              <span class="ref-code" id="ref-code-display">EMPOWER-<?php echo strtoupper(substr(md5($uid), 0, 6)); ?></span>
              <button class="ref-copy" onclick="copyRefCode()">Copy Code</button>
            </div>
          </div>
          <div class="ref-list-title">Your Referrals</div>
          <div id="ref-list"></div>
        </div>
      </div>

      <!-- CHAT HISTORY TAB -->
      <div id="tab-recent" style="display:none;">
        <div class="panel-header">
          <div class="panel-title">Chat History</div>
          <div class="panel-count" id="recent-count">0 conversations</div>
        </div>
        <div id="recent-panel"></div>
      </div>

    </div><!-- /content-area -->
  </div><!-- /room-main -->
</div><!-- /room-root -->

<!-- Floating bubbles -->
<div class="cr-float-group" aria-label="Quick actions">
  <a href="<?php echo esc_url( $chat_room_url ); ?>" class="cr-float-btn cr-float-active" title="Chat Room" aria-label="Chat Room">
    💬
    <span class="cr-float-badge" id="cr-float-chat-badge" style="display:none">0</span>
  </a>
  <!-- <button type="button" class="cr-float-btn cr-float-guide" title="Coming soon" aria-label="Coming soon">📚</button> -->
  <button type="button" class="cr-float-btn cr-float-ai" id="cr-float-ai-btn" title="AI Support" aria-label="AI Support">🤖</button>
</div>

<!-- CHAT MODAL -->
<div id="chat-modal-overlay" onclick="handleOverlayClick(event)">
  <div id="chat-modal">
    <div class="modal-header">
      <div class="mh-avatar-wrap">
        <div class="mh-avatar-init" id="mh-avatar-init"></div>
        <div class="mh-online" id="mh-online-dot"></div>
      </div>
      <div class="mh-info">
        <div class="mh-name" id="mh-name">User</div>
        <div class="mh-status" id="mh-status">Online</div>
      </div>
      <div class="mh-actions">
        <button class="mh-btn" title="Voice call" onclick="toast('📞 Voice call coming soon!','ok')">📞</button>
        <button class="mh-btn" title="View profile" onclick="viewProfile()">👤</button>
        <button class="mh-close" onclick="closeModal()">✕</button>
      </div>
    </div>

    <div id="modal-messages"></div>

    <div class="modal-input-area">
      <div class="modal-compose">
        <button type="button" class="modal-emoji" onclick="toggleEmoji()" title="Emoji" aria-label="Insert emoji">😊</button>
        <textarea id="modal-input"
          placeholder="Type a message…"
          rows="1"
          onkeydown="handleModalKey(event)"
          oninput="autoResizeModal(this)"></textarea>
      </div>
      <div class="emoji-picker" id="emoji-picker"></div>
      <button type="button" class="modal-send" id="modal-send-btn" onclick="sendModalMessage()" aria-label="Send message">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      </button>
    </div>
  </div>
</div>

<div id="room-toast"></div>

<script>
/* ══════════════════════════════════════
   CONFIG & STATE (mm-portal-api)
══════════════════════════════════════ */
var CURRENT_USER = {
  id: <?php echo (int)$uid; ?>,
  name: '<?php echo esc_js($user_name); ?>',
  initials: '<?php echo esc_js($user_init); ?>'
};

var PROF_URL = '<?php echo esc_js($profile_url); ?>';

var API = {
  root: '<?php echo esc_js( $portal_api_root ); ?>/',
  nonce: '<?php echo esc_js( $portal_api_nonce ); ?>'
};

var AI_CHAT = {
  restUrl: '<?php echo esc_js( $ai_rest_url ); ?>',
  nonce: '<?php echo esc_js( $ai_rest_nonce ); ?>',
  enabled: <?php echo $mm_ai_chat_active ? 'true' : 'false'; ?>
};
var aiConversationId = null;
var aiWaiting = false;
var aiInitializing = false;
var aiFallbackReady = false;

var USERS = [];
var MY_REFERRALS = [];
var RECENT_CHATS = [];
var userCache = {};

var currentTab = 'portal';
var activeChatId = null;
var activeConversationId = null;
var lastMessageId = 0;
var pollToken = 0;
var renderedMessageIds = {};
var emojiOpen = false;
var filteredUsers = [];
var usersLoading = false;
var usersPage = 1;
var usersTotal = 0;
var usersPerPage = 36;
var usersSearchQuery = '';
var searchDebounce = null;
var globalUnreadCount = 0;

var EMOJIS = ['😀','😂','🥰','😊','🙌','👏','🔥','💪','🚀','💰','🏆','✅','❤️','🎉','💡','👍','🤝','😎','🌟','💼'];

var AVATAR_COLORS = [
  ['#05489C','#1A5DB1'], ['#0c53ac','#3B82F6'], ['#052246','#1A5DB1'],
  ['#05489C','#6ea8c8'], ['#0c3e80','#8BA7F2'], ['#05489C','#BEC9E5'],
  ['#1A5DB1','#3B82F6'], ['#052246','#05489C'],
];
function avatarColor(id){ return AVATAR_COLORS[id % AVATAR_COLORS.length]; }

/* ══════════════════════════════════════
   API HELPERS
══════════════════════════════════════ */
function apiUrl(path, query){
  var url = API.root + String(path || '').replace(/^\//, '');
  if(query){
    var qs = Object.keys(query).map(function(k){
      if(query[k] === undefined || query[k] === null || query[k] === '') return '';
      return encodeURIComponent(k) + '=' + encodeURIComponent(query[k]);
    }).filter(Boolean).join('&');
    if(qs) url += (url.indexOf('?') >= 0 ? '&' : '?') + qs;
  }
  return url;
}

function apiFetch(path, options){
  options = options || {};
  var opts = {
    method: options.method || 'GET',
    credentials: 'same-origin',
    headers: {
      'X-WP-Nonce': API.nonce,
      'Accept': 'application/json'
    }
  };
  if(options.body !== undefined){
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(options.body);
  }
  return fetch(apiUrl(path, options.query), opts).then(function(res){
    return res.json().then(function(data){
      if(!res.ok){
        var msg = (data && data.message) ? data.message : ('Request failed (' + res.status + ')');
        if(data && data.code === 'rest_chat_backend_unavailable'){
          msg = 'Chat backend is not active. Please enable the mm-referral-chat plugin.';
        }
        throw new Error(msg);
      }
      return data;
    });
  });
}

function apiGet(path, query){ return apiFetch(path, { method: 'GET', query: query }); }
function apiPost(path, body){ return apiFetch(path, { method: 'POST', body: body }); }

function cacheUser(raw){
  var u = mapApiUser(raw);
  if(!u || !u.id) return null;
  userCache[u.id] = u;
  return u;
}

function decodeHtml(str){
  if(!str) return '';
  var el = document.createElement('textarea');
  el.innerHTML = str;
  return el.value;
}

function mapApiUser(raw){
  if(!raw) return null;
  var name = decodeHtml(raw.display_name || raw.username || 'Member');
  var interests = (raw.interests || []).map(function(i){
    var n = (typeof i === 'string') ? i : (i.name || '');
    return decodeHtml(n);
  }).filter(Boolean);
  return {
    id: raw.id,
    name: name,
    initials: initialsFromName(name),
    status: 'offline',
    interests: interests,
    avatar: raw.avatar_url || null,
    profileUrl: raw.profile_url || '',
    designation: decodeHtml(raw.designation || ''),
    joined: formatRegisteredDate(raw.registered_date),
    raw: raw
  };
}

function initialsFromName(name){
  var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
  if(parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  return String(name || '?').substring(0, 2).toUpperCase();
}

function formatRegisteredDate(iso){
  if(!iso) return '';
  var d = new Date(iso.replace(' ', 'T'));
  if(isNaN(d.getTime())) return '';
  return d.toLocaleDateString([], { month: 'short', year: 'numeric' });
}

function formatMessageTime(iso){
  if(!iso) return getTime();
  var d = new Date(iso.replace(' ', 'T'));
  if(isNaN(d.getTime())) return iso;
  var now = new Date();
  var sameDay = d.toDateString() === now.toDateString();
  if(sameDay) return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' +
    d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function getUserById(id){
  return userCache[id] || USERS.find(function(u){ return u.id === id; }) || null;
}

function avatarHtml(u, className, size){
  var colors = avatarColor(u.id);
  var box = 'width:' + size + 'px;height:' + size + 'px;border-radius:50%;flex-shrink:0;';
  var flex = className === 'ri-avatar-init'
    ? box + 'display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:900;color:#fff;'
    : box;
  if(u.avatar){
    return '<img class="' + className + '" src="' + escH(u.avatar) + '" alt="" style="' + flex + 'object-fit:cover;">';
  }
  return '<div class="' + className + '" style="' + flex + 'background:linear-gradient(135deg,' + colors[0] + ',' + colors[1] + ')">' + escH(u.initials) + '</div>';
}

function mapChatsFromApi(chats){
  return (chats || []).map(function(c){
    var other = c.other_user;
    if(other) cacheUser(other);
    var preview = c.last_message ? c.last_message.text : 'Start a conversation…';
    var timeSrc = c.last_message ? c.last_message.created_at : c.updated_at;
    return {
      chatId: c.id,
      userId: other ? other.id : null,
      preview: preview,
      time: formatMessageTime(timeSrc),
      unread: c.unread_count || 0,
      otherUser: other ? getUserById(other.id) : null
    };
  }).filter(function(rc){ return rc.userId; });
}

function updateUnreadBadge(count){
  count = parseInt(count, 10) || 0;
  globalUnreadCount = count;
  var label = count > 99 ? '99+' : String(count);
  var badge = document.getElementById('unread-badge');
  var floatBadge = document.getElementById('cr-float-chat-badge');
  [badge, floatBadge].forEach(function(el){
    if(!el) return;
    if(count > 0){
      el.textContent = label;
      el.style.display = '';
    } else {
      el.style.display = 'none';
    }
  });
}

/** Immediately clear red unread pills + sidebar/bubble counts for a chat. */
function clearUnreadForChat(userId, chatId){
  var cleared = 0;
  RECENT_CHATS.forEach(function(rc){
    var match = (userId && rc.userId === userId) || (chatId && rc.chatId === chatId);
    if(match && rc.unread){
      cleared += parseInt(rc.unread, 10) || 0;
      rc.unread = 0;
    }
  });
  if(cleared > 0){
    globalUnreadCount = Math.max(0, globalUnreadCount - cleared);
    updateUnreadBadge(globalUnreadCount);
    if(currentTab === 'recent') renderRecentChats();
  }
  return cleared;
}

/** Optimistic read + API sync (badge already updated by clearUnreadForChat). */
function markConversationRead(conversationId, userId){
  clearUnreadForChat(userId, conversationId);
  if(!conversationId) return Promise.resolve();
  return apiPost('chats/' + conversationId + '/read', {}).catch(function(){});
}

/* ══════════════════════════════════════
   DATA LOADERS
══════════════════════════════════════ */
function updateMemberCounts(){
  var total = usersTotal || USERS.length;
  var countEl = document.getElementById('user-count');
  if(countEl){
    if(USERS.length < total){
      countEl.textContent = 'Showing ' + USERS.length + ' of ' + total;
    } else {
      countEl.textContent = total + ' member' + (total !== 1 ? 's' : '');
    }
  }
}

function renderLoadMoreButton(){
  var wrap = document.getElementById('users-load-more-wrap');
  if(!wrap) return;
  if(USERS.length < usersTotal){
    wrap.innerHTML = '<button type="button" class="mm-load-more" id="btn-load-more" onclick="loadMoreUsers()">Load more (' +
      USERS.length + ' of ' + usersTotal + ')</button>';
  } else {
    wrap.innerHTML = '';
  }
}

function loadMoreUsers(){
  if(usersLoading || USERS.length >= usersTotal) return;
  usersPage++;
  loadPortalUsers(usersSearchQuery, true);
}

function loadPortalUsers(search, append){
  usersLoading = true;
  usersSearchQuery = search || '';
  if(!append) usersPage = 1;

  var grid = document.getElementById('users-grid');
  if(grid && currentTab === 'portal' && !append){
    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="es-text">Loading members…</div></div>';
  }
  var btn = document.getElementById('btn-load-more');
  if(btn) btn.disabled = true;

  return apiGet('users', {
    page: usersPage,
    per_page: usersPerPage,
    search: usersSearchQuery
  }).then(function(data){
    usersTotal = data.total != null ? parseInt(data.total, 10) : (data.users || []).length;
    var batch = (data.users || []).map(function(raw){
      return cacheUser(raw);
    }).filter(Boolean);

    if(append) USERS = USERS.concat(batch);
    else USERS = batch;

    filteredUsers = USERS.slice();
    updateMemberCounts();
    if(currentTab === 'portal') renderUsers(filteredUsers);
    renderLoadMoreButton();
  }).catch(function(err){
    if(!append && currentTab === 'portal'){
      document.getElementById('users-grid').innerHTML =
        '<div class="empty-state" style="grid-column:1/-1"><div class="es-icon">⚠️</div><div class="es-text">' + escH(err.message) + '</div></div>';
    }
    toast(err.message, 'fail');
  }).finally(function(){
    usersLoading = false;
    var b = document.getElementById('btn-load-more');
    if(b) b.disabled = false;
  });
}

function loadReferrals(){
  return apiGet('referrals').then(function(data){
    MY_REFERRALS = (data.users || []).map(function(raw){
      return cacheUser(raw);
    }).filter(Boolean);
    if(currentTab === 'referral') renderReferrals();
  }).catch(function(err){
    document.getElementById('ref-list').innerHTML =
      '<div class="empty-state"><div class="es-text">' + escH(err.message) + '</div></div>';
    toast(err.message, 'fail');
  });
}

function loadRecentChats(){
  return apiGet('chats', { page: 1, per_page: 50 }).then(function(data){
    RECENT_CHATS = mapChatsFromApi(data.chats);
    if(activeConversationId && document.getElementById('chat-modal-overlay').classList.contains('open')){
      RECENT_CHATS.forEach(function(rc){
        if(rc.chatId === activeConversationId || rc.userId === activeChatId) rc.unread = 0;
      });
    }
    if(currentTab === 'recent') renderRecentChats();
  }).catch(function(err){
    document.getElementById('recent-panel').innerHTML =
      '<div class="empty-state"><div class="es-icon">⚠️</div><div class="es-text">' + escH(err.message) + '</div></div>';
    toast(err.message, 'fail');
  });
}

function refreshUnreadCount(){
  return apiGet('chats/unread-count').then(function(data){
    updateUnreadBadge(data.unread_count);
  }).catch(function(){ /* silent */ });
}

/* ══════════════════════════════════════
   INIT
══════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function(){
  buildEmojiPicker();
  loadPortalUsers('');
  loadReferrals();
  loadRecentChats();
  refreshUnreadCount();
  setInterval(refreshUnreadCount, 30000);

  var aiBtn = document.getElementById('cr-float-ai-btn');
  if(aiBtn){
    aiBtn.addEventListener('click', function(e){
      e.preventDefault();
      openAiSupportChat();
    });
  }
});

/* ══════════════════════════════════════
   RENDER USERS GRID
══════════════════════════════════════ */
function memberAvatarMarkup(u){
  var colors = avatarColor(u.id);
  if(u.avatar){
    return '<img class="mm-member-avatar" src="' + escH(u.avatar) + '" alt="" loading="lazy">';
  }
  return '<div class="mm-member-avatar" style="background:linear-gradient(135deg,' + colors[0] + ',' + colors[1] + ')">' + escH(u.initials) + '</div>';
}

function memberTagline(u){
  if(u.interests && u.interests.length) return u.interests[0];
  if(u.designation) return u.designation;
  return 'Platform member';
}

function renderMemberCard(u){
  var meta = u.raw && u.raw.username ? '@' + escH(u.raw.username) : '';
  var tagFull = memberTagline(u);
  var tagDisplay = escH(tagFull);
  return '<article class="mm-member-card" onclick="openChat(' + u.id + ')" role="button" tabindex="0" aria-label="Chat with ' + escH(u.name) + '">'
    + '<div class="mm-member-card__avatar-wrap">' + memberAvatarMarkup(u) + '</div>'
    + '<div class="mm-member-card__body">'
    +   '<h3 class="mm-member-card__name">' + escH(u.name) + '</h3>'
    +   (meta ? '<p class="mm-member-card__user">' + meta + '</p>' : '')
    +   '<p class="mm-member-card__tagline" title="' + tagDisplay + '">' + tagDisplay + '</p>'
    + '</div>'
    + '<div class="mm-member-card__footer">'
    +   '<span class="mm-member-card__cta">💬 Start Chat</span>'
    + '</div>'
    + '</article>';
}

function renderUsers(list){
  var grid = document.getElementById('users-grid');
  if(!list.length){
    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="es-icon">🔍</div><div class="es-text">No members found matching your search.</div></div>';
    updateMemberCounts();
    renderLoadMoreButton();
    return;
  }
  updateMemberCounts();
  grid.innerHTML = list.map(renderMemberCard).join('');
}

function filterUsers(query){
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(function(){
    loadPortalUsers(query.trim());
  }, 350);
}

/* ══════════════════════════════════════
   RENDER REFERRALS
══════════════════════════════════════ */
function renderReferrals(){
  var list = document.getElementById('ref-list');
  if(!MY_REFERRALS.length){
    list.innerHTML = '<div class="empty-state"><div class="es-icon">🤝</div><div class="es-text">No referral partners yet.<br>Share your code to grow your network!</div></div>';
    return;
  }
  list.innerHTML = MY_REFERRALS.map(function(r){
    var sub = r.joined ? ('Joined ' + escH(r.joined)) : '';
    if(r.designation) sub += (sub ? ' · ' : '') + escH(r.designation);
    var av = avatarHtml(r, 'ri-avatar-init', 38);
    return '<div class="ref-item" onclick="openChat(' + r.id + ')" style="cursor:pointer" title="Chat with ' + escH(r.name) + '">'
      + av
      + '<div style="flex:1;min-width:0">'
      +   '<div style="font-size:.82rem;font-weight:800;color:var(--mm-black)">' + escH(r.name) + '</div>'
      +   '<div class="ref-earnings">' + (sub || 'Referral partner') + '</div>'
      + '</div>'
      + '<span class="ref-status active">💬 Chat</span>'
      + '</div>';
  }).join('');
}

/* ══════════════════════════════════════
   RENDER RECENT CHATS
══════════════════════════════════════ */
function renderRecentChats(){
  var panel = document.getElementById('recent-panel');
  var countEl = document.getElementById('recent-count');
  countEl.textContent = RECENT_CHATS.length + ' conversation' + (RECENT_CHATS.length !== 1 ? 's' : '');

  if(!RECENT_CHATS.length){
    panel.innerHTML = '<div class="empty-state"><div class="es-icon">💬</div><div class="es-text">No recent chats yet.<br>Start a conversation from Members!</div></div>';
    return;
  }

  panel.innerHTML = RECENT_CHATS.map(function(rc){
    var u = getUserById(rc.userId) || rc.otherUser;
    if(!u) return '';
    var av = avatarHtml(u, 'ri-avatar-init', 40);
    return '<div class="recent-item" onclick="openChat(' + u.id + ')">'
      + '<div class="ri-avatar-wrap">'
      +   av
      + '</div>'
      + '<div class="ri-info">'
      +   '<div class="ri-name">' + escH(u.name) + '</div>'
      +   '<div class="ri-preview">' + escH(rc.preview) + '</div>'
      + '</div>'
      + '<div class="ri-meta">'
      +   '<div class="ri-time">' + escH(rc.time) + '</div>'
      +   (rc.unread ? '<div class="ri-unread">' + rc.unread + '</div>' : '')
      + '</div>'
      + '</div>';
  }).join('');
}

/* ══════════════════════════════════════
   TAB SWITCHING
══════════════════════════════════════ */
function switchTab(tab, btnEl){
  currentTab = tab;

  document.querySelectorAll('.cr-menu-item').forEach(function(s){ s.classList.remove('active'); });
  var sbMap = { portal:'sb-portal', referral:'sb-referral', recent:'sb-recent' };
  var activeEl = btnEl || document.getElementById(sbMap[tab]);
  if(activeEl) activeEl.classList.add('active');

  document.getElementById('tab-portal').style.display   = (tab === 'portal')   ? 'flex' : 'none';
  document.getElementById('tab-referral').style.display = (tab === 'referral') ? 'flex' : 'none';
  document.getElementById('tab-recent').style.display   = (tab === 'recent')   ? 'flex' : 'none';

  if(tab === 'portal'){
    document.getElementById('tab-portal').style.flexDirection = 'column';
    document.getElementById('tab-portal').style.overflow = 'hidden';
    renderUsers(filteredUsers);
  }
  if(tab === 'referral'){
    var refTab = document.getElementById('tab-referral');
    refTab.style.flexDirection = 'column';
    refTab.style.overflow = 'hidden';
    if(!MY_REFERRALS.length) loadReferrals();
    else renderReferrals();
  }
  if(tab === 'recent'){
    var recTab = document.getElementById('tab-recent');
    recTab.style.flexDirection = 'column';
    recTab.style.overflow = 'hidden';
    if(!RECENT_CHATS.length) loadRecentChats();
    else renderRecentChats();
    refreshUnreadCount();
  }
}

// Init portal tab display
document.getElementById('tab-portal').style.display = 'flex';
document.getElementById('tab-portal').style.flexDirection = 'column';
document.getElementById('tab-portal').style.overflow = 'hidden';

/* ══════════════════════════════════════
   OPEN CHAT MODAL
══════════════════════════════════════ */
function setModalHeader(u){
  var initEl = document.getElementById('mh-avatar-init');
  if(u.avatar){
    initEl.innerHTML = '<img src="' + escH(u.avatar) + '" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
    initEl.style.background = 'transparent';
  } else {
    var colors = avatarColor(u.id);
    initEl.textContent = u.initials;
    initEl.style.background = 'linear-gradient(135deg,' + colors[0] + ',' + colors[1] + ')';
  }
  document.getElementById('mh-name').textContent = u.name;
  var interest = u.interests[0] || u.designation || 'Member';
  document.getElementById('mh-status').textContent = interest;
  document.getElementById('mh-online-dot').style.background = '#aaa';
}

function openChat(userId){
  var u = getUserById(userId);
  if(!u){
    toast('Loading member…', 'ok');
    apiGet('users/' + userId).then(function(raw){
      cacheUser(raw);
      openChat(userId);
    }).catch(function(err){ toast(err.message, 'fail'); });
    return;
  }

  activeChatId = userId;
  activeConversationId = null;
  lastMessageId = 0;
  renderedMessageIds = {};
  pollToken++;

  setModalHeader(u);
  document.getElementById('modal-messages').innerHTML =
    '<div class="empty-state"><div class="es-text">Opening chat…</div></div>';
  document.getElementById('chat-modal-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';

  clearUnreadForChat(userId, null);

  var btn = document.getElementById('modal-send-btn');
  btn.disabled = true;

  apiPost('chats', { user_id: userId }).then(function(conv){
    activeConversationId = conv.id;
    if(conv.other_user) cacheUser(conv.other_user);
    btn.disabled = false;
    markConversationRead(conv.id, userId);
    return apiGet('chats/' + conv.id + '/messages', { limit: 50 });
  }).then(function(data){
    renderChatMessages(data.messages || []);
    var ids = (data.messages || []).map(function(m){ return m.id; });
    lastMessageId = ids.length ? Math.max.apply(null, ids) : 0;
    startMessagePolling();
    loadRecentChats();
    setTimeout(function(){ document.getElementById('modal-input').focus(); }, 200);
  }).catch(function(err){
    btn.disabled = false;
    document.getElementById('modal-messages').innerHTML =
      '<div class="empty-state"><div class="es-icon">⚠️</div><div class="es-text">' + escH(err.message) + '</div></div>';
    toast(err.message, 'fail');
  });
}

function closeModal(){
  pollToken++;
  document.getElementById('chat-modal-overlay').classList.remove('open');
  document.body.style.overflow = 'hidden';
  activeChatId = null;
  activeConversationId = null;
  lastMessageId = 0;
  renderedMessageIds = {};
  document.getElementById('modal-input').value = '';
  document.getElementById('emoji-picker').classList.remove('open');
  emojiOpen = false;
  removeTypingDots();
  loadRecentChats();
  refreshUnreadCount();
}

function handleOverlayClick(e){
  if(e.target === document.getElementById('chat-modal-overlay')) closeModal();
}

/* ══════════════════════════════════════
   RENDER CHAT MESSAGES
══════════════════════════════════════ */
function renderChatMessages(messages){
  var msgs = document.getElementById('modal-messages');
  msgs.innerHTML = '';
  var sep = document.createElement('div');
  sep.className = 'mm-date-sep';
  sep.innerHTML = '<span>Conversation</span>';
  msgs.appendChild(sep);

  if(!messages.length){
    var u = activeChatId ? getUserById(activeChatId) : null;
    var empty = document.createElement('div');
    empty.className = 'empty-state';
    empty.innerHTML = '<div class="es-icon">👋</div><div class="es-text">Say hello to ' + escH(u ? u.name : 'them') + '!<br>Start a conversation below.</div>';
    msgs.appendChild(empty);
    return;
  }
  messages.forEach(function(m){
    appendApiMessage(m, false);
  });
  msgs.scrollTop = msgs.scrollHeight;
}

function appendApiMessage(m, animate){
  if(!m || !m.id || renderedMessageIds[m.id]) return;
  renderedMessageIds[m.id] = true;
  if(m.id > lastMessageId) lastMessageId = m.id;
  var body = m.text || m.message || '';
  var from = (m.is_mine === true || m.sender_id === CURRENT_USER.id) ? 'me' : 'them';
  appendModalMsg(from, body, formatMessageTime(m.created_at), animate);
}

function startMessagePolling(){
  var token = ++pollToken;
  function poll(){
    if(token !== pollToken || !activeConversationId) return;
    if(!document.getElementById('chat-modal-overlay').classList.contains('open')) return;

    apiGet('chats/' + activeConversationId + '/messages', {
      since_id: lastMessageId,
      wait: 25
    }).then(function(data){
      if(token !== pollToken) return;
      var incoming = (data.messages || []).filter(function(m){ return !m.is_mine; });
      incoming.forEach(function(m){ appendApiMessage(m, true); });
      (data.messages || []).forEach(function(m){
        if(m.is_mine && !renderedMessageIds[m.id]) appendApiMessage(m, true);
      });
      if(incoming.length && activeConversationId){
        markConversationRead(activeConversationId, activeChatId);
        loadRecentChats();
      } else if((data.messages || []).length){
        loadRecentChats();
      }
      poll();
    }).catch(function(){
      if(token === pollToken) setTimeout(poll, 3000);
    });
  }
  poll();
}

/* ══════════════════════════════════════
   SEND MESSAGE
══════════════════════════════════════ */
function appendModalMsg(from, text, timeStr, animate){
  var msgs = document.getElementById('modal-messages');
  var empty = msgs.querySelector('.empty-state');
  if(empty) empty.remove();
  var isMe = from === 'me';
  var u = activeChatId ? getUserById(activeChatId) : null;
  var colors = u ? avatarColor(u.id) : ['#05489C','#1A5DB1'];
  var initials = u ? u.initials : '?';
  timeStr = timeStr || getTime();

  var row = document.createElement('div');
  row.className = 'mm' + (isMe ? ' me' : '');
  if(!animate) row.style.animation = 'none';

  if(isMe){
    row.innerHTML =
      '<div class="mm-body">'
    + '<div class="mm-bubble">' + escH(text) + '</div>'
    + '<div class="mm-time">' + escH(timeStr) + '</div>'
    + '</div>'
    + '<div class="mm-avatar-init" style="background:var(--mm-primary)">' + escH(CURRENT_USER.initials) + '</div>';
  } else {
    row.innerHTML =
      '<div class="mm-avatar-init" style="background:linear-gradient(135deg,' + colors[0] + ',' + colors[1] + ')">' + escH(initials) + '</div>'
    + '<div class="mm-body">'
    + '<div class="mm-bubble">' + escH(text) + '</div>'
    + '<div class="mm-time">' + escH(timeStr) + '</div>'
    + '</div>';
  }

  msgs.appendChild(row);
  msgs.scrollTop = msgs.scrollHeight;
}

function sendModalMessage(){
  var input = document.getElementById('modal-input');
  var text = (input.value || '').trim();
  if(!text || !activeChatId || !activeConversationId) return;

  var btn = document.getElementById('modal-send-btn');
  btn.disabled = true;
  input.value = '';
  autoResizeModal(input);

  apiPost('chats/' + activeConversationId + '/messages', { message: text }).then(function(data){
    btn.disabled = false;
    if(data.message) appendApiMessage(data.message, true);
    loadRecentChats();
  }).catch(function(err){
    btn.disabled = false;
    toast(err.message, 'fail');
    input.value = text;
  });
}

function showTypingDots(){
  var msgs = document.getElementById('modal-messages');
  var u = activeChatId ? getUserById(activeChatId) : null;
  var colors = u ? avatarColor(u.id) : ['#05489C','#1A5DB1'];
  var initials = u ? u.initials : '?';
  var row = document.createElement('div');
  row.className = 'mm mm-typing';
  row.id = 'typing-row';
  row.innerHTML =
    '<div class="mm-avatar-init" style="background:linear-gradient(135deg,' + colors[0] + ',' + colors[1] + ')">' + escH(initials) + '</div>'
  + '<div class="mm-body"><div class="mm-bubble"><div class="tdots"><span></span><span></span><span></span></div></div></div>';
  msgs.appendChild(row);
  msgs.scrollTop = msgs.scrollHeight;
}

function removeTypingDots(){
  var el = document.getElementById('typing-row');
  if(el) el.remove();
}

function handleModalKey(e){
  if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); sendModalMessage(); }
}
function autoResizeModal(el){
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 110) + 'px';
}

/* ══════════════════════════════════════
   EMOJI PICKER
══════════════════════════════════════ */
function buildEmojiPicker(){
  var picker = document.getElementById('emoji-picker');
  picker.innerHTML = EMOJIS.map(function(e){
    return '<button class="ep-btn" onclick="insertEmoji(\'' + e + '\')" type="button">' + e + '</button>';
  }).join('');
}
function toggleEmoji(){
  emojiOpen = !emojiOpen;
  document.getElementById('emoji-picker').classList.toggle('open', emojiOpen);
}
function insertEmoji(e){
  var input = document.getElementById('modal-input');
  input.value += e;
  input.focus();
  emojiOpen = false;
  document.getElementById('emoji-picker').classList.remove('open');
}

/* ══════════════════════════════════════
   REFERRAL CODE COPY
══════════════════════════════════════ */
function copyRefCode(){
  var code = document.getElementById('ref-code-display').textContent;
  if(navigator.clipboard){
    navigator.clipboard.writeText(code).then(function(){
      toast('✅ Referral code copied!','ok');
    });
  } else {
    toast('✅ Code: ' + code,'ok');
  }
}

/* ══════════════════════════════════════
   VIEW PROFILE
══════════════════════════════════════ */
function viewProfile(){
  if(!activeChatId) return;
  var u = getUserById(activeChatId);
  if(u && u.profileUrl){
    window.open(u.profileUrl, '_blank');
    return;
  }
  toast('Profile link unavailable', 'fail');
}

/* ══════════════════════════════════════
   UTILITIES
══════════════════════════════════════ */
function getTime(){
  return new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
}
function escH(s){
  return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function toast(m, type){
  var t = document.getElementById('room-toast');
  t.textContent = m; t.className = 'show ' + (type||'');
  setTimeout(function(){ t.className=''; }, 3200);
}

function tryOpenAiSupportChat(){
  if(window.MMChatWidget && typeof window.MMChatWidget.openWidget === 'function'){
    try {
      if(typeof window.MMChatWidget.init === 'function') window.MMChatWidget.init();
      window.MMChatWidget.openWidget();
      return true;
    } catch (e) {
      console.warn('MMChatWidget failed, using fallback:', e);
    }
  }
  var panel = document.getElementById('mm-ai-chat-widget');
  if(panel && window.jQuery){
    ensureAiFallbackReady();
    openAiFallbackWidget();
    return true;
  }
  return false;
}

function ensureAiFallbackReady(){
  if(aiFallbackReady) return;
  aiFallbackReady = true;
  var stored = localStorage.getItem('mm_ai_chat_session');
  if(stored){
    try {
      var session = JSON.parse(stored);
      aiConversationId = session.conversationId || null;
    } catch(e){}
  }
  var $ = window.jQuery;
  $(document).off('click.crAi', '#mm-ai-chat-close').on('click.crAi', '#mm-ai-chat-close', function(e){
    e.preventDefault();
    closeAiFallbackWidget();
  });
  $(document).off('click.crAi', '#mm-ai-chat-send').on('click.crAi', '#mm-ai-chat-send', function(e){
    e.preventDefault();
    sendAiFallbackMessage();
  });
  $(document).off('keypress.crAi', '#mm-ai-chat-input').on('keypress.crAi', '#mm-ai-chat-input', function(e){
    if(e.key === 'Enter' && !e.shiftKey){
      e.preventDefault();
      sendAiFallbackMessage();
    }
  });
}

function openAiFallbackWidget(){
  var $ = window.jQuery;
  if(!$) return;
  $('#mm-ai-chat-widget').stop(true, true).slideDown(300);
  $('#mm-ai-chat-toggle').hide();
  if(!aiConversationId){
    initAiFallbackChat();
  } else if($('#mm-ai-chat-messages').children().length === 0){
    loadAiFallbackMessages();
  }
}

function closeAiFallbackWidget(){
  var $ = window.jQuery;
  if(!$) return;
  $('#mm-ai-chat-widget').stop(true, true).slideUp(300);
  $('#mm-ai-chat-toggle').hide();
}

function aiPayload(response){
  if(!response || typeof response !== 'object') return {};
  return (response.data && typeof response.data === 'object') ? response.data : response;
}

function appendAiMessage(type, content){
  var $ = window.jQuery;
  if(!$) return;
  var cls = type === 'user' ? 'msg-user' : 'msg-ai';
  var safe = $('<div>').text(content || '').html();
  $('#mm-ai-chat-messages').append(
    '<div class="mm-ai-chat-message ' + cls + '"><div class="mm-ai-chat-message-content">' + safe + '</div></div>'
  );
  var el = document.getElementById('mm-ai-chat-messages');
  if(el) el.scrollTop = el.scrollHeight;
}

function initAiFallbackChat(){
  var $ = window.jQuery;
  if(!$) return;
  if(!AI_CHAT.restUrl){
    appendAiMessage('ai', 'AI chat is not configured.');
    return;
  }
  if(aiInitializing) return;
  aiInitializing = true;
  $('#mm-ai-chat-messages').empty();
  $.ajax({
    url: AI_CHAT.restUrl.replace(/\/$/, '') + '/chat/initiate',
    type: 'POST',
    dataType: 'json',
    processData: false,
    contentType: 'application/json',
    headers: { 'X-WP-Nonce': AI_CHAT.nonce },
    data: JSON.stringify({ page_context: { page_url: window.location.href, page_title: document.title } }),
    success: function(response){
      aiInitializing = false;
      var data = aiPayload(response);
      if(data.success && data.conversation_id){
        aiConversationId = data.conversation_id;
        localStorage.setItem('mm_ai_chat_session', JSON.stringify({
          conversationId: data.conversation_id,
          sessionId: data.session_id,
          mode: 'ai'
        }));
        if(data.message && data.message.content){
          appendAiMessage('ai', data.message.content);
        } else {
          appendAiMessage('ai', 'Welcome! How can I help you today?');
        }
        if(data.api_warning){
          appendAiMessage('ai', 'Note: ' + data.api_warning);
        }
      } else {
        aiConversationId = null;
        localStorage.removeItem('mm_ai_chat_session');
        appendAiMessage('ai', data.error || 'Could not start chat.');
      }
    },
    error: function(xhr){
      aiInitializing = false;
      aiConversationId = null;
      localStorage.removeItem('mm_ai_chat_session');
      console.error('AI initiate error:', xhr.status, xhr.responseText);
      appendAiMessage('ai', 'Error connecting to AI chat. Hard-refresh (Ctrl+F5) and try again.');
    }
  });
}

function loadAiFallbackMessages(){
  var $ = window.jQuery;
  if(!$) return;
  $.ajax({
    url: AI_CHAT.restUrl + '/chat/messages/' + aiConversationId,
    type: 'GET',
    dataType: 'json',
    headers: { 'X-WP-Nonce': AI_CHAT.nonce },
    success: function(response){
      var data = aiPayload(response);
      if(response.success && data.messages){
        $('#mm-ai-chat-messages').empty();
        data.messages.forEach(function(msg){
          appendAiMessage(msg.sender_type || msg.type || 'ai', msg.content || '');
        });
      }
    }
  });
}

function sendAiFallbackMessage(){
  var $ = window.jQuery;
  if(!$) return;
  var text = $('#mm-ai-chat-input').val().trim();
  if(!text || aiWaiting) return;
  if(!aiConversationId){
    if(!aiInitializing) initAiFallbackChat();
    return;
  }
  appendAiMessage('user', text);
  $('#mm-ai-chat-input').val('');
  aiWaiting = true;
  $('#mm-ai-chat-send').prop('disabled', true);
  $.ajax({
    url: AI_CHAT.restUrl.replace(/\/$/, '') + '/chat/message',
    type: 'POST',
    dataType: 'json',
    processData: false,
    contentType: 'application/json',
    headers: { 'X-WP-Nonce': AI_CHAT.nonce },
    data: JSON.stringify({ conversation_id: aiConversationId, message: text }),
    success: function(response){
      var data = aiPayload(response);
      if(data.success && data.ai_response){
        appendAiMessage('ai', data.ai_response);
      } else {
        appendAiMessage('ai', data.error || 'Unable to get a response.');
      }
    },
    error: function(xhr){
      console.error('AI message error:', xhr.status, xhr.responseText);
      appendAiMessage('ai', 'Error sending message.');
    },
    complete: function(){
      aiWaiting = false;
      $('#mm-ai-chat-send').prop('disabled', false);
    }
  });
}

function openAiSupportChat(){
  if(tryOpenAiSupportChat()) return;
  var tries = 0;
  function attempt(){
    if(tryOpenAiSupportChat()) return;
    tries++;
    if(tries < 15){
      setTimeout(attempt, 200);
    } else {
      toast('AI chat widget not found. Enable it in AI Chat → Settings, then hard-refresh (Ctrl+F5).', 'fail');
    }
  }
  attempt();
}

// Close emoji picker on outside click
document.addEventListener('click', function(e){
  if(emojiOpen && !e.target.closest('#emoji-picker') && !e.target.closest('.modal-emoji')){
    emojiOpen = false;
    document.getElementById('emoji-picker').classList.remove('open');
  }
});

// ESC to close modal
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') closeModal();
});
</script>

<?php wp_footer(); ?>
</body>
</html>