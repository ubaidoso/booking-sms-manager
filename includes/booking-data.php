<?php
if (!defined('ABSPATH')) {
    exit;
}

function bsm_get_form_value($form, $field_name) {
    if (isset($form[$field_name]['value'])) {
        return sanitize_text_field($form[$field_name]['value']);
    }
    return '';
}

function bsm_parse_booking_form($form_string) {
    $form = array();

    if (empty($form_string)) {
        return $form;
    }

    $fields = explode('~', $form_string);

    foreach ($fields as $field) {
        $parts = explode('^', $field, 3);

        if (count($parts) === 3) {
            $form[$parts[1]] = array(
                'type'  => $parts[0],
                'value' => $parts[2],
            );
        }
    }

    return $form;
}

function bsm_format_yes_no($value) {
    $value = strtolower(trim((string) $value));

    if (in_array($value, array('yes', '1', 'true'), true)) {
        return 'Yes';
    }

    if (in_array($value, array('no', '0', 'false'), true)) {
        return 'No';
    }

    return $value !== '' ? $value : 'Not specified';
}

function bsm_get_booking_details($booking_id) {
    global $wpdb;

    $booking_id = absint($booking_id);

    if (!$booking_id) {
        return false;
    }

    $booking_table = $wpdb->prefix . 'booking';
    $dates_table   = $wpdb->prefix . 'bookingdates';

    $booking = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$booking_table} WHERE booking_id = %d LIMIT 1",
            $booking_id
        ),
        ARRAY_A
    );

    if (!$booking) {
        return false;
    }

    $form = bsm_parse_booking_form(isset($booking['form']) ? $booking['form'] : '');

    $first_name  = bsm_get_form_value($form, 'name1');
    $second_name = bsm_get_form_value($form, 'secondname1');

    $customer_name = trim($first_name . ' ' . $second_name);

    $email = bsm_get_form_value($form, 'email1');
    $phone = bsm_get_form_value($form, 'phone1');

    $date_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT booking_date, approved
             FROM {$dates_table}
             WHERE booking_id = %d
             ORDER BY booking_date ASC",
            $booking_id
        ),
        ARRAY_A
    );

    $booking_dates = array();

    foreach ((array) $date_rows as $row) {

    if (empty($row['booking_date'])) {
        continue;
    }

    $raw_date = trim($row['booking_date']);

    /*
    |--------------------------------------------------------------------------
    | WP Booking Calendar stores the booking date.
    | Keep the actual calendar date and avoid timezone shifting.
    |--------------------------------------------------------------------------
    */

    $date_only = substr($raw_date, 0, 10);

    if (
        preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $date_only
        )
    ) {

        $booking_dates[] = wp_date(
            'F j, Y',
            strtotime($date_only . ' 12:00:00')
        );
    }
}

    $booking_dates = array_values(array_unique($booking_dates));
    if (
        empty($booking_dates) &&
        !empty($booking['sort_date'])
    ) {

        $raw_date = trim(
            $booking['sort_date']
        );

        $date_only = substr(
            $raw_date,
            0,
            10
        );

        if (
            preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $date_only
            )
        ) {

            $booking_dates[] = wp_date(
                'F j, Y',
                strtotime($date_only . ' 12:00:00')
            );
        }
    }

    return array(
        'booking_id'       => $booking_id,
        'customer_name'    => $customer_name ?: 'Customer',
        'email'            => $email ?: 'Not provided',
        'phone'            => $phone,
        'visitors'         => bsm_get_form_value($form, 'visitors1') ?: 'Not specified',
        'dog_1'            => bsm_get_form_value($form, 'dog_name11'),
        'dog_2'            => bsm_get_form_value($form, 'dog_name21'),
        'dog_3'            => bsm_get_form_value($form, 'dog_name31'),
        'bath'             => bsm_format_yes_no(bsm_get_form_value($form, 'bath_radio1')),
        'oatmeal_shampoo'  => bsm_format_yes_no(bsm_get_form_value($form, 'bathoptions_radio1')),
        'nail_trim'        => bsm_format_yes_no(bsm_get_form_value($form, 'nailoptions_radio1')),
        'booking_time'     => bsm_get_form_value($form, 'my_day_parts1') ?: 'Not specified',
        'comment'          => bsm_get_form_value($form, 'my_details1'),
        'booking_dates'    => $booking_dates,
        'date_text'        => !empty($booking_dates) ? implode(', ', $booking_dates) : 'Not specified',
        'booking'          => $booking,
    );
}
