<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'bsm_register_admin_menu');
add_action('admin_init', 'bsm_register_settings');


/**
 * SMS Outbox - WordPress Screen Options
 */
function bsm_outbox_screen_option() {

    add_screen_option(
        'per_page',
        array(
            'label'   => 'SMS per page',
            'default' => 20,
            'option'  => 'bsm_outbox_per_page',
        )
    );
}

/**
 * Save SMS Outbox Screen Options value.
 */
function bsm_outbox_set_screen_option($status, $option, $value) {

    if ($option === 'bsm_outbox_per_page') {
        return max(1, absint($value));
    }

    return $status;
}

add_filter(
    'set-screen-option',
    'bsm_outbox_set_screen_option',
    10,
    3
);

function bsm_register_admin_menu() {
   $outbox_hook = add_menu_page(
        'Booking SMS Manager',
        'Booking SMS',
        'manage_options',
        'booking-sms-manager',
        'bsm_outbox_page',
        'dashicons-email-alt',
        56
    );

    /*
     * Add WordPress Screen Options to SMS Outbox.
     */
    add_action(
        "load-$outbox_hook",
        'bsm_outbox_screen_option'
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
	add_submenu_page(
		'booking-sms-manager',
		'SMS Templates',
		'SMS Templates',
		'manage_options',
		'booking-sms-templates',
		'bsm_sms_templates_page'
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

/*
|--------------------------------------------------------------------------
| SMS OUTBOX PAGE
|--------------------------------------------------------------------------
*/

function bsm_outbox_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $table = $wpdb->prefix . 'booking_sms_logs';

		// Pagination settings
	$per_page = get_user_option('bsm_outbox_per_page');

	/*
	 * Default to 20 if the user has not selected
	 * a value from Screen Options yet.
	 */
	if (!$per_page) {
		$per_page = 20;
	}

	$per_page = max(1, absint($per_page));

	$current_page = isset($_GET['paged'])
		? max(1, absint($_GET['paged']))
		: 1;

	$offset = ($current_page - 1) * $per_page;

		// Get total number of records
		$total_items = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table}"
		);

		$total_pages = ceil($total_items / $per_page);

		// Get records for current page
		$logs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		?>
		<div class="wrap">
			<h1>SMS Outbox</h1>
			<p>
		Showing <?php echo number_format_i18n($per_page); ?> SMS attempts per page.
		<?php if ($total_items > 0) : ?>
			Total records: <strong><?php echo number_format_i18n($total_items); ?></strong>
		<?php endif; ?>
	</p>

        <style>
            .bsm-status {
                display:inline-block;
                padding:4px 9px;
                border-radius:12px;
                font-weight:600;
                font-size:12px;
            }

            .bsm-success {
                background:#d7f5e5;
                color:#126b3a;
            }

            .bsm-failed {
                background:#f9d7d7;
                color:#8a1f1f;
            }

            .bsm-sending {
                background:#fff0c2;
                color:#765700;
            }

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
                            <td>
                                <?php echo absint($log['id']); ?>
                            </td>

                            <td>
                                <?php if (!empty($log['booking_id'])) : ?>
                                    #<?php echo absint($log['booking_id']); ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $log['event_type']
                                        )
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <?php echo esc_html($log['recipient']); ?>
                            </td>

                            <td>
                                <?php echo esc_html($log['created_at']); ?>
                            </td>

                            <td>
                                <details>
                                    <summary>View message</summary>

                                    <div class="bsm-message">
                                        <?php echo esc_html($log['message']); ?>
                                    </div>
                                </details>
                            </td>

                            <td>
                                <span class="bsm-status bsm-<?php echo esc_attr($log['status']); ?>">
                                    <?php echo esc_html(ucfirst($log['status'])); ?>
                                </span>

                                <?php if (!empty($log['twilio_status'])) : ?>
                                    <br>
                                    <small>
                                        Twilio:
                                        <?php echo esc_html($log['twilio_status']); ?>
                                    </small>
                                <?php endif; ?>

                                <?php if (!empty($log['error_message'])) : ?>
                                    <br>
                                    <small>
                                        <?php echo esc_html($log['error_message']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                echo !empty($log['twilio_sid'])
                                    ? esc_html($log['twilio_sid'])
                                    : '—';
                                ?>
                            </td>

                            <td>
                                <?php if (!empty($log['api_response'])) : ?>

                                    <details>
                                        <summary>View response</summary>

                                        <div class="bsm-response">
                                            <?php echo esc_html($log['api_response']); ?>
                                        </div>
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

<?php if ($total_pages > 1) : ?>

    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php
                echo esc_html(
                    sprintf(
                        _n(
                            '%s item',
                            '%s items',
                            $total_items,
                            'default'
                        ),
                        number_format_i18n($total_items)
                    )
                );
                ?>
            </span>

            <?php
            $page_url = function ($page) {
                return esc_url(
                    add_query_arg(
                        'paged',
                        $page,
                        remove_query_arg('paged')
                    )
                );
            };
            ?>

            <span class="pagination-links">

                <!-- First Page -->
                <?php if ($current_page > 1) : ?>

                    <a class="first-page button"
                       href="<?php echo $page_url(1); ?>">
                        <span aria-hidden="true">«</span>
                        <span class="screen-reader-text">
                            First page
                        </span>
                    </a>

                <?php else : ?>

                    <span class="tablenav-pages-navspan button disabled"
                          aria-hidden="true">
                        «
                    </span>

                <?php endif; ?>


                <!-- Previous Page -->
                <?php if ($current_page > 1) : ?>

                    <a class="prev-page button"
                       href="<?php echo $page_url($current_page - 1); ?>">
                        <span aria-hidden="true">‹</span>
                        <span class="screen-reader-text">
                            Previous page
                        </span>
                    </a>

                <?php else : ?>

                    <span class="tablenav-pages-navspan button disabled"
                          aria-hidden="true">
                        ‹
                    </span>

                <?php endif; ?>


                <!-- Current Page -->
                <span class="paging-input">

                    <label class="screen-reader-text" for="current-page-selector">
                        Current Page
                    </label>

                    <input
                        class="current-page"
                        id="current-page-selector"
                        type="text"
                        name="paged"
                        value="<?php echo esc_attr($current_page); ?>"
                        size="1"
                        aria-describedby="table-paging"
                    >

                    <span class="tablenav-paging-text">
                        <?php echo esc_html__('of', 'default'); ?>

                        <span class="total-pages">
                            <?php echo esc_html($total_pages); ?>
                        </span>
                    </span>

                </span>


                <!-- Next Page -->
                <?php if ($current_page < $total_pages) : ?>

                    <a class="next-page button"
                       href="<?php echo $page_url($current_page + 1); ?>">
                        <span aria-hidden="true">›</span>
                        <span class="screen-reader-text">
                            Next page
                        </span>
                    </a>

                <?php else : ?>

                    <span class="tablenav-pages-navspan button disabled"
                          aria-hidden="true">
                        ›
                    </span>

                <?php endif; ?>


                <!-- Last Page -->
                <?php if ($current_page < $total_pages) : ?>

                    <a class="last-page button"
                       href="<?php echo $page_url($total_pages); ?>">
                        <span aria-hidden="true">»</span>
                        <span class="screen-reader-text">
                            Last page
                        </span>
                    </a>

                <?php else : ?>

                    <span class="tablenav-pages-navspan button disabled"
                          aria-hidden="true">
                        »
                    </span>

                <?php endif; ?>

            </span>
        </div>
    </div>

<?php endif; ?>

    </div>
    <?php
}

