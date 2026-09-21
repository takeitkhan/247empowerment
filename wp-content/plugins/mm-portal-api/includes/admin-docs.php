<?php
/**
 * Admin: API documentation page.
 *
 * Registers a top-level "Portal API" menu in wp-admin with one submenu entry
 * per section. Each submenu loads the same page and activates the matching
 * tab via the `?tab=` query arg. Inside the page, JS swaps tab panels
 * without a reload.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MM_Portal_Admin_Docs {

    const PAGE_SLUG = 'mm-portal-api-docs';
    const CAP       = 'manage_options';

    /**
     * Tab definitions. Order here = order in the menu and in the tab bar.
     */
    public static function tabs() {
        return [
            'overview'  => [ 'label' => 'Overview',          'icon' => 'dashicons-info' ],
            'users'     => [ 'label' => 'Portal Users',      'icon' => 'dashicons-groups' ],
            'referrals' => [ 'label' => 'Referral Partners', 'icon' => 'dashicons-networking' ],
            'chats'     => [ 'label' => 'Recent Chats',      'icon' => 'dashicons-format-chat' ],
            'messages'  => [ 'label' => '1:1 Chat',          'icon' => 'dashicons-email-alt' ],
            'realtime'  => [ 'label' => 'Realtime',          'icon' => 'dashicons-update' ],
        ];
    }

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
    }

    public static function register_menu() {
        add_menu_page(
            'Portal API Docs',
            'Portal API',
            self::CAP,
            self::PAGE_SLUG,
            [ __CLASS__, 'render_page' ],
            'dashicons-rest-api',
            58
        );

        foreach ( self::tabs() as $slug => $tab ) {
            add_submenu_page(
                self::PAGE_SLUG,
                $tab['label'],
                $tab['label'],
                self::CAP,
                self::PAGE_SLUG . ( $slug === 'overview' ? '' : '&tab=' . $slug ),
                [ __CLASS__, 'render_page' ]
            );
        }

        // Rename the auto-added first submenu item ("Portal API" duplicate).
        global $submenu;
        if ( isset( $submenu[ self::PAGE_SLUG ][0][0] ) ) {
            $submenu[ self::PAGE_SLUG ][0][0] = 'Overview';
        }
    }

    public static function render_page() {
        if ( ! current_user_can( self::CAP ) ) {
            wp_die( 'Unauthorized' );
        }

        $tabs   = self::tabs();
        $active = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';
        if ( ! isset( $tabs[ $active ] ) ) {
            $active = 'overview';
        }

        $base = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
        ?>
        <div class="wrap mm-portal-docs">
            <h1 style="margin-bottom:6px;">
                <span class="dashicons dashicons-rest-api" style="font-size:28px;width:28px;height:28px;vertical-align:middle;"></span>
                MM Portal API &mdash; Documentation
            </h1>
            <p class="description" style="margin-top:0;">
                Namespace: <code>mm/v1</code> &middot;
                Base URL: <code><?php echo esc_html( rest_url( MM_PORTAL_API_NAMESPACE . '/' ) ); ?></code>
            </p>

            <?php self::print_styles(); ?>

            <h2 class="nav-tab-wrapper mm-portal-tabs">
                <?php foreach ( $tabs as $slug => $tab ) :
                    $url = $base . ( $slug === 'overview' ? '' : '&tab=' . $slug );
                    $cls = 'nav-tab' . ( $slug === $active ? ' nav-tab-active' : '' );
                ?>
                    <a href="<?php echo esc_url( $url ); ?>"
                       class="<?php echo esc_attr( $cls ); ?>"
                       data-tab="<?php echo esc_attr( $slug ); ?>">
                        <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                        <?php echo esc_html( $tab['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <div class="mm-portal-panels">
                <?php foreach ( $tabs as $slug => $tab ) : ?>
                    <section id="panel-<?php echo esc_attr( $slug ); ?>"
                             class="mm-portal-panel"
                             style="<?php echo $slug === $active ? '' : 'display:none;'; ?>">
                        <?php self::render_panel( $slug ); ?>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        (function () {
            var tabs   = document.querySelectorAll('.mm-portal-tabs .nav-tab');
            var panels = document.querySelectorAll('.mm-portal-panel');
            tabs.forEach(function (t) {
                t.addEventListener('click', function (e) {
                    // Allow Ctrl/Cmd+click to open in new tab normally.
                    if (e.metaKey || e.ctrlKey || e.shiftKey) return;
                    e.preventDefault();
                    var target = t.getAttribute('data-tab');
                    tabs.forEach(function (x) { x.classList.remove('nav-tab-active'); });
                    t.classList.add('nav-tab-active');
                    panels.forEach(function (p) {
                        p.style.display = (p.id === 'panel-' + target) ? '' : 'none';
                    });
                    var url = new URL(window.location.href);
                    if (target === 'overview') { url.searchParams.delete('tab'); }
                    else { url.searchParams.set('tab', target); }
                    window.history.replaceState({}, '', url.toString());
                });
            });

            // Click-to-copy on any <pre> block.
            document.querySelectorAll('.mm-portal-panel pre').forEach(function (pre) {
                pre.title = 'Click to copy';
                pre.addEventListener('click', function () {
                    var t = pre.innerText;
                    navigator.clipboard && navigator.clipboard.writeText(t);
                    var orig = pre.style.outline;
                    pre.style.outline = '2px solid #2271b1';
                    setTimeout(function(){ pre.style.outline = orig; }, 400);
                });
            });
        })();
        </script>
        <?php
    }

    private static function print_styles() {
        ?>
        <style>
            .mm-portal-docs .nav-tab .dashicons { font-size:16px; width:16px; height:16px; vertical-align:text-bottom; margin-right:4px; }
            .mm-portal-docs .mm-portal-tabs { margin-top:16px; }
            .mm-portal-panel { background:transparent; padding:18px 0; max-width:980px; }
            .mm-portal-panel h2 { margin-top:24px; border-bottom:1px solid #dcdcde; padding-bottom:6px; }
            .mm-portal-endpoint { background:#fff; border:1px solid #dcdcde; border-left:4px solid #2271b1; border-radius:4px; padding:18px 20px; margin:14px 0; }
            .mm-portal-endpoint h3 { margin:0 0 6px; color:#1d2327; font-size:16px; }
            .mm-portal-endpoint .mm-route { font-family:Consolas,Menlo,monospace; font-size:13px; background:#f0f0f1; padding:4px 8px; border-radius:3px; display:inline-block; }
            .mm-portal-endpoint .mm-method { display:inline-block; font-weight:700; padding:2px 8px; border-radius:3px; color:#fff; margin-right:6px; font-family:Consolas,Menlo,monospace; font-size:12px; }
            .mm-method.GET    { background:#1d8a4a; }
            .mm-method.POST   { background:#2271b1; }
            .mm-method.PUT    { background:#b26a00; }
            .mm-method.DELETE { background:#b32d2e; }
            .mm-portal-endpoint p { margin:8px 0; }
            .mm-portal-endpoint pre { background:#1d2327; color:#e6e6e6; padding:12px 14px; border-radius:4px; overflow-x:auto; font-size:12.5px; line-height:1.5; cursor:pointer; }
            .mm-portal-endpoint pre.mm-error { background:#3b1d1d; }
            .mm-portal-endpoint table.mm-params { border-collapse:collapse; width:100%; margin:6px 0 12px; }
            .mm-portal-endpoint table.mm-params th, .mm-portal-endpoint table.mm-params td { border:1px solid #dcdcde; padding:6px 10px; text-align:left; font-size:13px; vertical-align:top; }
            .mm-portal-endpoint table.mm-params th { background:#f6f7f7; }
            .mm-portal-endpoint .mm-section-label { font-weight:600; color:#50575e; margin-top:14px; display:block; }
            .mm-portal-callout { background:#fff8e1; border-left:4px solid #dba617; padding:10px 14px; margin:12px 0; border-radius:3px; }
            .mm-portal-callout.info { background:#e7f3fb; border-color:#2271b1; }
        </style>
        <?php
    }

    /* =====================================================================
     * Panels — one method per tab.
     * ===================================================================== */

    private static function render_panel( $slug ) {
        $fn = 'panel_' . $slug;
        if ( method_exists( __CLASS__, $fn ) ) {
            self::$fn();
        }
    }

    /* ---------- OVERVIEW -------------------------------------------------- */
    private static function panel_overview() {
        $root = rest_url( MM_PORTAL_API_NAMESPACE . '/' );
        ?>
        <h2>Overview</h2>
        <p>The Portal API exposes user discovery, referral relationships, and 1:1 chat as REST endpoints for the front-end of the 247 Empowerment portal.</p>

        <div class="mm-portal-callout info">
            <strong>Base URL:</strong> <code><?php echo esc_html( $root ); ?></code><br>
            <strong>Namespace:</strong> <code>mm/v1</code><br>
            <strong>Auth:</strong> mm-spg login nonce <em>(Postman-friendly)</em> &mdash; or WordPress cookie + <code>X-WP-Nonce</code> from a logged-in browser.
        </div>

        <h3>Authentication &mdash; the simple flow</h3>
        <p>This plugin reuses the login system from <code>mm-spg</code>. Hit the login endpoint once, grab the token, then attach it to every Portal API call. Works from Postman, mobile apps, cURL &mdash; anywhere.</p>

        <p><strong>Step 1.</strong> Log in (mm-spg endpoint):</p>
<pre>POST <?php echo esc_html( rest_url( 'api/v1/auth/login' ) ); ?>
Content-Type: application/json

{
  "username": "your-login",
  "password": "your-password"
}</pre>

        <p><strong>Response:</strong></p>
<pre>{
  "success": true,
  "data": {
    "user_id": 1,
    "username": "your-login",
    "nonce": "abc123def456..."   // &larr; save this
  }
}</pre>

        <p><strong>Step 2.</strong> Send the token with every Portal API request. Any one of these three is accepted:</p>
        <table class="mm-params">
            <thead><tr><th>Where</th><th>Example</th></tr></thead>
            <tbody>
                <tr><td>Query string</td><td><code>GET /wp-json/mm/v1/users?nonce=abc123def456...</code></td></tr>
                <tr><td>Header</td><td><code>X-MM-Nonce: abc123def456...</code></td></tr>
                <tr><td>JSON body</td><td><code>{ "nonce": "abc123...", "message": "hi" }</code></td></tr>
            </tbody>
        </table>
        <p>Tokens are valid for 24 hours and are stored as user meta (<code>mm_spg_api_nonce</code>). Re-login to refresh.</p>

        <h3>Postman quickstart</h3>
        <ol>
            <li>New request &rarr; <code>POST <?php echo esc_html( rest_url( 'api/v1/auth/login' ) ); ?></code>, body raw-JSON with your credentials. Copy the <code>nonce</code> from the response.</li>
            <li>For any Portal API request, go to <strong>Params</strong> and add <code>nonce = &lt;your token&gt;</code>. Done.</li>
            <li><em>Tip:</em> in Postman&rsquo;s login request, add this to <strong>Tests</strong> to auto-save the token as a collection variable:
<pre>const json = pm.response.json();
if (json.data &amp;&amp; json.data.nonce) {
    pm.collectionVariables.set('nonce', json.data.nonce);
}</pre>
                Then in every other request use <code>?nonce={{nonce}}</code>.</li>
        </ol>

        <h3>Browser front-end (no token needed)</h3>
        <p>For pages served by WordPress, the plugin localizes <code>window.MM_PORTAL_API</code> automatically. Use the standard cookie+nonce flow:</p>
<pre>{
  "root":      "<?php echo esc_html( $root ); ?>",
  "namespace": "mm/v1",
  "nonce":     "&lt;wp_rest nonce&gt;",   // for X-WP-Nonce header
  "user_id":   123
}

fetch(MM_PORTAL_API.root + 'users?page=1&amp;per_page=20', {
    credentials: 'same-origin',
    headers: { 'X-WP-Nonce': MM_PORTAL_API.nonce }
})
.then(r =&gt; r.json())
.then(console.log);</pre>

        <h3>Error envelope</h3>
        <p>Errors follow the standard WP REST shape:</p>
<pre class="mm-error">{
  "code":    "rest_forbidden",
  "message": "You must be logged in.",
  "data":    { "status": 401 }
}</pre>

        <h3>Endpoint index</h3>
        <table class="mm-params">
            <thead><tr><th>Method</th><th>Route</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><span class="mm-method GET">GET</span></td><td><code>/users</code></td><td>Portal users list (paginated, searchable, filter by interest)</td></tr>
                <tr><td><span class="mm-method GET">GET</span></td><td><code>/users/{id}</code></td><td>Single user full profile</td></tr>
                <tr><td><span class="mm-method GET">GET</span></td><td><code>/referrals</code></td><td>Users referred by current user</td></tr>
                <tr><td><span class="mm-method GET">GET</span></td><td><code>/chats</code></td><td>Recent chats with last message + unread count</td></tr>
                <tr><td><span class="mm-method POST">POST</span></td><td><code>/chats</code></td><td>Start (or fetch) a chat with a user</td></tr>
                <tr><td><span class="mm-method GET">GET</span></td><td><code>/chats/{id}/messages</code></td><td>History or long-poll for new messages</td></tr>
                <tr><td><span class="mm-method POST">POST</span></td><td><code>/chats/{id}/messages</code></td><td>Send a message</td></tr>
                <tr><td><span class="mm-method POST">POST</span></td><td><code>/chats/{id}/read</code></td><td>Mark a chat as read</td></tr>
                <tr><td><span class="mm-method GET">GET</span></td><td><code>/chats/unread-count</code></td><td>Total unread messages across all chats</td></tr>
            </tbody>
        </table>
        <?php
    }

    /* ---------- PORTAL USERS --------------------------------------------- */
    private static function panel_users() {
        ?>
        <h2>Portal Users</h2>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method GET">GET</span><span class="mm-route">/wp-json/mm/v1/users</span></h3>
            <p>Returns a paginated list of all portal users (excluding the current user).</p>

            <span class="mm-section-label">Query parameters</span>
            <table class="mm-params">
                <thead><tr><th>Name</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>page</code></td><td>int</td><td>1</td><td>Page number</td></tr>
                    <tr><td><code>per_page</code></td><td>int</td><td>20</td><td>Items per page (max 100)</td></tr>
                    <tr><td><code>search</code></td><td>string</td><td>&mdash;</td><td>Matches against login, email, display name, nicename</td></tr>
                    <tr><td><code>interest</code></td><td>string</td><td>&mdash;</td><td>Filter by category term ID or slug</td></tr>
                </tbody>
            </table>

            <span class="mm-section-label">Example request</span>
<pre>GET /wp-json/mm/v1/users?page=1&amp;per_page=10&amp;search=john&amp;interest=fitness
X-WP-Nonce: &lt;nonce&gt;</pre>

            <span class="mm-section-label">Success response (200)</span>
<pre>{
  "users": [
    {
      "id": 42,
      "username": "johndoe",
      "display_name": "John Doe",
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "bio": "Fitness coach &amp; entrepreneur.",
      "designation": "Coach",
      "location": "Austin, TX",
      "avatar_url": "https://pet.test/wp-content/.../profile.jpg",
      "profile_url": "https://pet.test/johndoe",
      "interests": [
        { "id": 12, "name": "Fitness",  "slug": "fitness",  "priority": 1 },
        { "id": 18, "name": "Business", "slug": "business", "priority": 2 }
      ],
      "registered_date": "2025-08-12 10:33:21"
    }
  ],
  "page": 1,
  "per_page": 10,
  "total": 187,
  "total_pages": 19
}</pre>
            <p><em>Response headers also include <code>X-WP-Total</code> and <code>X-WP-TotalPages</code>.</em></p>
        </div>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method GET">GET</span><span class="mm-route">/wp-json/mm/v1/users/{id}</span></h3>
            <p>Returns the full profile for a single user, including the rich <code>profile</code> block produced by <code>UserProfileData::getProfile()</code>.</p>

            <span class="mm-section-label">Success response (200)</span>
<pre>{
  "id": 42,
  "username": "johndoe",
  "display_name": "John Doe",
  "avatar_url": "https://pet.test/...",
  "interests": [ /* … */ ],
  "profile": {
    "username": "johndoe",
    "first_name": "John",
    "last_name": "Doe",
    "designation": "Coach",
    "about_me": "…",
    "location": "Austin, TX",
    "social_links": { "facebook": "…", "instagram": "…" },
    "user_categories_with_priority": [ /* … */ ],
    "mm_spg_avatar": "male",
    "shareable_link": "https://pet.test/johndoe"
  }
}</pre>

            <span class="mm-section-label">Error (404)</span>
<pre class="mm-error">{ "code": "rest_user_not_found", "message": "User not found.", "data": { "status": 404 } }</pre>
        </div>
        <?php
    }

    /* ---------- REFERRAL PARTNERS ---------------------------------------- */
    private static function panel_referrals() {
        ?>
        <h2>Referral Partners</h2>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method GET">GET</span><span class="mm-route">/wp-json/mm/v1/referrals</span></h3>
            <p>Returns the list of users referred by the currently logged-in user.</p>
            <p>The endpoint delegates to <code>UserProfileData::getReferredUsersBy()</code> (reading the mutual <code>referral_partners</code> user meta), with a fallback to <code>meta_query</code> on the <code>referrer</code> meta for legacy data.</p>

            <span class="mm-section-label">Example request</span>
<pre>GET /wp-json/mm/v1/referrals
X-WP-Nonce: &lt;nonce&gt;</pre>

            <span class="mm-section-label">Success response (200)</span>
<pre>{
  "users": [
    {
      "id": 87,
      "username": "janedoe",
      "display_name": "Jane Doe",
      "first_name": "Jane",
      "last_name": "Doe",
      "email": "jane@example.com",
      "bio": "Wellness mentor.",
      "designation": "Mentor",
      "location": "Seattle, WA",
      "avatar_url": "https://pet.test/...",
      "profile_url": "https://pet.test/janedoe",
      "interests": [
        { "id": 12, "name": "Fitness", "slug": "fitness", "priority": 1 }
      ],
      "registered_date": "2025-10-04 14:08:09"
    }
  ],
  "count": 1
}</pre>

            <div class="mm-portal-callout info">
                Same user shape as <code>/users</code>. Results are sorted alphabetically by <code>display_name</code>.
            </div>
        </div>
        <?php
    }

    /* ---------- RECENT CHATS --------------------------------------------- */
    private static function panel_chats() {
        ?>
        <h2>Recent Chats</h2>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method GET">GET</span><span class="mm-route">/wp-json/mm/v1/chats</span></h3>
            <p>Returns the current user's conversations ordered by most recent activity.</p>

            <span class="mm-section-label">Query parameters</span>
            <table class="mm-params">
                <thead><tr><th>Name</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>page</code></td><td>int</td><td>1</td><td>Page number</td></tr>
                    <tr><td><code>per_page</code></td><td>int</td><td>20</td><td>Conversations per page (max 100)</td></tr>
                </tbody>
            </table>

            <span class="mm-section-label">Success response (200)</span>
<pre>{
  "chats": [
    {
      "id": 17,
      "other_user": {
        "id": 87,
        "username": "janedoe",
        "display_name": "Jane Doe",
        "avatar_url": "https://pet.test/...",
        "interests": [ /* … */ ]
      },
      "last_message": {
        "id": 1042,
        "sender_id": 87,
        "text": "See you tomorrow!",
        "created_at": "2026-05-31 22:14:09",
        "is_mine": false
      },
      "unread_count": 2,
      "updated_at": "2026-05-31 22:14:09"
    }
  ],
  "page": 1,
  "per_page": 20,
  "count": 1
}</pre>
        </div>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method GET">GET</span><span class="mm-route">/wp-json/mm/v1/chats/unread-count</span></h3>
            <p>Total unread messages across all conversations &mdash; ideal for a header badge.</p>

            <span class="mm-section-label">Success response (200)</span>
<pre>{ "unread_count": 7 }</pre>
        </div>
        <?php
    }

    /* ---------- 1:1 CHAT -------------------------------------------------- */
    private static function panel_messages() {
        ?>
        <h2>1:1 Chat</h2>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method POST">POST</span><span class="mm-route">/wp-json/mm/v1/chats</span></h3>
            <p>Creates (or returns) a conversation between the current user and the given <code>user_id</code>. Idempotent &mdash; the same pair always resolves to the same conversation.</p>

            <span class="mm-section-label">Request body</span>
<pre>{ "user_id": 87 }</pre>

            <span class="mm-section-label">Success response (200)</span>
<pre>{
  "id": 17,
  "other_user": {
    "id": 87,
    "username": "janedoe",
    "display_name": "Jane Doe",
    "avatar_url": "https://pet.test/..."
  },
  "created_at": "2026-05-30 14:09:01",
  "updated_at": "2026-05-31 22:14:09"
}</pre>

            <span class="mm-section-label">Errors</span>
<pre class="mm-error">{ "code": "rest_invalid_user",     "data": { "status": 400 } }
{ "code": "rest_chat_forbidden",   "data": { "status": 403 } }   // blocked by mm_portal_api_can_chat filter
{ "code": "rest_chat_backend_unavailable", "data": { "status": 503 } } // mm-referral-chat plugin not active</pre>
        </div>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method GET">GET</span><span class="mm-route">/wp-json/mm/v1/chats/{id}/messages</span></h3>
            <p>Returns conversation messages. Two modes:</p>
            <ul>
                <li><strong>History</strong> &mdash; default. Use <code>limit</code> and <code>before_id</code> to paginate backwards. Side-effect: the conversation is marked read for the viewer.</li>
                <li><strong>Long-poll</strong> &mdash; pass <code>since_id</code> (and optional <code>wait</code>). See the <em>Realtime</em> tab.</li>
            </ul>

            <span class="mm-section-label">Query parameters</span>
            <table class="mm-params">
                <thead><tr><th>Name</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>limit</code></td><td>int</td><td>50</td><td>Max messages to return (cap 200)</td></tr>
                    <tr><td><code>before_id</code></td><td>int</td><td>0</td><td>Return messages older than this id (pagination)</td></tr>
                    <tr><td><code>since_id</code></td><td>int</td><td>0</td><td>Long-poll for messages newer than this id</td></tr>
                    <tr><td><code>wait</code></td><td>int</td><td>0</td><td>Long-poll timeout in seconds (cap 25)</td></tr>
                </tbody>
            </table>

            <span class="mm-section-label">Success response (200) &mdash; history mode</span>
<pre>{
  "messages": [
    {
      "id": 1041,
      "conversation_id": 17,
      "sender_id": 1,
      "is_mine": true,
      "text": "Hi Jane, are we still on for tomorrow?",
      "is_read": true,
      "read_at": "2026-05-31 22:10:00",
      "created_at": "2026-05-31 22:09:31"
    },
    {
      "id": 1042,
      "conversation_id": 17,
      "sender_id": 87,
      "is_mine": false,
      "text": "See you tomorrow!",
      "is_read": false,
      "read_at": null,
      "created_at": "2026-05-31 22:14:09"
    }
  ],
  "mode": "history",
  "has_more": false
}</pre>
        </div>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method POST">POST</span><span class="mm-route">/wp-json/mm/v1/chats/{id}/messages</span></h3>
            <p>Sends a new message in the conversation.</p>

            <span class="mm-section-label">Request body</span>
<pre>{ "message": "Hey, how are you?" }</pre>

            <span class="mm-section-label">Success response (200)</span>
<pre>{
  "message": {
    "id": 1043,
    "conversation_id": 17,
    "sender_id": 1,
    "is_mine": true,
    "text": "Hey, how are you?",
    "is_read": false,
    "read_at": null,
    "created_at": "2026-06-01 09:00:01"
  }
}</pre>

            <span class="mm-section-label">Errors</span>
<pre class="mm-error">{ "code": "rest_invalid_message", "data": { "status": 400 } }
{ "code": "rest_forbidden",       "data": { "status": 403 } }   // not a participant</pre>

            <p><em>Server-side action fired on success:</em> <code>do_action('mm_portal_api_message_sent', $conv_id, $sender_id, $message)</code> &mdash; hook here to push via Pusher/Ably/etc.</p>
        </div>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method POST">POST</span><span class="mm-route">/wp-json/mm/v1/chats/{id}/read</span></h3>
            <p>Marks all messages from the other participant in the conversation as read.</p>

            <span class="mm-section-label">Success response (200)</span>
<pre>{ "success": true }</pre>
        </div>
        <?php
    }

    /* ---------- REALTIME ------------------------------------------------- */
    private static function panel_realtime() {
        ?>
        <h2>Realtime (Long-Poll)</h2>

        <p>The plugin uses HTTP long-polling for realtime delivery &mdash; no Node/Socket server required. The same <code>GET /chats/{id}/messages</code> endpoint switches into long-poll mode when <code>since_id</code> is provided.</p>

        <div class="mm-portal-callout">
            <strong>How it works:</strong> the server holds the connection open for up to <code>wait</code> seconds (max 25), polling the DB every ~1.5s for messages with <code>id &gt; since_id</code>. It returns immediately when new messages arrive, or after <code>wait</code> seconds with an empty <code>messages</code> array. The client then re-issues the request with the latest id.
        </div>

        <div class="mm-portal-endpoint">
            <h3><span class="mm-method GET">GET</span><span class="mm-route">/wp-json/mm/v1/chats/{id}/messages?since_id=1042&amp;wait=25</span></h3>

            <span class="mm-section-label">Success response (200) &mdash; new message arrived</span>
<pre>{
  "messages": [
    {
      "id": 1043,
      "conversation_id": 17,
      "sender_id": 87,
      "is_mine": false,
      "text": "Quick question…",
      "is_read": false,
      "read_at": null,
      "created_at": "2026-06-01 09:00:08"
    }
  ],
  "mode": "poll"
}</pre>

            <span class="mm-section-label">Success response (200) &mdash; timeout, nothing new</span>
<pre>{ "messages": [], "mode": "poll" }</pre>
        </div>

        <h3>Client implementation</h3>
<pre>async function listen(convId, fromId) {
    let lastId = fromId;
    while (true) {
        const url = `${MM_PORTAL_API.root}chats/${convId}/messages`
                  + `?since_id=${lastId}&amp;wait=25`;
        const r = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-WP-Nonce': MM_PORTAL_API.nonce }
        });
        if (!r.ok) { await new Promise(s =&gt; setTimeout(s, 3000)); continue; }
        const { messages } = await r.json();
        for (const m of messages) {
            appendToChat(m);
            lastId = m.id;
        }
    }
}</pre>

        <h3>Upgrading to push (Pusher / Ably / Firebase)</h3>
        <p>When you're ready to swap long-poll for true push, hook the server-side action:</p>
<pre>add_action('mm_portal_api_message_sent', function ($conv_id, $sender_id, $message) {
    // Pusher example
    $pusher-&gt;trigger("chat-$conv_id", 'message', $message);
}, 10, 3);</pre>
        <p>The client API contract does not need to change &mdash; long-poll continues to work as a fallback.</p>

        <h3>Operational notes</h3>
        <ul>
            <li>Each waiting client holds one PHP-FPM worker. Tune <code>pm.max_children</code> if many users keep chat tabs open.</li>
            <li>Keep <code>wait</code> &le; the value of <code>request_terminate_timeout</code> / <code>max_execution_time</code>.</li>
            <li>Behind a reverse proxy (Traefik), make sure read/idle timeouts allow at least 30s.</li>
        </ul>
        <?php
    }
}

MM_Portal_Admin_Docs::init();
