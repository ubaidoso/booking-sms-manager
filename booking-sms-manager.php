<?php
/**
 * Plugin Name: Booking SMS Manager
 * Description: Sends customer SMS notifications for WP Booking Calendar events through Twilio and provides an admin SMS Outbox.
 * Version: 1.0.2
 * Author: Mr.Ubaid Tahir
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BSM_VERSION', '1.0.1');
define('BSM_FILE', __FILE__);
define('BSM_PATH', plugin_dir_path(__FILE__));
define('BSM_URL', plugin_dir_url(__FILE__));
define('BSM_DB_VERSION', '1.0.0');


/*
|--------------------------------------------------------------------------
| LOAD FILES
|--------------------------------------------------------------------------
*/

require_once BSM_PATH . 'includes/booking-data.php';
require_once BSM_PATH . 'includes/sms-messages.php';
require_once BSM_PATH . 'includes/sms-sender.php';
require_once BSM_PATH . 'includes/admin.php';
require_once BSM_PATH . 'includes/booking-hooks.php';
require_once BSM_PATH . 'includes/updater.php';


/*
|--------------------------------------------------------------------------
| CRON - EVERY 5 MINUTES
|--------------------------------------------------------------------------
*/

add_filter(
    'cron_schedules',
    'bsm_add_five_minute_cron'
);

function bsm_add_five_minute_cron($schedules)
{
    $schedules['bsm_every_five_minutes'] = array(
        'interval' => 5 * MINUTE_IN_SECONDS,
        'display'  => 'Every 5 Minutes - Booking SMS Manager',
    );

    return $schedules;
}


/*
|--------------------------------------------------------------------------
| SCHEDULE REMINDER CRON
|--------------------------------------------------------------------------
*/

function bsm_schedule_reminder_cron()
{
    if (
        !wp_next_scheduled(
            'bsm_process_reminder_sms'
        )
    ) {

        wp_schedule_event(
            time() + 60,
            'bsm_every_five_minutes',
            'bsm_process_reminder_sms'
        );
    }
}


/*
|--------------------------------------------------------------------------
| ACTIVATION
|--------------------------------------------------------------------------
*/

register_activation_hook(
    __FILE__,
    'bsm_activate'
);

function bsm_activate()
{
    global $wpdb;

    $table =
        $wpdb->prefix . 'booking_sms_logs';

    $charset_collate =
        $wpdb->get_charset_collate();

    require_once ABSPATH .
        'wp-admin/includes/upgrade.php';


    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        booking_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        event_type VARCHAR(50) NOT NULL DEFAULT '',
        recipient VARCHAR(50) NOT NULL DEFAULT '',
        message LONGTEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'failed',
        twilio_sid VARCHAR(100) NOT NULL DEFAULT '',
        twilio_status VARCHAR(50) NOT NULL DEFAULT '',
        http_code INT NOT NULL DEFAULT 0,
        api_response LONGTEXT NULL,
        error_message TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY booking_id (booking_id),
        KEY event_type (event_type),
        KEY status (status),
        KEY twilio_sid (twilio_sid)
    ) {$charset_collate};";


    dbDelta($sql);


    update_option(
        'bsm_db_version',
        BSM_DB_VERSION
    );


    if (!get_option('bsm_settings')) {

        add_option(
            'bsm_settings',
            array(
                'account_sid' => '',
                'auth_token' => '',
                'from_number' => '',
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | START CRON
    |--------------------------------------------------------------------------
    */

    bsm_schedule_reminder_cron();
}


/*
|--------------------------------------------------------------------------
| DEACTIVATION
|--------------------------------------------------------------------------
*/

register_deactivation_hook(
    __FILE__,
    'bsm_deactivate'
);

function bsm_deactivate()
{
    $timestamp =
        wp_next_scheduled(
            'bsm_process_reminder_sms'
        );

    if ($timestamp) {

        wp_unschedule_event(
            $timestamp,
            'bsm_process_reminder_sms'
        );
    }
}


/*
|--------------------------------------------------------------------------
| SETTINGS
|--------------------------------------------------------------------------
*/

function bsm_get_settings()
{
    $defaults = array(
        'account_sid' => '',
        'auth_token' => '',
        'from_number' => '',
    );

    $settings =
        get_option(
            'bsm_settings',
            array()
        );

    return wp_parse_args(
        is_array($settings)
            ? $settings
            : array(),
        $defaults
    );
}