/*
|--------------------------------------------------------------------------
| SMS TEMPLATES PAGE
|--------------------------------------------------------------------------
*/

function bsm_sms_templates_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $templates = bsm_get_sms_templates();

    /*
     * Save templates.
     */
    if (
        isset($_POST['bsm_save_sms_templates']) &&
        check_admin_referer(
            'bsm_save_sms_templates_action',
            'bsm_sms_templates_nonce'
        )
    ) {

        $new_templates = array();

        $template_keys = array(
            'pending',
            'approved',
            'pre_arrival',
            'pre_pickup',
            'completed',
        );

        foreach ($template_keys as $key) {

            $new_templates[$key] =
                isset($_POST['bsm_sms_template'][$key])
                    ? sanitize_textarea_field(
                        wp_unslash(
                            $_POST['bsm_sms_template'][$key]
                        )
                    )
                    : '';
        }

        update_option(
            'bsm_sms_templates',
            $new_templates
        );

        $templates =
            bsm_get_sms_templates();

        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>SMS templates saved successfully.</strong></p>';
        echo '</div>';
    }

    ?>

    <div class="wrap">

        <h1>Booking SMS Manager — SMS Templates</h1>

        <p>
            Edit the SMS messages below. The available placeholders
            will automatically be replaced with the customer's booking
            information when the SMS is sent.
        </p>

        <div
            style="
                background:#fff;
                border:1px solid #dcdcde;
                padding:15px 20px;
                margin:20px 0;
                max-width:900px;
            "
        >

            <h2 style="margin-top:0;">
                Available Placeholders
            </h2>

            <p>
                You can use the following placeholders in any SMS template:
            </p>

            <code>
                {booking_id}
            </code>

            &nbsp;

            <code>
                {customer_name}
            </code>

            &nbsp;

            <code>
                {email}
            </code>

            &nbsp;

            <code>
                {phone}
            </code>

            &nbsp;

            <code>
                {visitors}
            </code>

            &nbsp;

            <code>
                {dog_1}
            </code>

            &nbsp;

            <code>
                {dog_2}
            </code>

            &nbsp;

            <code>
                {dog_3}
            </code>

            &nbsp;

            <code>
                {bath}
            </code>

            &nbsp;

            <code>
                {oatmeal_shampoo}
            </code>

            &nbsp;

            <code>
                {nail_trim}
            </code>

            &nbsp;

            <code>
                {booking_time}
            </code>

            &nbsp;

            <code>
                {booking_date}
            </code>

            &nbsp;

            <code>
                {comment}
            </code>

        </div>


        <form method="post">

            <?php
            wp_nonce_field(
                'bsm_save_sms_templates_action',
                'bsm_sms_templates_nonce'
            );
            ?>


            <!-- PENDING -->

            <div
                style="
                    background:#fff;
                    border:1px solid #dcdcde;
                    padding:20px;
                    margin-bottom:20px;
                    max-width:900px;
                "
            >

                <h2>
                    1. New Booking / Pending
                </h2>

                <p>
                    Message sent when a new booking is received
                    and is pending approval.
                </p>

                <textarea
                    name="bsm_sms_template[pending]"
                    rows="18"
                    style="width:100%; font-family:monospace;"
                ><?php
                    echo esc_textarea(
                        $templates['pending']
                    );
                ?></textarea>

            </div>


            <!-- APPROVED -->

            <div
                style="
                    background:#fff;
                    border:1px solid #dcdcde;
                    padding:20px;
                    margin-bottom:20px;
                    max-width:900px;
                "
            >

                <h2>
                    2. Booking Approved
                </h2>

                <p>
                    Message sent when a booking is approved.
                </p>

                <textarea
                    name="bsm_sms_template[approved]"
                    rows="18"
                    style="width:100%; font-family:monospace;"
                ><?php
                    echo esc_textarea(
                        $templates['approved']
                    );
                ?></textarea>

            </div>


            <!-- PRE ARRIVAL -->

            <div
                style="
                    background:#fff;
                    border:1px solid #dcdcde;
                    padding:20px;
                    margin-bottom:20px;
                    max-width:900px;
                "
            >

                <h2>
                    3. Pre-Arrival Reminder
                </h2>

                <p>
                    Message sent before the customer's reservation.
                </p>

                <textarea
                    name="bsm_sms_template[pre_arrival]"
                    rows="15"
                    style="width:100%; font-family:monospace;"
                ><?php
                    echo esc_textarea(
                        $templates['pre_arrival']
                    );
                ?></textarea>

            </div>


            <!-- PRE PICKUP -->

            <div
                style="
                    background:#fff;
                    border:1px solid #dcdcde;
                    padding:20px;
                    margin-bottom:20px;
                    max-width:900px;
                "
            >

                <h2>
                    4. Pre-Pickup Reminder
                </h2>

                <p>
                    Message sent before the scheduled pickup time.
                </p>

                <textarea
                    name="bsm_sms_template[pre_pickup]"
                    rows="15"
                    style="width:100%; font-family:monospace;"
                ><?php
                    echo esc_textarea(
                        $templates['pre_pickup']
                    );
                ?></textarea>

            </div>


            <!-- COMPLETED -->

            <div
                style="
                    background:#fff;
                    border:1px solid #dcdcde;
                    padding:20px;
                    margin-bottom:20px;
                    max-width:900px;
                "
            >

                <h2>
                    5. Booking Completed
                </h2>

                <p>
                    Message sent when a booking is marked as completed.
                </p>

                <textarea
                    name="bsm_sms_template[completed]"
                    rows="12"
                    style="width:100%; font-family:monospace;"
                ><?php
                    echo esc_textarea(
                        $templates['completed']
                    );
                ?></textarea>

            </div>


            <?php
            submit_button(
                'Save SMS Templates',
                'primary',
                'bsm_save_sms_templates'
            );
            ?>

        </form>

    </div>

    <?php
}

