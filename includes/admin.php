<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'bsm_register_admin_menu');
add_action('admin_init', 'bsm_register_settings');

function bsm_register_admin_menu() {
    add_menu_page(
        'Booking SMS Manager',
        'Booking SMS',
        'manage_options',
        'booking-sms-manager',
        'bsm_outbox_page',
        'dashicons-email-alt',
        56
    );

    add_submenu_page(
        'booking-sms-manager',
        'SMS Outbox',
        'Outbox',
        'manage_options',
        'booking-sms-manager',
        'bsm_outbox_page'
    );

    add_submenu_page(
        'booking-sms-manager',
        'Twilio Settings',
        'Settings',
        'manage_options',
        'booking-sms-settings',
        'bsm_settings_page'
    );
}

function bsm_register_settings() {
    register_setting(
        'bsm_settings_group',
        'bsm_settings',
        array(
            'type'              => 'array',
            'sanitize_callback' => 'bsm_sanitize_settings',
            'default'           => array(),
        )
    );

    add_settings_section(
        'bsm_twilio_section',
        'Twilio Configuration',
        function () {
            echo '<p>Enter your Twilio credentials here. Do not place credentials directly in theme files.</p>';
        },
        'booking-sms-settings'
    );

    add_settings_field(
        'account_sid',
        'Account SID',
        'bsm_text_field',
        'booking-sms-settings',
        'bsm_twilio_section',
        array('key' => 'account_sid', 'type' => 'text')
    );

    add_settings_field(
        'auth_token',
        'Auth Token',
        'bsm_text_field',
        'booking-sms-settings',
        'bsm_twilio_section',
        array('key' => 'auth_token', 'type' => 'password')
    );

    add_settings_field(
        'from_number',
        'Twilio From Number',
        'bsm_text_field',
        'booking-sms-settings',
        'bsm_twilio_section',
        array('key' => 'from_number', 'type' => 'text')
    );
}

function bsm_sanitize_settings($input) {
    $old = bsm_get_settings();
    $input = is_array($input) ? $input : array();

    $auth_token = isset($input['auth_token']) ? trim($input['auth_token']) : '';

    // Keep the existing token if the password field is intentionally left blank.
    if ($auth_token === '') {
        $auth_token = $old['auth_token'];
    }

    return array(
        'account_sid' => isset($input['account_sid']) ? sanitize_text_field($input['account_sid']) : '',
        'auth_token'  => $auth_token,
        'from_number' => isset($input['from_number']) ? sanitize_text_field($input['from_number']) : '',
    );
}

function bsm_text_field($args) {
    $settings = bsm_get_settings();
    $key = $args['key'];
    $type = isset($args['type']) ? $args['type'] : 'text';
    $value = isset($settings[$key]) ? $settings[$key] : '';

    if ($key === 'auth_token') {
        printf(
            '<input type="password" name="bsm_settings[%1$s]" value="" class="regular-text" autocomplete="new-password" placeholder="%2$s">',
            esc_attr($key),
            esc_attr($value !== '' ? 'Configured — leave blank to keep it' : 'Enter Auth Token')
        );
        echo '<p class="description">For security, the saved token is never displayed. Leave this field blank to keep the current token.</p>';
        return;
    }

    printf(
        '<input type="%1$s" name="bsm_settings[%2$s]" value="%3$s" class="regular-text" autocomplete="off">',
        esc_attr($type),
        esc_attr($key),
        esc_attr($value)
    );
}

function bsm_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = bsm_get_settings();
    ?>
    <div class="wrap">
        <h1>Booking SMS Manager — Settings</h1>

        <div class="notice notice-warning inline">
            <p><strong>Security:</strong> Your previously shared Twilio Auth Token should be rotated immediately. The plugin stores the new credential in WordPress options instead of hard-coding it in PHP.</p>
        </div>

        <form method="post" action="options.php">
            <?php
            settings_fields('bsm_settings_group');
            do_settings_sections('booking-sms-settings');
            submit_button('Save Settings');
            ?>
        </form>

        <hr>

        <h2>Current Configuration</h2>
        <table class="widefat" style="max-width:700px;">
            <tbody>
                <tr>
                    <td><strong>Account SID</strong></td>
                    <td><?php echo !empty($settings['account_sid']) ? esc_html($settings['account_sid']) : 'Not configured'; ?></td>
                </tr>
                <tr>
                    <td><strong>Auth Token</strong></td>
                    <td><?php echo !empty($settings['auth_token']) ? 'Configured' : 'Not configured'; ?></td>
                </tr>
                <tr>
                    <td><strong>From Number</strong></td>
                    <td><?php echo !empty($settings['from_number']) ? esc_html($settings['from_number']) : 'Not configured'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
}

function bsm_outbox_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $table = $wpdb->prefix . 'booking_sms_logs';
    $logs = $wpdb->get_results(
        "SELECT * FROM {$table} ORDER BY id DESC LIMIT 100",
        ARRAY_A
    );

    ?>
    <div class="wrap">
        <h1>SMS Outbox</h1>
        <p>Latest 100 SMS attempts sent by Booking SMS Manager.</p>

        <style>
            .bsm-status {
                display:inline-block;
                padding:4px 9px;
                border-radius:12px;
                font-weight:600;
                font-size:12px;
            }
            .bsm-success { background:#d7f5e5; color:#126b3a; }
            .bsm-failed { background:#f9d7d7; color:#8a1f1f; }
            .bsm-sending { background:#fff0c2; color:#765700; }
            .bsm-response {
                max-width:420px;
                max-height:180px;
                overflow:auto;
                background:#f6f7f7;
                border:1px solid #ddd;
                padding:8px;
                white-space:pre-wrap;
                font-family:monospace;
                font-size:11px;
            }
            .bsm-message {
                max-width:360px;
                white-space:pre-wrap;
            }
        </style>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Booking</th>
                    <th>Type</th>
                    <th>Recipient</th>
                    <th>Date</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Twilio SID</th>
                    <th>API Response</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)) : ?>
                    <tr>
                        <td colspan="9">No SMS records yet.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo absint($log['id']); ?></td>
                            <td>
                                <?php if (!empty($log['booking_id'])) : ?>
                                    #<?php echo absint($log['booking_id']); ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $log['event_type']))); ?></td>
                            <td><?php echo esc_html($log['recipient']); ?></td>
                            <td><?php echo esc_html($log['created_at']); ?></td>
                            <td>
                                <details>
                                    <summary>View message</summary>
                                    <div class="bsm-message"><?php echo esc_html($log['message']); ?></div>
                                </details>
                            </td>
                            <td>
                                <span class="bsm-status bsm-<?php echo esc_attr($log['status']); ?>">
                                    <?php echo esc_html(ucfirst($log['status'])); ?>
                                </span>
                                <?php if (!empty($log['twilio_status'])) : ?>
                                    <br><small>Twilio: <?php echo esc_html($log['twilio_status']); ?></small>
                                <?php endif; ?>
                                <?php if (!empty($log['error_message'])) : ?>
                                    <br><small><?php echo esc_html($log['error_message']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo !empty($log['twilio_sid']) ? esc_html($log['twilio_sid']) : '—'; ?></td>
                            <td>
                                <?php if (!empty($log['api_response'])) : ?>
                                    <details>
                                        <summary>View response</summary>
                                        <div class="bsm-response"><?php echo esc_html($log['api_response']); ?></div>
                                    </details>
                                <?php elseif (!empty($log['error_message'])) : ?>
                                    <?php echo esc_html($log['error_message']); ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
