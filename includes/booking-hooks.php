<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| BOOKING SMS MANAGER - BOOKING HOOKS
|--------------------------------------------------------------------------
|
| Handles:
|
| 1. New Booking / Pending SMS
| 2. Booking Approved SMS
| 3. Pre-Arrival Reminder
| 4. Pre-Pickup Reminder
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| SMS #1
| NEW BOOKING / PENDING
|--------------------------------------------------------------------------
*/

add_action(
    'wpbc_track_new_booking',
    'bsm_send_pending_booking_sms',
    10,
    1
);

function bsm_send_pending_booking_sms($params)
{
    $booking_id = 0;

    /*
    |--------------------------------------------------------------------------
    | GET BOOKING ID
    |--------------------------------------------------------------------------
    */

    if (
        is_array($params) &&
        isset($params['booking_id'])
    ) {
        $booking_id = absint(
            $params['booking_id']
        );
    }

    if (
        !$booking_id &&
        is_numeric($params)
    ) {
        $booking_id = absint($params);
    }

    if (!$booking_id) {

        error_log(
            'BSM PENDING SMS ERROR: Booking ID not found.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE PROTECTION
    |--------------------------------------------------------------------------
    */

    $sent_key =
        'bsm_pending_sms_sent_' .
        $booking_id;


    if (get_option($sent_key, false)) {

        error_log(
            'BSM PENDING SMS: Already sent for Booking #' .
            $booking_id
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $details = bsm_get_booking_details(
        $booking_id
    );


    if (!$details) {

        error_log(
            'BSM PENDING SMS: Booking #' .
            $booking_id .
            ' not found.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PHONE
    |--------------------------------------------------------------------------
    */

    if (empty($details['phone'])) {

        error_log(
            'BSM PENDING SMS ERROR: No phone number for Booking #' .
            $booking_id
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | BUILD MESSAGE
    |--------------------------------------------------------------------------
    */

    $message =
        bsm_pending_booking_message(
            $details
        );


    /*
    |--------------------------------------------------------------------------
    | SEND SMS
    |--------------------------------------------------------------------------
    */

    $sent = bsm_send_twilio_sms(
        $details['phone'],
        $message,
        $booking_id,
        'pending'
    );


    /*
    |--------------------------------------------------------------------------
    | MARK AS SENT
    |--------------------------------------------------------------------------
    */

    if ($sent) {

        add_option(
            $sent_key,
            current_time('mysql'),
            '',
            'no'
        );

        error_log(
            'BSM PENDING SMS MARKED AS SENT - Booking #' .
            $booking_id
        );
    }
}


/*
|--------------------------------------------------------------------------
| SMS #2
| BOOKING APPROVED
|--------------------------------------------------------------------------
*/

add_action(
    'wpbc_booking_action__approved',
    'bsm_send_approved_booking_sms',
    10,
    2
);

function bsm_send_approved_booking_sms(
    $booking_id,
    $approved = 0
) {

    $booking_id = absint(
        $booking_id
    );


    if (!$booking_id) {

        error_log(
            'BSM APPROVED SMS ERROR: Invalid Booking ID.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE PROTECTION
    |--------------------------------------------------------------------------
    */

    $sent_key =
        'bsm_approved_sms_sent_' .
        $booking_id;


    if (get_option($sent_key, false)) {

        error_log(
            'BSM APPROVED SMS: Already sent for Booking #' .
            $booking_id
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $details = bsm_get_booking_details(
        $booking_id
    );


    if (!$details) {

        error_log(
            'BSM APPROVED SMS: Booking #' .
            $booking_id .
            ' not found.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PHONE
    |--------------------------------------------------------------------------
    */

    if (empty($details['phone'])) {

        error_log(
            'BSM APPROVED SMS ERROR: No phone number for Booking #' .
            $booking_id
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | BUILD MESSAGE
    |--------------------------------------------------------------------------
    */

    $message =
        bsm_approved_booking_message(
            $details
        );


    /*
    |--------------------------------------------------------------------------
    | SEND SMS
    |--------------------------------------------------------------------------
    */

    $sent = bsm_send_twilio_sms(
        $details['phone'],
        $message,
        $booking_id,
        'approved'
    );


    /*
    |--------------------------------------------------------------------------
    | MARK AS SENT
    |--------------------------------------------------------------------------
    */

    if ($sent) {

        add_option(
            $sent_key,
            current_time('mysql'),
            '',
            'no'
        );

        error_log(
            'BSM APPROVED SMS MARKED AS SENT - Booking #' .
            $booking_id
        );
    }
}


/*
|--------------------------------------------------------------------------
| SMS #3
| PRE-ARRIVAL REMINDER
|--------------------------------------------------------------------------
|
| Sends 1 day before the booking date.
|
| Scheduled time:
| 9:00 AM WordPress/site timezone.
|
|--------------------------------------------------------------------------
*/

function bsm_send_pre_arrival_reminder_sms(
    $booking_id
) {

    $booking_id = absint(
        $booking_id
    );

    if (!$booking_id) {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE PROTECTION
    |--------------------------------------------------------------------------
    */

    $sent_key =
        'bsm_pre_arrival_sms_sent_' .
        $booking_id;


    if (get_option($sent_key, false)) {

        error_log(
            'BSM PRE-ARRIVAL SMS: Already sent for Booking #' .
            $booking_id
        );

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $details = bsm_get_booking_details(
        $booking_id
    );


    if (!$details) {

        error_log(
            'BSM PRE-ARRIVAL SMS: Booking #' .
            $booking_id .
            ' not found.'
        );

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PHONE
    |--------------------------------------------------------------------------
    */

    if (empty($details['phone'])) {

        error_log(
            'BSM PRE-ARRIVAL SMS ERROR: No phone number for Booking #' .
            $booking_id
        );

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | BUILD MESSAGE
    |--------------------------------------------------------------------------
    */

    $message =
        my_wpbc_pre_arrival_booking_message(
            $details
        );


    /*
    |--------------------------------------------------------------------------
    | SEND SMS
    |--------------------------------------------------------------------------
    */

    $sent = bsm_send_twilio_sms(
        $details['phone'],
        $message,
        $booking_id,
        'pre_arrival'
    );


    /*
    |--------------------------------------------------------------------------
    | MARK AS SENT
    |--------------------------------------------------------------------------
    */

    if ($sent) {

        add_option(
            $sent_key,
            current_time('mysql'),
            '',
            'no'
        );

        error_log(
            'BSM PRE-ARRIVAL SMS MARKED AS SENT - Booking #' .
            $booking_id
        );

        return true;
    }


    return false;
}


/*
|--------------------------------------------------------------------------
| SMS #4
| PRE-PICKUP REMINDER
|--------------------------------------------------------------------------
|
| Sends 2 hours before pickup time.
|
| Pickup time field:
|
| my_day_parts1
|
| Examples:
|
| 10:00am
| 9:30pm
|
|--------------------------------------------------------------------------
*/

function bsm_send_pre_pickup_reminder_sms(
    $booking_id
) {

    $booking_id = absint(
        $booking_id
    );

    if (!$booking_id) {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | DUPLICATE PROTECTION
    |--------------------------------------------------------------------------
    */

    $sent_key =
        'bsm_pre_pickup_sms_sent_' .
        $booking_id;


    if (get_option($sent_key, false)) {

        error_log(
            'BSM PRE-PICKUP SMS: Already sent for Booking #' .
            $booking_id
        );

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $details = bsm_get_booking_details(
        $booking_id
    );


    if (!$details) {

        error_log(
            'BSM PRE-PICKUP SMS: Booking #' .
            $booking_id .
            ' not found.'
        );

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PHONE
    |--------------------------------------------------------------------------
    */

    if (empty($details['phone'])) {

        error_log(
            'BSM PRE-PICKUP SMS ERROR: No phone number for Booking #' .
            $booking_id
        );

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK PICKUP TIME
    |--------------------------------------------------------------------------
    */

    if (
        empty($details['booking_time']) ||
        $details['booking_time'] === 'Not specified'
    ) {

        error_log(
            'BSM PRE-PICKUP SMS ERROR: Pickup time not specified for Booking #' .
            $booking_id
        );

        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | BUILD MESSAGE
    |--------------------------------------------------------------------------
    */

    $message =
        my_wpbc_pre_pickup_booking_message(
            $details
        );


    /*
    |--------------------------------------------------------------------------
    | SEND SMS
    |--------------------------------------------------------------------------
    */

    $sent = bsm_send_twilio_sms(
        $details['phone'],
        $message,
        $booking_id,
        'pre_pickup'
    );


    /*
    |--------------------------------------------------------------------------
    | MARK AS SENT
    |--------------------------------------------------------------------------
    */

    if ($sent) {

        add_option(
            $sent_key,
            current_time('mysql'),
            '',
            'no'
        );

        error_log(
            'BSM PRE-PICKUP SMS MARKED AS SENT - Booking #' .
            $booking_id
        );

        return true;
    }


    return false;
}


/*
|--------------------------------------------------------------------------
| CRON
| CHECK UPCOMING BOOKINGS
|--------------------------------------------------------------------------
|
| Runs every 5 minutes.
|
|--------------------------------------------------------------------------
*/

add_action(
    'bsm_process_reminder_sms',
    'bsm_process_reminder_sms'
);


function bsm_process_reminder_sms()
{
    global $wpdb;

    /*
    |--------------------------------------------------------------------------
    | CURRENT WORDPRESS TIME
    |--------------------------------------------------------------------------
    */

    $now = current_time('timestamp');

    $timezone = wp_timezone();

    error_log(
        'BSM CRON RUNNING | Time: ' .
        wp_date(
            'Y-m-d h:i:s A T',
            $now,
            $timezone
        ) .
        ' | Timestamp: ' .
        $now
    );


    /*
    |--------------------------------------------------------------------------
    | BOOKING TABLES
    |--------------------------------------------------------------------------
    */

    $booking_table =
        $wpdb->prefix . 'booking';

    $dates_table =
        $wpdb->prefix . 'bookingdates';


    /*
    |--------------------------------------------------------------------------
    | FIND BOOKINGS
    |--------------------------------------------------------------------------
    |
    | Look ahead 7 days.
    |
    | We get booking IDs first, then retrieve ALL dates
    | for each booking.
    |
    |--------------------------------------------------------------------------
    */

    $start_date = wp_date(
        'Y-m-d',
        $now,
        $timezone
    );

    $end_date = wp_date(
        'Y-m-d',
        strtotime(
            '+7 days',
            $now
        ),
        $timezone
    );


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING IDs
    |--------------------------------------------------------------------------
    */

    $booking_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT b.booking_id
             FROM {$booking_table} b
             INNER JOIN {$dates_table} d
                ON b.booking_id = d.booking_id
             WHERE d.booking_date >= %s
               AND d.booking_date <= %s
             ORDER BY b.booking_id ASC",
            $start_date,
            $end_date
        )
    );


    if (empty($booking_ids)) {

        error_log(
            'BSM CRON BOOKINGS FOUND | None'
        );

        return;
    }


    error_log(
        'BSM CRON BOOKINGS FOUND | ' .
        implode(
            ', ',
            array_map(
                'absint',
                $booking_ids
            )
        )
    );


    /*
    |--------------------------------------------------------------------------
    | PROCESS EACH BOOKING
    |--------------------------------------------------------------------------
    */

    foreach ($booking_ids as $booking_id) {

        $booking_id = absint(
            $booking_id
        );

        if (!$booking_id) {
            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | GET ALL DATES FOR THIS BOOKING
        |--------------------------------------------------------------------------
        */

        $booking_dates = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT booking_date
                 FROM {$dates_table}
                 WHERE booking_id = %d
                 ORDER BY booking_date ASC",
                $booking_id
            )
        );


        if (empty($booking_dates)) {

            error_log(
                'BSM CRON SKIPPED | Booking #' .
                $booking_id .
                ' | No booking dates found.'
            );

            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | CLEAN / NORMALIZE DATES
        |--------------------------------------------------------------------------
        */

        $clean_dates = array();

        foreach ($booking_dates as $booking_date) {

            $date_only = substr(
                trim($booking_date),
                0,
                10
            );

            if (
                preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/',
                    $date_only
                )
            ) {

                $clean_dates[] =
                    $date_only;
            }
        }


        $clean_dates =
            array_values(
                array_unique(
                    $clean_dates
                )
            );


        if (empty($clean_dates)) {

            error_log(
                'BSM CRON SKIPPED | Booking #' .
                $booking_id .
                ' | No valid dates.'
            );

            continue;
        }


        /*
        |--------------------------------------------------------------------------
        | FIRST AND LAST BOOKING DATE
        |--------------------------------------------------------------------------
        */

        sort($clean_dates);

        $first_booking_date =
            reset($clean_dates);

        $last_booking_date =
            end($clean_dates);


        /*
        |--------------------------------------------------------------------------
        | DEBUG
        |--------------------------------------------------------------------------
        */

        error_log(
            'BSM CRON PROCESSING BOOKING #' .
            $booking_id .
            ' | First Date: ' .
            $first_booking_date .
            ' | Last Date: ' .
            $last_booking_date
        );


        /*
        |--------------------------------------------------------------------------
        | PRE-ARRIVAL
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Pre-Arrival uses FIRST booking date.
        |
        | Example:
        |
        | Booking: Aug 19 -> Aug 22
        |
        | First date: Aug 19
        | Reminder:  Aug 18 at 9:00 AM
        |
        |--------------------------------------------------------------------------
        */

        bsm_process_pre_arrival_for_booking(
            $booking_id,
            $first_booking_date,
            $now
        );


        /*
        |--------------------------------------------------------------------------
        | PRE-PICKUP
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Pre-Pickup uses LAST booking date.
        |
        | Example:
        |
        | Booking: Aug 19 -> Aug 22
        | Pickup: 11:00 AM
        |
        | Last date: Aug 22
        | Reminder:  Aug 22 at 9:00 AM
        |
        |--------------------------------------------------------------------------
        */

        bsm_process_pre_pickup_for_booking(
            $booking_id,
            $last_booking_date,
            $now
        );
    }
}


/*
|--------------------------------------------------------------------------
| PRE-ARRIVAL CRON PROCESSOR
|--------------------------------------------------------------------------
|
| Uses FIRST / EARLIEST booking date.
|
| Sends:
|
| 1 day before booking
| at 9:00 AM
|
|--------------------------------------------------------------------------
*/

function bsm_process_pre_arrival_for_booking(
    $booking_id,
    $booking_date,
    $now
) {

    if (empty($booking_date)) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | TIMEZONE
    |--------------------------------------------------------------------------
    */

    $timezone = wp_timezone();


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE DATE
    |--------------------------------------------------------------------------
    */

    $date_only = substr(
        trim($booking_date),
        0,
        10
    );


    if (
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $date_only
        )
    ) {

        error_log(
            'BSM PRE-ARRIVAL ERROR | Booking #' .
            $booking_id .
            ' | Invalid booking date: ' .
            $booking_date
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE BOOKING DATE AT 9:00 AM
    |--------------------------------------------------------------------------
    */

    try {

        $booking_datetime = new DateTime(
            $date_only . ' 09:00:00',
            $timezone
        );

    } catch (Exception $e) {

        error_log(
            'BSM PRE-ARRIVAL ERROR | Booking #' .
            $booking_id .
            ' | Could not create date.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | MOVE ONE DAY BACK
    |--------------------------------------------------------------------------
    */

    $booking_datetime->modify(
        '-1 day'
    );


    $reminder_time =
        $booking_datetime->getTimestamp();


    /*
    |--------------------------------------------------------------------------
    | DEBUG LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        'BSM PRE-ARRIVAL CHECK | Booking #' .
        $booking_id .
        ' | Booking Date: ' .
        $date_only .
        ' | Reminder Target: ' .
        wp_date(
            'Y-m-d h:i:s A T',
            $reminder_time,
            $timezone
        ) .
        ' | Current Time: ' .
        wp_date(
            'Y-m-d h:i:s A T',
            $now,
            $timezone
        )
    );


    /*
    |--------------------------------------------------------------------------
    | SEND WINDOW
    |--------------------------------------------------------------------------
    |
    | Cron runs every 5 minutes.
    |
    | Allow 10 minutes.
    |
    |--------------------------------------------------------------------------
    */

    if (
        $now >= $reminder_time &&
        $now < (
            $reminder_time +
            10 * MINUTE_IN_SECONDS
        )
    ) {

        error_log(
            'BSM PRE-ARRIVAL TRIGGERED | Booking #' .
            $booking_id
        );


        bsm_send_pre_arrival_reminder_sms(
            $booking_id
        );
    }
}


/*
|--------------------------------------------------------------------------
| PRE-PICKUP CRON PROCESSOR
|--------------------------------------------------------------------------
|
| Uses LAST / LATEST booking date.
|
| Sends:
|
| 2 hours before pickup time.
|
| Pickup time comes from:
|
| my_day_parts1
|
|--------------------------------------------------------------------------
*/

function bsm_process_pre_pickup_for_booking(
    $booking_id,
    $booking_date,
    $now
) {

    if (empty($booking_date)) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | TIMEZONE
    |--------------------------------------------------------------------------
    */

    $timezone = wp_timezone();


    /*
    |--------------------------------------------------------------------------
    | GET BOOKING DETAILS
    |--------------------------------------------------------------------------
    */

    $details = bsm_get_booking_details(
        $booking_id
    );


    if (!$details) {

        error_log(
            'BSM PRE-PICKUP ERROR | Booking #' .
            $booking_id .
            ' | Booking details not found.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | GET PICKUP TIME
    |--------------------------------------------------------------------------
    |
    | Field:
    |
    | my_day_parts1
    |
    | Examples:
    |
    | 11:00am
    | 10:00am
    | 9:30pm
    |
    |--------------------------------------------------------------------------
    */

    $pickup_time =
        isset($details['booking_time'])
            ? trim($details['booking_time'])
            : '';


    if (
        empty($pickup_time) ||
        strtolower($pickup_time) === 'not specified'
    ) {

        error_log(
            'BSM PRE-PICKUP ERROR | Booking #' .
            $booking_id .
            ' | Pickup time not specified.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE LAST BOOKING DATE
    |--------------------------------------------------------------------------
    */

    $date_only = substr(
        trim($booking_date),
        0,
        10
    );


    if (
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $date_only
        )
    ) {

        error_log(
            'BSM PRE-PICKUP ERROR | Booking #' .
            $booking_id .
            ' | Invalid last booking date: ' .
            $booking_date
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE PICKUP DATETIME
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Uses LAST booking date.
    |
    | Example:
    |
    | Booking: Aug 19 -> Aug 22
    | Pickup:  11:00 AM
    |
    | Pickup:
    | Aug 22 at 11:00 AM
    |
    |--------------------------------------------------------------------------
    */

    try {

        $pickup_datetime = new DateTime(
            $date_only . ' ' . $pickup_time,
            $timezone
        );

    } catch (Exception $e) {

        error_log(
            'BSM PRE-PICKUP ERROR | Booking #' .
            $booking_id .
            ' | Could not parse pickup time: ' .
            $pickup_time
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | PICKUP TIMESTAMP
    |--------------------------------------------------------------------------
    */

    $pickup_timestamp =
        $pickup_datetime->getTimestamp();


    /*
    |--------------------------------------------------------------------------
    | TWO HOURS BEFORE PICKUP
    |--------------------------------------------------------------------------
    */

    $pickup_datetime->modify(
        '-2 hours'
    );


    $reminder_time =
        $pickup_datetime->getTimestamp();


    /*
    |--------------------------------------------------------------------------
    | DEBUG LOG
    |--------------------------------------------------------------------------
    */

    error_log(
        'BSM PRE-PICKUP CHECK | Booking #' .
        $booking_id .
        ' | Last Booking Date: ' .
        $date_only .
        ' | Pickup: ' .
        wp_date(
            'Y-m-d h:i:s A T',
            $pickup_timestamp,
            $timezone
        ) .
        ' | Reminder: ' .
        wp_date(
            'Y-m-d h:i:s A T',
            $reminder_time,
            $timezone
        ) .
        ' | Current: ' .
        wp_date(
            'Y-m-d h:i:s A T',
            $now,
            $timezone
        )
    );


    /*
    |--------------------------------------------------------------------------
    | SEND WINDOW
    |--------------------------------------------------------------------------
    |
    | Cron runs every 5 minutes.
    |
    | Allow 10 minutes.
    |
    |--------------------------------------------------------------------------
    */

    if (
        $now >= $reminder_time &&
        $now < (
            $reminder_time +
            10 * MINUTE_IN_SECONDS
        )
    ) {

        error_log(
            'BSM PRE-PICKUP TRIGGERED | Booking #' .
            $booking_id
        );


        bsm_send_pre_pickup_reminder_sms(
            $booking_id
        );
    }
}

/*
|--------------------------------------------------------------------------
| MARK BOOKING AS COMPLETE
|--------------------------------------------------------------------------
|
| Completion is managed by Booking SMS Manager.
|
| WP Booking Calendar remains internally Approved.
| We store completion in our SMS log/event system.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CHECK IF BOOKING IS APPROVED
|--------------------------------------------------------------------------
*/

function bsm_is_booking_approved($booking_id)
{
    global $wpdb;

    $booking_id = absint($booking_id);

    if (!$booking_id) {
        return false;
    }

    $dates_table = $wpdb->prefix . 'bookingdates';

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT approved
             FROM {$dates_table}
             WHERE booking_id = %d",
            $booking_id
        ),
        ARRAY_A
    );

    if (empty($rows)) {
        return false;
    }

    foreach ($rows as $row) {

        if (
            !isset($row['approved']) ||
            (int) $row['approved'] !== 1
        ) {
            return false;
        }
    }

    return true;
}

function bsm_has_booking_ended($booking_id)
{
    global $wpdb;

    $booking_id = absint($booking_id);

    if (!$booking_id) {
        return false;
    }

    $dates_table = $wpdb->prefix . 'bookingdates';

    $last_booking_date = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT MAX(booking_date)
             FROM {$dates_table}
             WHERE booking_id = %d",
            $booking_id
        )
    );

    if (empty($last_booking_date)) {
        return false;
    }

    /*
     * WPBC booking_date can contain a date/time.
     * We only need the calendar date.
     */
    $last_date = substr(
        trim($last_booking_date),
        0,
        10
    );

    if (
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $last_date
        )
    ) {
        return false;
    }

    /*
     * Use WordPress site's timezone.
     */
    $today = current_time('Y-m-d');

    /*
     * Last booking date must be BEFORE today.
     *
     * Example:
     * Last date = 2026-09-23
     * Today     = 2026-09-23
     * Result    = false
     *
     * Last date = 2026-09-22
     * Today     = 2026-09-23
     * Result    = true
     */
    return $last_date < $today;
}


/*
|--------------------------------------------------------------------------
| CHECK IF BOOKING IS COMPLETED
|--------------------------------------------------------------------------
|
| We use the SMS log table rather than creating one WordPress option
| for every booking.
|
| A successful "completed" SMS means the booking was completed.
|
|--------------------------------------------------------------------------
*/

function bsm_is_booking_completed($booking_id)
{
    global $wpdb;

    $booking_id = absint($booking_id);

    if (!$booking_id) {
        return false;
    }

    $table = $wpdb->prefix . 'booking_sms_logs';

    $completed = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id
             FROM {$table}
             WHERE booking_id = %d
             AND event_type = %s
             AND status = %s
             ORDER BY id DESC
             LIMIT 1",
            $booking_id,
            'completed',
            'success'
        )
    );

    return !empty($completed);
}


/*
|--------------------------------------------------------------------------
| AJAX - MARK BOOKING AS COMPLETE
|--------------------------------------------------------------------------
*/

add_action(
    'wp_ajax_bsm_mark_booking_complete',
    'bsm_ajax_mark_booking_complete'
);


function bsm_ajax_mark_booking_complete()
{
    /*
     * Permission.
     */
    if (!current_user_can('manage_options')) {

        wp_send_json_error(
            array(
                'message' =>
                    'You do not have permission to complete bookings.',
            ),
            403
        );
    }


    /*
     * Nonce.
     */
    check_ajax_referer(
        'bsm_mark_booking_complete',
        'nonce'
    );


    /*
     * Booking ID.
     */
    $booking_id = isset($_POST['booking_id'])
        ? absint($_POST['booking_id'])
        : 0;


    if (!$booking_id) {

        wp_send_json_error(
            array(
                'message' => 'Invalid booking ID.',
            ),
            400
        );
    }


    /*
     * Must still be approved.
     */
    if (!bsm_is_booking_approved($booking_id)) {

        error_log(
            'BSM COMPLETE BLOCKED | Booking #' .
            $booking_id .
            ' is not approved.'
        );

        wp_send_json_error(
            array(
                'message' =>
                    'Only approved bookings can be marked as complete.',
            ),
            400
        );
    }
	
	
		/*
		 * Last Date Must be passed.
		 */
		if (!bsm_has_booking_ended($booking_id)) {

		error_log(
			'BSM COMPLETE BLOCKED | Booking #' .
			$booking_id .
			' | Last booking date has not passed.'
		);

		wp_send_json_error(
			array(
				'message' =>
					'This booking cannot be marked as complete until the last booking date has passed.',
			),
			400
		);
	}


    /*
     * Already completed?
     */
    if (bsm_is_booking_completed($booking_id)) {

        wp_send_json_success(
            array(
                'message' =>
                    'This booking has already been completed.',
                'booking_id' =>
                    $booking_id,
                'completed' =>
                    true,
                'already_completed' =>
                    true,
            )
        );
    }


    /*
     * Lock the booking.
     *
     * Prevents double-click / two simultaneous requests.
     */
    $lock_key =
        'bsm_complete_lock_' .
        $booking_id;


    if (get_transient($lock_key)) {

        wp_send_json_error(
            array(
                'message' =>
                    'This booking is already being processed.',
            ),
            409
        );
    }


    set_transient(
        $lock_key,
        1,
        60
    );


    /*
     * Get booking details.
     */
    $details =
        bsm_get_booking_details(
            $booking_id
        );


    if (!$details) {

        delete_transient(
            $lock_key
        );

        wp_send_json_error(
            array(
                'message' =>
                    'Booking details could not be found.',
            ),
            404
        );
    }


    /*
     * Customer phone.
     */
    $phone = isset($details['phone'])
        ? trim($details['phone'])
        : '';


    if ($phone === '') {

        delete_transient(
            $lock_key
        );

        error_log(
            'BSM COMPLETE FAILED | Booking #' .
            $booking_id .
            ' | Customer phone missing.'
        );

        wp_send_json_error(
            array(
                'message' =>
                    'Customer phone number is missing.',
            ),
            400
        );
    }


    /*
     * Build completion SMS.
     */
    $message =
        bsm_completed_booking_message(
            $details
        );


    /*
     * Send SMS.
     *
     * The SMS sender creates the log entry.
     */
    $sent =
        bsm_send_twilio_sms(
            $phone,
            $message,
            $booking_id,
            'completed'
        );


    if (!$sent) {

        delete_transient(
            $lock_key
        );

        error_log(
            'BSM COMPLETE SMS FAILED | Booking #' .
            $booking_id
        );

        wp_send_json_error(
            array(
                'message' =>
                    'The completion SMS could not be sent.',
            ),
            500
        );
    }


    /*
     * Confirm that the successful completion event
     * was actually recorded.
     */
    if (!bsm_is_booking_completed($booking_id)) {

        delete_transient(
            $lock_key
        );

        error_log(
            'BSM COMPLETE FAILED | Booking #' .
            $booking_id .
            ' | SMS sent but completion log was not found.'
        );

        wp_send_json_error(
            array(
                'message' =>
                    'SMS was sent, but the completion status could not be saved.',
            ),
            500
        );
    }


    /*
     * Unlock.
     */
    delete_transient(
        $lock_key
    );


    error_log(
        'BSM BOOKING COMPLETED | Booking #' .
        $booking_id .
        ' | Completion SMS sent.'
    );


    /*
     * Success.
     */
    wp_send_json_success(
        array(
            'message' =>
                'Booking marked as complete and SMS sent successfully.',

            'booking_id' =>
                $booking_id,

            'completed' =>
                true,
        )
    );
}


/*
|--------------------------------------------------------------------------
| AJAX - GET COMPLETION STATUS
|--------------------------------------------------------------------------
|
| Allows the Booking Calendar listing JavaScript to determine which
| approved bookings are already completed after page reload.
|
|--------------------------------------------------------------------------
*/

add_action(
    'wp_ajax_bsm_get_completion_status',
    'bsm_ajax_get_completion_status'
);


function bsm_ajax_get_completion_status()
{
    if (!current_user_can('manage_options')) {

        wp_send_json_error(
            array(
                'message' => 'Unauthorized.',
            ),
            403
        );
    }


    check_ajax_referer(
        'bsm_mark_booking_complete',
        'nonce'
    );


    $booking_ids =
        isset($_POST['booking_ids']) &&
        is_array($_POST['booking_ids'])
            ? array_map(
                'absint',
                wp_unslash(
                    $_POST['booking_ids']
                )
            )
            : array();


    $booking_ids =
        array_values(
            array_filter(
                array_unique(
                    $booking_ids
                )
            )
        );


    if (empty($booking_ids)) {

        wp_send_json_success(
            array(
                'completed' => array(),
            )
        );
    }


    $completed = array();
	$ended = array();

	foreach ($booking_ids as $booking_id) {

		if (
			bsm_is_booking_completed(
				$booking_id
			)
		) {
			$completed[] = $booking_id;
		}

		if (
			bsm_has_booking_ended(
				$booking_id
			)
		) {
			$ended[] = $booking_id;
		}
	}


	wp_send_json_success(
		array(
			'completed' => $completed,
			'ended'     => $ended,
		)
	);
}