/*
|--------------------------------------------------------------------------
| BOOKING CALENDAR - MARK AS COMPLETE
|--------------------------------------------------------------------------
*/

add_action(
    'admin_enqueue_scripts',
    'bsm_booking_calendar_complete_script'
);


function bsm_booking_calendar_complete_script($hook)
{
    /*
     * Only load on WP Booking Calendar.
     */
    if (
        !isset($_GET['page']) ||
        $_GET['page'] !== 'wpbc'
    ) {
        return;
    }


    /*
     * Admin only.
     */
    if (!current_user_can('manage_options')) {
        return;
    }


    wp_enqueue_script('jquery');


    wp_localize_script(
        'jquery',
        'BSMCompleteBooking',
        array(
            'ajaxUrl' =>
                admin_url('admin-ajax.php'),

            'nonce' =>
                wp_create_nonce(
                    'bsm_mark_booking_complete'
                ),
        )
    );


    $script = <<<'JS'
(function () {

    'use strict';


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING ID
    |--------------------------------------------------------------------------
    */

    function bsmGetBookingIdFromMenu(menu) {

        if (!menu) {
            return 0;
        }


        /*
         * Approved booking contains
         * "Set as Pending".
         */
        var pendingLink =
            menu.querySelector(
                '.ul_dropdown_menu_li_action_set_booking_pending'
            );


        if (pendingLink) {

            var onclick =
                pendingLink.getAttribute(
                    'onclick'
                ) || '';


            var match =
                onclick.match(
                    /\.val\(['"](\d+)['"]\)/
                );


            if (
                match &&
                match[1]
            ) {

                return parseInt(
                    match[1],
                    10
                );
            }
        }


        /*
         * Fallback: Edit booking.
         */
        var editLink =
            menu.querySelector(
                '.ul_dropdown_menu_li_action_edit_booking'
            );


        if (editLink) {

            var editOnclick =
                editLink.getAttribute(
                    'onclick'
                ) || '';


            var editMatch =
                editOnclick.match(
                    /add_booking_modal_from_row\s*\(\s*['"](\d+)['"]/
                );


            if (
                editMatch &&
                editMatch[1]
            ) {

                return parseInt(
                    editMatch[1],
                    10
                );
            }
        }


        return 0;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK APPROVED
    |--------------------------------------------------------------------------
    */

    function bsmIsApprovedMenu(menu) {

        if (!menu) {
            return false;
        }


        return !!menu.querySelector(
            '.ul_dropdown_menu_li_action_set_booking_pending'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING ROW
    |--------------------------------------------------------------------------
    */

    function bsmGetBookingRow(element) {

        if (!element) {
            return null;
        }


        /*
         * WPBC listing row.
         */
        var row =
            element.closest(
                '.wpbc_a_row'
            );


        if (row) {
            return row;
        }


        /*
         * Fallback.
         */
        var parent =
            element.parentElement;


        while (
            parent &&
            parent !== document.body
        ) {

            if (
                parent.querySelector(
                    '.wpbc_a_col__action'
                )
            ) {

                return parent;
            }


            parent =
                parent.parentElement;
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | CHANGE APPROVED LABEL TO COMPLETED
    |--------------------------------------------------------------------------
    */

    function bsmChangeStatusLabel(
        menu,
        bookingId
    ) {

        var row =
            bsmGetBookingRow(
                menu
            );


        if (!row) {
            return;
        }


        /*
         * Find WPBC columns.
         */
        var columns =
            row.querySelectorAll(
                '.wpbc_a_col'
            );


        columns.forEach(
            function (column) {

                /*
                 * Never modify action column.
                 */
                if (
                    column.classList.contains(
                        'wpbc_a_col__action'
                    )
                ) {
                    return;
                }


                /*
                 * Already changed.
                 */
                if (
                    column.getAttribute(
                        'data-bsm-completed'
                    ) === '1'
                ) {
                    return;
                }


                var text =
                    column.textContent.trim();


                /*
                 * Only replace a column whose
                 * visible status is Approved.
                 */
                if (
                    text === 'Approved'
                ) {

                    column.innerHTML =
                        column.innerHTML.replace(
                            /Approved/g,
                            'Completed'
                        );


                    column.setAttribute(
                        'data-bsm-completed',
                        '1'
                    );
                }

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADD COMPLETED MENU ITEM
    |--------------------------------------------------------------------------
    */

    function bsmAddCompletedAction(
        menu,
        bookingId
    ) {

        if (!menu || !bookingId) {
            return;
        }


        /*
         * Remove Mark as Complete if it exists.
         */
        var existing =
            menu.querySelector(
                '.bsm-mark-complete-action'
            );


        if (existing) {

            var existingLi =
                existing.closest('li');


            if (existingLi) {
                existingLi.remove();
            }
        }


        /*
         * Don't add twice.
         */
        if (
            menu.querySelector(
                '.bsm-completed-action'
            )
        ) {
            bsmChangeStatusLabel(
                menu,
                bookingId
            );

            return;
        }


        /*
         * Create LI.
         */
        var li =
            document.createElement(
                'li'
            );


        li.className =
            'bsm-completed-wrapper';


        /*
         * Create link.
         */
        var link =
            document.createElement(
                'a'
            );


        link.href =
            'javascript:void(0)';


        link.className =
            'ul_dropdown_menu_li_action bsm-completed-action';


        link.setAttribute(
            'data-booking-id',
            String(bookingId)
        );


        link.style.color =
            '#46b450';


        link.style.fontWeight =
            '600';


        link.innerHTML =
            'Completed' +
            '<i class="menu_icon icon-1x wpbc_icn_done"></i>';


        li.appendChild(
            link
        );


        /*
         * Insert after Edit Booking.
         */
        var editLink =
            menu.querySelector(
                '.ul_dropdown_menu_li_action_edit_booking'
            );


        if (editLink) {

            var editLi =
                editLink.closest('li');


            if (editLi) {

                editLi.parentNode.insertBefore(
                    li,
                    editLi.nextSibling
                );

            } else {

                menu.appendChild(
                    li
                );
            }

        } else {

            menu.appendChild(
                li
            );
        }


        /*
         * Change listing label.
         */
        bsmChangeStatusLabel(
            menu,
            bookingId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ADD MARK AS COMPLETE
    |--------------------------------------------------------------------------
    */

    function bsmAddCompleteAction(
        menu
    ) {

        if (!menu) {
            return;
        }


        /*
         * Only approved.
         */
        if (
            !bsmIsApprovedMenu(
                menu
            )
        ) {
            return;
        }


        /*
         * Get ID.
         */
        var bookingId =
            bsmGetBookingIdFromMenu(
                menu
            );


        if (!bookingId) {
            return;
        }


        /*
         * Don't add if already completed.
         */
        if (
            menu.querySelector(
                '.bsm-completed-action'
            )
        ) {
            return;
        }


        /*
         * Don't add twice.
         */
        if (
            menu.querySelector(
                '.bsm-mark-complete-action'
            )
        ) {
            return;
        }


        /*
         * Create menu item.
         */
        var li =
            document.createElement(
                'li'
            );


        li.className =
            'bsm-mark-complete-wrapper';


        var link =
            document.createElement(
                'a'
            );


        link.href =
            'javascript:void(0)';


        link.className =
            'ul_dropdown_menu_li_action bsm-mark-complete-action';


        link.setAttribute(
            'data-booking-id',
            String(bookingId)
        );


        link.innerHTML =
            'Mark as Complete' +
            '<i class="menu_icon icon-1x wpbc_icn_done"></i>';


        li.appendChild(
            link
        );


        /*
         * Insert after Edit Booking.
         */
        var editLink =
            menu.querySelector(
                '.ul_dropdown_menu_li_action_edit_booking'
            );


        if (editLink) {

            var editLi =
                editLink.closest('li');


            if (editLi) {

                editLi.parentNode.insertBefore(
                    li,
                    editLi.nextSibling
                );

            } else {

                menu.appendChild(
                    li
                );
            }

        } else {

            menu.appendChild(
                li
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD COMPLETION STATUS IN ONE REQUEST
    |--------------------------------------------------------------------------
    |
    | Instead of sending one AJAX request for every booking,
    | collect all booking IDs and send one request.
    |
    |--------------------------------------------------------------------------
    */

    function bsmLoadCompletionStatuses() {

        var menus =
            document.querySelectorAll(
                '.wpbc_ui_el__dropdown > .ul_dropdown_menu'
            );


        if (!menus.length) {
            return;
        }


        var bookingMap = {};


        menus.forEach(
            function (menu) {

                /*
                 * Only approved bookings.
                 */
                if (
                    !bsmIsApprovedMenu(
                        menu
                    )
                ) {
                    return;
                }


                var bookingId =
                    bsmGetBookingIdFromMenu(
                        menu
                    );


                if (bookingId) {

                    bookingMap[
                        bookingId
                    ] = true;
                }
            }
        );


        var bookingIds =
            Object.keys(
                bookingMap
            );


        if (!bookingIds.length) {
            return;
        }


        /*
         * Prevent duplicate AJAX calls.
         */
        if (
            document.body.getAttribute(
                'data-bsm-status-loading'
            ) === '1'
        ) {
            return;
        }


        document.body.setAttribute(
            'data-bsm-status-loading',
            '1'
        );


        var formData =
            new FormData();


        formData.append(
            'action',
            'bsm_get_completion_status'
        );


        formData.append(
            'nonce',
            BSMCompleteBooking.nonce
        );


        bookingIds.forEach(
            function (bookingId) {

                formData.append(
                    'booking_ids[]',
                    bookingId
                );
            }
        );


        fetch(
            BSMCompleteBooking.ajaxUrl,
            {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            }
        )
        .then(
            function (response) {
                return response.json();
            }
        )
        .then(
            function (result) {

                document.body.removeAttribute(
                    'data-bsm-status-loading'
                );


                if (
                    !result ||
                    !result.success ||
                    !result.data
                ) {
                    return;
                }


                var completed =
					result.data.completed ||
					[];

				var ended =
					result.data.ended ||
					[];

				var completedMap =
					{};

				var endedMap =
					{};

				completed.forEach(
					function (bookingId) {
						completedMap[
							String(bookingId)
						] = true;
					}
				);

				ended.forEach(
					function (bookingId) {
						endedMap[
							String(bookingId)
						] = true;
					}
				);


                /*
                 * Update every approved menu.
                 */
                menus.forEach(
                    function (menu) {

                        if (
                            !bsmIsApprovedMenu(
                                menu
                            )
                        ) {
                            return;
                        }


                        var bookingId =
                            bsmGetBookingIdFromMenu(
                                menu
                            );


                        if (!bookingId) {
                            return;
                        }


                        if (
							completedMap[
								String(bookingId)
							]
						) {

							bsmAddCompletedAction(
								menu,
								bookingId
							);

						} else if (
							endedMap[
								String(bookingId)
							]
						) {

							bsmAddCompleteAction(
								menu
							);

						}
                    }
                );
            }
        )
        .catch(
            function (error) {

                document.body.removeAttribute(
                    'data-bsm-status-loading'
                );


                console.log(
                    'BSM: Completion status check failed.',
                    error
                );


                /*
                 * If status check fails, still allow
                 * the normal Mark as Complete action.
                 */
                menus.forEach(
                    function (menu) {

                        bsmAddCompleteAction(
                            menu
                        );
                    }
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HANDLE MARK AS COMPLETE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function (event) {

            var link =
                event.target.closest(
                    '.bsm-mark-complete-action'
                );


            if (!link) {
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            /*
             * Prevent double click.
             */
            if (
                link.getAttribute(
                    'data-processing'
                ) === '1'
            ) {
                return;
            }


            var bookingId =
                parseInt(
                    link.getAttribute(
                        'data-booking-id'
                    ),
                    10
                );


            if (!bookingId) {
                return;
            }


            /*
             * Confirmation.
             */
            var confirmed =
                window.confirm(
                    'Are you sure you want to mark booking #' +
                    bookingId +
                    ' as complete and send the completion SMS?'
                );


            if (!confirmed) {
                return;
            }


            /*
             * Lock.
             */
            link.setAttribute(
                'data-processing',
                '1'
            );


            link.style.pointerEvents =
                'none';


            link.style.opacity =
                '0.6';


            var originalHtml =
                link.innerHTML;


            link.innerHTML =
                'Marking as Complete...';


            /*
             * AJAX.
             */
            var formData =
                new FormData();


            formData.append(
                'action',
                'bsm_mark_booking_complete'
            );


            formData.append(
                'booking_id',
                bookingId
            );


            formData.append(
                'nonce',
                BSMCompleteBooking.nonce
            );


            fetch(
                BSMCompleteBooking.ajaxUrl,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                }
            )
            .then(
                function (response) {
                    return response.json();
                }
            )
            .then(
                function (result) {

                    if (
                        !result ||
                        !result.success
                    ) {

                        var message =
                            result &&
                            result.data &&
                            result.data.message
                                ? result.data.message
                                : 'Unable to complete this booking.';


                        throw new Error(
                            message
                        );
                    }


                    /*
                     * Replace menu item with Completed.
                     */
                    var menu =
                        link.closest(
                            '.ul_dropdown_menu'
                        );


                    if (menu) {

                        bsmAddCompletedAction(
                            menu,
                            bookingId
                        );
                    }


                    /*
                     * Success message.
                     */
                    console.log(
                        'BSM: Booking #' +
                        bookingId +
                        ' marked as complete.'
                    );

                }
            )
            .catch(
                function (error) {

                    alert(
                        error.message ||
                        'Unable to complete this booking.'
                    );


                    /*
                     * Restore.
                     */
                    link.innerHTML =
                        originalHtml;


                    link.style.pointerEvents =
                        'auto';


                    link.style.opacity =
                        '1';


                    link.removeAttribute(
                        'data-processing'
                    );
                }
            );

        },
        true
    );


    /*
    |--------------------------------------------------------------------------
    | WPBC OPENS / UPDATES MENUS DYNAMICALLY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function () {

            setTimeout(
                bsmLoadCompletionStatuses,
                50
            );

            setTimeout(
                bsmLoadCompletionStatuses,
                250
            );

            setTimeout(
                bsmLoadCompletionStatuses,
                600
            );

        },
        true
    );


    /*
    |--------------------------------------------------------------------------
    | MUTATION OBSERVER
    |--------------------------------------------------------------------------
    */

    var observer =
        new MutationObserver(
            function () {

                /*
                 * Small delay prevents us from
                 * hammering AJAX while WPBC is
                 * modifying the DOM.
                 */
                clearTimeout(
                    window.bsmStatusTimer
                );


                window.bsmStatusTimer =
                    setTimeout(
                        bsmLoadCompletionStatuses,
                        150
                    );
            }
        );


    observer.observe(
        document.body,
        {
            childList: true,
            subtree: true
        }
    );


    /*
    |--------------------------------------------------------------------------
    | INITIAL RUN
    |--------------------------------------------------------------------------
    */

    setTimeout(
        bsmLoadCompletionStatuses,
        800
    );

})();
JS;


    wp_add_inline_script(
        'jquery',
        $script,
        'after'
    );
}