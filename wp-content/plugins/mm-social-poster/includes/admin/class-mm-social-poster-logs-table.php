<?php
/**
 * WP_List_Table for share logs: filter by status/platform, search by user.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MM_Social_Poster_Logs_Table extends WP_List_Table
{
    const PER_PAGE = 25;

    const EVENTS = array(
        'share'            => 'Share',
        'oauth_connect'    => 'Connected',
        'oauth_disconnect' => 'Disconnected',
        'oauth_authorize'  => 'OAuth: authorize',
        'oauth_state'      => 'OAuth: state check',
        'oauth_token'      => 'OAuth: token exchange',
        'oauth_userinfo'   => 'OAuth: profile fetch',
    );

    public function __construct()
    {
        parent::__construct(array('singular' => 'share', 'plural' => 'shares', 'ajax' => false));
    }

    public function get_columns()
    {
        return array(
            'created_at' => 'Date',
            'event'      => 'Event',
            'user'       => 'User',
            'post'       => 'Post',
            'platform'   => 'Platform',
            'status'     => 'Status',
            'remote'     => 'Remote post',
            'error'      => 'Message / response',
        );
    }

    protected function filter_args()
    {
        $args = array();

        $event = sanitize_key($_GET['event'] ?? '');
        if (isset(self::EVENTS[$event])) {
            $args['event'] = $event;
        }

        $status = sanitize_key($_GET['status'] ?? '');
        if (in_array($status, array('success', 'failed'), true)) {
            $args['status'] = $status;
        }

        $platform = sanitize_key($_GET['platform'] ?? '');
        if ($platform !== '') {
            $args['platform'] = $platform;
        }

        $search = trim(sanitize_text_field(wp_unslash($_GET['s'] ?? '')));
        if ($search !== '') {
            $users = get_users(array(
                'search'         => '*' . $search . '*',
                'search_columns' => array('user_login', 'user_email', 'user_nicename', 'display_name'),
                'fields'         => 'ID',
                'number'         => 200,
            ));
            $args['user_ids'] = $users;
        }

        return $args;
    }

    public function prepare_items()
    {
        $args = $this->filter_args();
        $total = MM_Social_Poster_DB::count_logs($args);
        $paged = max(1, $this->get_pagenum());

        $args['per_page'] = self::PER_PAGE;
        $args['offset'] = ($paged - 1) * self::PER_PAGE;

        $this->items = MM_Social_Poster_DB::get_logs($args);
        $this->_column_headers = array($this->get_columns(), array(), array());
        $this->set_pagination_args(array(
            'total_items' => $total,
            'per_page'    => self::PER_PAGE,
            'total_pages' => (int) ceil($total / self::PER_PAGE),
        ));
    }

    protected function extra_tablenav($which)
    {
        if ($which !== 'top') {
            return;
        }
        $status = sanitize_key($_GET['status'] ?? '');
        $platform = sanitize_key($_GET['platform'] ?? '');
        $event = sanitize_key($_GET['event'] ?? '');
        ?>
        <div class="alignleft actions">
            <select name="event">
                <option value="">All events</option>
                <?php foreach (self::EVENTS as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($event, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All statuses</option>
                <option value="success" <?php selected($status, 'success'); ?>>Success</option>
                <option value="failed" <?php selected($status, 'failed'); ?>>Failed</option>
            </select>
            <select name="platform">
                <option value="">All platforms</option>
                <option value="linkedin" <?php selected($platform, 'linkedin'); ?>>LinkedIn</option>
            </select>
            <?php submit_button('Filter', '', 'filter_action', false); ?>
        </div>
        <?php
    }

    public function no_items()
    {
        echo 'No events logged yet.';
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'created_at':
                return esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $item->created_at));

            case 'event':
                return esc_html(self::EVENTS[$item->event] ?? $item->event);

            case 'user':
                $user = get_userdata($item->user_id);
                if (!$user) {
                    return '#' . (int) $item->user_id;
                }
                return sprintf(
                    '<a href="%s">%s</a><br><span class="description">%s</span>',
                    esc_url(get_edit_user_link($user->ID)),
                    esc_html($user->display_name),
                    esc_html($user->user_email)
                );

            case 'post':
                if (empty($item->post_id)) {
                    return '—';
                }
                $post = get_post($item->post_id);
                if (!$post) {
                    return '#' . (int) $item->post_id . ' <span class="description">(deleted)</span>';
                }
                return sprintf(
                    '<a href="%s">%s</a> <a href="%s" target="_blank" rel="noopener" class="description">view</a>',
                    esc_url(get_edit_post_link($post->ID)),
                    esc_html(wp_trim_words($post->post_title ?: $post->post_content, 8, '…')),
                    esc_url(get_permalink($post))
                );

            case 'platform':
                return esc_html(ucfirst($item->platform));

            case 'status':
                $color = $item->status === 'success' ? '#00a32a' : '#d63638';
                return '<span style="color:' . $color . ';font-weight:600;">' . esc_html(ucfirst($item->status)) . '</span>';

            case 'remote':
                if (empty($item->remote_url)) {
                    return '—';
                }
                return sprintf(
                    '<a href="%s" target="_blank" rel="noopener">Open</a><br><code class="description">%s</code>',
                    esc_url($item->remote_url),
                    esc_html($item->remote_post_id)
                );

            case 'error':
                if (empty($item->error_message) && empty($item->response)) {
                    return '—';
                }
                $out = '<strong>' . esc_html((string) $item->error_message) . '</strong>';
                if (!empty($item->response)) {
                    $decoded = json_decode($item->response, true);
                    $pretty = $decoded !== null ? wp_json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $item->response;
                    $out .= '<details><summary>Raw response</summary><pre style="max-width:480px;white-space:pre-wrap;font-size:11px;">' . esc_html($pretty) . '</pre></details>';
                }
                return $out;
        }
        return '';
    }
}
